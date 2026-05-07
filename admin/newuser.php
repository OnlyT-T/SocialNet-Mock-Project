<?php
// /admin/newuser.php — Admin page to create a new user
require_once __DIR__ . '/../db_config.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $fullname = trim($_POST['fullname'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm']  ?? '';

    if ($username === '' || $fullname === '' || $password === '') {
        $error = 'All fields are required.';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $error = 'Username must be between 3 and 50 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $db   = get_db();
        $stmt = $db->prepare('SELECT id FROM account WHERE username = ?');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Username \"$username\" is already taken.";
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $ins    = $db->prepare('INSERT INTO account (username, fullname, password) VALUES (?, ?, ?)');
            $ins->bind_param('sss', $username, $fullname, $hashed);
            if ($ins->execute()) {
                $success = "User \"$username\" created successfully!";
            } else {
                $error = 'Failed to create user. Please try again.';
            }
            $ins->close();
        }
        $stmt->close();
        $db->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Create New User | SocialNet</title>
    <link rel="stylesheet" href="/style.css">
    <style>
        body { background: #1a1a2e; }
        .admin-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .admin-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,.35);
            padding: 2.5rem 2.2rem;
            width: 100%;
            max-width: 460px;
        }
        .admin-card h1 {
            font-size: 1.5rem;
            margin-bottom: .2rem;
            color: #c0392b;
        }
        .admin-badge {
            display: inline-block;
            background: #c0392b;
            color: #fff;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: 1px;
            padding: .15rem .5rem;
            border-radius: 4px;
            text-transform: uppercase;
            margin-bottom: 1.4rem;
        }
    </style>
</head>
<body>
<div class="admin-wrapper">
    <div class="admin-card">
        <h1>&#128274; Admin Panel</h1>
        <span class="admin-badge">SocialNet</span>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" action="/admin/newuser.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       placeholder="e.g. johndoe" autocomplete="off" required>
            </div>
            <div class="form-group">
                <label for="fullname">Full Name</label>
                <input type="text" id="fullname" name="fullname"
                       value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>"
                       placeholder="e.g. John Doe" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       placeholder="Min. 6 characters" required>
            </div>
            <div class="form-group">
                <label for="confirm">Confirm Password</label>
                <input type="password" id="confirm" name="confirm"
                       placeholder="Repeat password" required>
            </div>
            <button type="submit" class="btn btn-full" style="background:#c0392b;">
                Create User
            </button>
        </form>
    </div>
</div>
</body>
</html>
