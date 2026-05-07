<?php
// /socialnet/index.php — Home Page
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /socialnet/signin.php');
    exit;
}

require_once __DIR__ . '/../db_config.php';

$db    = get_db();
$stmt  = $db->prepare('SELECT id, username, fullname FROM account WHERE id != ? ORDER BY username ASC');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$others = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home | SocialNet</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<?php require_once __DIR__ . '/../menubar.php'; ?>

<div class="page-container">

    <!-- Current user info -->
    <div class="card">
        <h2>&#127968; Welcome back!</h2>
        <div class="profile-header">
            <div class="profile-avatar-lg"><?= htmlspecialchars(mb_substr($_SESSION['fullname'], 0, 1)) ?></div>
            <div class="profile-meta">
                <h2><?= htmlspecialchars($_SESSION['fullname']) ?></h2>
                <p class="username-tag">@<?= htmlspecialchars($_SESSION['username']) ?></p>
            </div>
        </div>
        <a href="/socialnet/profile.php" class="btn btn-secondary">View My Profile</a>
    </div>

    <!-- Other users -->
    <div class="card">
        <h2>&#128101; People on SocialNet</h2>
        <?php if (empty($others)): ?>
            <p style="color:#65676b; font-size:.93rem;">No other users yet. Ask the admin to add more!</p>
        <?php else: ?>
            <ul class="user-list">
                <?php foreach ($others as $u): ?>
                <li>
                    <div class="user-avatar"><?= htmlspecialchars(mb_substr($u['fullname'], 0, 1)) ?></div>
                    <div class="user-info-block">
                        <div class="uname"><?= htmlspecialchars($u['fullname']) ?></div>
                        <div class="fname">@<?= htmlspecialchars($u['username']) ?></div>
                    </div>
                    <a href="/socialnet/profile.php?owner=<?= urlencode($u['username']) ?>" class="btn btn-secondary" style="font-size:.85rem; padding:.4rem .9rem;">
                        View Profile
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

</div>
</body>
</html>
