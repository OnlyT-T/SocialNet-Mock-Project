<?php
// /socialnet/profile.php — View profile (own or another user's)
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /socialnet/signin.php');
    exit;
}

require_once __DIR__ . '/../db_config.php';

$db = get_db();

$owner_name = isset($_GET['owner']) ? trim($_GET['owner']) : '';

// Determine whose profile to show
if ($owner_name !== '') {
    $owner_name = trim($_GET['owner']);
    $stmt = $db->prepare('SELECT id, username, fullname, description FROM account WHERE username = ?');
    $stmt->bind_param('s', $owner_name);
} else {
    $stmt = $db->prepare('SELECT id, username, fullname, description FROM account WHERE id = ?');
    $stmt->bind_param('i', $_SESSION['user_id']);
}

$stmt->execute();
$owner = $stmt->get_result()->fetch_assoc();
$stmt->close();
$db->close();

if (!$owner) {
    http_response_code(404);
    echo '<p style="padding:2rem; font-family:sans-serif;">User not found.</p>';
    exit;
}

$is_own = ($owner['id'] === $_SESSION['user_id']);
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
                <?php endif; ?>
            </div>
        </div>

        <h2 style="font-size:1rem; color:#65676b; margin-bottom:.7rem;">&#128221; About</h2>
        <div class="description-box"><?php if (trim($owner['description'] ?? '') !== ''): ?><?= nl2br(htmlspecialchars($owner['description'])) ?>
            <?php else: ?>
                <span style="color:#bbb; font-style:italic;">
                    <?= $is_own ? 'You haven\'t written anything yet. Go to Settings to add a description.' : 'This user hasn\'t written anything yet.' ?>
                </span>
            <?php endif; ?>
        </div>

        <?php if ($is_own): ?>
            <div style="margin-top:1.2rem;">
                <a href="/socialnet/setting.php" class="btn">Edit Profile</a>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
