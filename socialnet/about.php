<?php
// /socialnet/about.php — Static about page
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /socialnet/signin.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About | SocialNet</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<?php require_once __DIR__ . '/../menubar.php'; ?>

<div class="page-container">
    <div class="card">
        <h2>&#8505;&#65039; About This Project</h2>
        <div class="about-info">
            <p><span>Student Name:</span> <?= htmlspecialchars($_SESSION['fullname']) ?></p>
            <p><span>Student Number:</span> 1694593</p>
        </div>
        <hr style="margin:1.2rem 0; border:none; border-top:1px solid #e4e6eb;">
        <p style="color:#65676b; font-size:.92rem;">
            @SocialNet is a mock social network web application built with PHP, MySQL, Nginx, and Linux.
        </p>
    </div>
</div>
</body>
</html>
