<?php
// /socialnet/profile.php — View profile (own or a friend's; strangers are blocked)
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /socialnet/signin.php');
    exit;
}

require_once __DIR__ . '/../db_config.php';

$db = get_db();
$me = (int) $_SESSION['user_id'];

$owner_name = isset($_GET['owner']) ? trim($_GET['owner']) : '';

// Resolve which account to display
if ($owner_name !== '') {
    $stmt = $db->prepare('SELECT id, username, fullname, description FROM account WHERE username = ?');
    $stmt->bind_param('s', $owner_name);
} else {
    $stmt = $db->prepare('SELECT id, username, fullname, description FROM account WHERE id = ?');
    $stmt->bind_param('i', $me);
}

$stmt->execute();
$owner = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$owner) {
    $db->close();
    http_response_code(404);
    echo '<p style="padding:2rem; font-family:sans-serif;">User not found.</p>';
    exit;
}

$is_own = ((int)$owner['id'] === $me);

// ── Access control: only own profile OR accepted friends ────────────────
/*$is_friend = false;
if (!$is_own) {
    $other_id = (int)$owner['id'];
    $stmt = $db->prepare('
        SELECT id FROM friendship
        WHERE  status = "accepted"
          AND  ((requester_id = ? AND receiver_id = ?)
             OR (requester_id = ? AND receiver_id = ?))
        LIMIT 1
    ');
    $stmt->bind_param('iiii', $me, $other_id, $other_id, $me);
    $stmt->execute();
    $is_friend = (bool) $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$db->close();

// Redirect strangers away (they should not see profile contents)
if (!$is_own && !$is_friend) {
    header('Location: /socialnet/index.php');
    exit;
} */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($owner['fullname']) ?>'s Profile | SocialNet</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<?php require_once __DIR__ . '/../menubar.php'; ?>

<div class="page-container">
    <div class="card">
        <div class="profile-header">
            <div class="profile-avatar-lg"><?= htmlspecialchars(substr($owner['fullname'], 0, 1)) ?></div>
            <div class="profile-meta">
                <h2 style="margin: 0; line-height: 1.5;"><?= htmlspecialchars($owner['fullname']) ?></h2>
                <p class="username-tag">@<?= htmlspecialchars($owner['username']) ?></p>
                <?php if ($is_own): ?>
                    <span style="font-size:.8rem; background:#e7f3ff; color:#1877f2; padding:.2rem .55rem; border-radius:4px; font-weight:600;">You</span>
                <?php elseif ($is_friend): ?>
                    <span style="font-size:.8rem; background:#e8f5e9; color:#1b5e20; padding:.2rem .55rem; border-radius:4px; font-weight:600;">&#128149; Friend</span>
                <?php endif; ?>
            </div>
        </div>

        <h2 style="font-size:1rem; color:#65676b; margin-bottom:.7rem;">&#128221; About</h2>
        <div class="description-box">
	    <?php if (trim($owner['description'] ?? '') !== ''): ?>
		<?= nl2br($owner['description']) ?>
            <?php else: ?>
                <span style="color:#bbb; font-style:italic;">
                    <?= $is_own
                        ? 'You haven\'t written anything yet. Go to Settings to add a description.'
                        : 'This user hasn\'t written anything yet.' ?>
                </span>
            <?php endif; ?>
        </div>

        <div style="margin-top:1.2rem; display:flex; gap:.75rem; flex-wrap:wrap;">
            <?php if ($is_own): ?>
                <a href="/socialnet/setting.php" class="btn">Edit Profile</a>
            <?php endif; ?>
            <a href="/socialnet/index.php" class="btn btn-secondary">&#8592; Back to Home</a>
        </div>
    </div>
</div>
</body>
</html>
