<?php
require_once 'inc/util.inc';
session_start();
$_SESSION['error']= "";
$past= time() - 100;
//this makes the time in the past to destroy the cookie
setcookie('user', 'gone', $past);
setcookie('pass', 'gone', $past);
session_destroy();
header("Location: login.php");
return;
?>