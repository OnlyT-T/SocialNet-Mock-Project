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
	    margin-top: 0.5rem;
        }

        /* ── Enhanced input fields ── */
        .input-wrapper {
            position: relative;
        }
        .input-wrapper .input-icon {
            position: absolute;
            left: .85rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1rem;
            pointer-events: none;
            opacity: .55;
        }
        .input-wrapper input {
            padding-left: 2.5rem !important;
            height: 46px;
            border: 2px solid #e0e0e0 !important;
            border-radius: 8px !important;
            font-size: .95rem !important;
            background: #fafafa !important;
            transition: border-color .2s, box-shadow .2s, background .2s !important;
            color: #1a1a2e !important;
        }
        .input-wrapper input::placeholder { color: #bbb; }
        .input-wrapper input:focus {
            border-color: #c0392b !important;
            background: #fff !important;
            box-shadow: 0 0 0 3px rgba(192,57,43,.12) !important;
            outline: none !important;
        }
        .form-group label {
            font-size: .8rem !important;
            font-weight: 700 !important;
            letter-spacing: .4px;
            text-transform: uppercase;
            color: #000000 !important;
            margin-bottom: .4rem !important;
        }
        .form-group { margin-bottom: 1.15rem !important; }

        /* Submit button */
        .btn-admin {
            width: 100%;
            height: 46px;
            background: #c0392b;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: .4px;
            cursor: pointer;
            margin-top: .4rem;
            transition: background .2s, transform .1s, box-shadow .2s;
            box-shadow: 0 3px 10px rgba(192,57,43,.35);
        }
        .btn-admin:hover {
            background: #a93226;
            box-shadow: 0 5px 14px rgba(192,57,43,.45);
        }
        .btn-admin:active { transform: scale(.98); }

        /* ── Toast notification ── */
        .toast {
            position: fixed;
            top: 1.5rem;
            right: 1.5rem;
            display: flex;
            align-items: center;
            gap: .75rem;
            background: #23983e;
            color: #fff;
            padding: .85rem 1.3rem;
            border-radius: 10px;
            box-shadow: 0 6px 20px rgba(0,0,0,.25);
            font-size: 1.0rem;
            font-weight: 600;
            z-index: 9999;
            animation: slideIn .35s ease, fadeOut .5s ease 2.8s forwards;
            pointer-events: none;
        }
        .toast-icon {
            font-size: 1.3rem;
            line-height: 1;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(60px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        @keyframes fadeOut {
            from { opacity: 1; transform: translateX(0); }
            to   { opacity: 0; transform: translateX(60px); }
        }
    </style>
</head>
<body>
<div class="admin-wrapper">
    <div class="admin-card">
        <h1 style="font-size: 36px">&#128274;ADMIN PANEL</h1>
        <span class="admin-badge">SocialNet</span>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="/admin/newuser.php">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-wrapper">
                    <span class="input-icon">&#64;</span>
                    <input type="text" id="username" name="username"
                           placeholder="e.g. nguyenvana" autocomplete="off" required>
                </div>
            </div>
            <div class="form-group">
                <label for="fullname">Full Name</label>
                <div class="input-wrapper">
                    <span class="input-icon">&#128100;</span>
                    <input type="text" id="fullname" name="fullname"
                           placeholder="e.g. Nguyen Van A" required>
                </div>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <span class="input-icon">&#128274;</span>
                    <input type="password" id="password" name="password"
                           placeholder="Min. 6 characters" required>
                </div>
            </div>
            <div class="form-group">
                <label for="confirm">Confirm Password</label>
                <div class="input-wrapper">
                    <span class="input-icon">&#10003;</span>
                    <input type="password" id="confirm" name="confirm"
                           placeholder="Repeat password" required>
                </div>
            </div>

            <button type="submit" class="btn-admin">&#43; Create User</button>
        </form>
    </div>
</div>
<?php if ($success): ?>
<div class="toast" id="toast">
    <span class="toast-icon">&#10004;</span>
    <span>Register Success!</span>
</div>
<script>
    setTimeout(() => {
        const t = document.getElementById('toast');
        if (t) t.remove();
    }, 3300);
</script>
<?php endif; ?>
</body>
</html>
