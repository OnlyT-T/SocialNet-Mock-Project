<?php
// /socialnet/signin.php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: /socialnet/index.php');
    exit;
}

require_once __DIR__ . '/../db_config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
	$db = get_db();
	
	$query = "SELECT * FROM account WHERE username = '" . $username . "' AND password = '" . $password . "'";

	//var_dump($query);

	$result = $db->query($query);

	//var_dump($result);
	//die();

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['fullname'] = $user['fullname'];

            header('Location: /socialnet/index.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
	}
	$db->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | SocialNet</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <h1>&#127758; SocialNet</h1>
        <p class="tagline">Connect with people around you.</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="/socialnet/signin.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       placeholder="Your username" autocomplete="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       placeholder="Your password" autocomplete="current-password" required>
            </div>
            <button type="submit" class="btn btn-full">Sign In</button>
        </form>
    </div>
</div>
</body>
</html>
