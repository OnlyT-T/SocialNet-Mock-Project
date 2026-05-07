<?php
// /socialnet/signout.php — Destroy session and redirect
session_start();
session_unset();
session_destroy();

header('Location: /socialnet/signin.php');
exit;
