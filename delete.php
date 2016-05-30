<?php
require_once 'inc/util.inc';
session_start();
$_SESSION['error']= "";
if(isset($_COOKIE['user'])) {
	$username= $_COOKIE['user'];
	$password= $_COOKIE['pass'];
	$db_link= mysql_connect($db, $db_user, $db_pass, TRUE, MYSQL_CLIENT_INTERACTIVE);
	if (!$db_link) error(mysql_error());
	mysql_query( "SET NAMES utf8", $db_link );
	mysql_query( "SET CHARACTER SET utf8", $db_link );
	mysql_query("SET SESSION interactive_timeout=30", $db_link);
	mysql_select_db($dbname);
	$user= mysql_query("SELECT * FROM user WHERE firstname = '$username'");
	if (!$user) error(mysql_error());
	while($userinfo= mysql_fetch_array($user)) {
		if($password != $userinfo['password']) {
			header("Location: login.php");
			return;
		} else {
			$_SESSION['unlocked']= $userinfo['unlocked'];
			if (!(bool) $_SESSION['unlocked']) {
				header("Location: unlock.php");
				return;
			}
			$id=$_GET["id"];
			$step=$_GET["step"];
			$anchor=$_GET["anchor"];
			$userid = $userinfo['id'];
			$sql="DELETE FROM formalised_constraint WHERE userid = '$userid' AND id='$id'";
			$result=mysql_query($sql);
			if (!$result) error(mysql_error());
			header("location: $step.php#$anchor");
		}
	}
	mysql_free_result($user);
	mysql_close($db_link);
} else {
	header("Location: login.php");
	return;
}
?>