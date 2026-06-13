<?php
// auth/logout.php
session_start();
session_destroy();
header('Location: /jobboard/auth/login.php');
exit;