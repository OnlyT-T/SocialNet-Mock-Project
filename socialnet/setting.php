<?php
// /socialnet/setting.php — Edit profile description
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /socialnet/signin.php');
    exit;
}

require_once __DIR__ . '/../db_config.php';

$db      = get_db();
$error   = '';
$success = '';

// Load current description
$stmt = $db->prepare('SELECT description FROM account WHERE id = ?');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$row  = $stmt->get_result()->fetch_assoc();
$stmt->close();
$description = $row['description'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_desc = trim($_POST['description'] ?? '');
    $upd = $db->prepare('UPDATE account SET description = ? WHERE id = ?');
    $upd->bind_param('si', $new_desc, $_SESSION['user_id']);
    if ($upd->execute()) {
        $description = $new_desc;
        $success = 'Profile updated successfully!';
    } else {
        $error = 'Failed to update profile.';
    }
    $upd->close();
}
$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | SocialNet</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<?php require_once __DIR__ . '/../menubar.php'; ?>

<div class="page-container">
    <div class="card">
        <h2>&#9881;&#65039; Profile Settings</h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <p style="margin-bottom:1.1rem; color:#65676b; font-size:.93rem;">
            Editing profile for <strong>@<?= htmlspecialchars($_SESSION['username']) ?></strong>
        </p>

        <form method="POST" action="/socialnet/setting.php">
            <div class="form-group">
                <label for="description">About Me / Profile Description</label>
                <textarea id="description" name="description"
                          placeholder="Write something about yourself..."><?= htmlspecialchars($description) ?></textarea>
            </div>
            <button type="submit" class="btn">Save Changes</button>
        </form>
    </div>
</div>
</body>
</html>
