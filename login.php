<?php
require_once 'inc/util.inc';
session_start();
$_SESSION['error'] = "";
if (isset($_COOKIE['user'])) {
	$username = $_COOKIE['user'];
	$password = $_COOKIE['pass'];
	$db_link = mysql_connect($db, $db_user, $db_pass, TRUE, MYSQL_CLIENT_INTERACTIVE);
	if (!$db_link)
		error(mysql_error());
	mysql_query("SET SESSION interactive_timeout=30", $db_link);
	mysql_select_db($dbname);
	$user = mysql_query("SELECT * FROM user WHERE firstname = '$username'");
	if (!$user)
		error(mysql_error());
	while ($userinfo = mysql_fetch_array($user)) {
		if ($password == $userinfo['password']) {
			$_SESSION['unlocked'] = $userinfo['unlocked'];
			if ((bool) $_SESSION['unlocked']) {
				header("Location: index.php");
			} else {
				header("Location: unlock.php");
			}
			return;
		}
	}
	mysql_free_result($user);
	mysql_close($db_link);
}
//if the login form is submitted
if (isset($_POST['submit'])) {
	// makes sure they filled it in
	if (!$_POST['username'] | !$_POST['password']) {
		die('You did not fill in a required field.');
	}
	$db_link2 = mysql_connect($db, $db_user, $db_pass);
	if (!$db_link2)
		error(mysql_error());
	mysql_select_db($dbname);
	$user2 = mysql_query("SELECT * FROM user WHERE firstname = '" . $_POST['username'] . "'");
	if (!$user2)
		error(mysql_error());
	//Gives error if user dosen't exist
	$number = mysql_num_rows($user2);
	if ($number == 0) {
		die('That user does not exist in our database.');
	}
	while ($userinfo = mysql_fetch_array($user2)) {
		//gives error if the password is wrong
		if ($_POST['password'] != $userinfo['password']) {
			die('Incorrect password, please try again.');
		} else {
			// if login is ok then we add a cookie
			$_POST['username'] = stripslashes($_POST['username']);
			$twohours = time() + 7200;
			setcookie('user', $_POST['username'], $twohours);
			setcookie('pass', $_POST['password'], $twohours);
			//then redirect them
			$_SESSION['unlocked'] = $userinfo['unlocked'];
			if ((bool) $_SESSION['unlocked']) {
				header("Location: index.php");
			} else {
				header("Location: unlock.php");
			}
			return;
		}
	}
	mysql_free_result($user2);
	mysql_close($db_link2);
} else {
	// if they are not logged in
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>CLIF: Login</title>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet"></link>
<script type="text/javascript" src="http://code.jquery.com/jquery-latest.js"></script>
<script type="text/javascript" src="js/bootstrap.min.js"></script>
</head>
<body>
	<div class="container">
		<div style="height: 10px"></div>
		<h1>CLIF: Login</h1>
		<p>
			Please sign in. If you would like to have a user account or encounter
			problems, please <a href="mailto:k.dentler@vu.nl">mail me</a>.
		</p>
		<div style="height: 15px"></div>
		<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
			<div style="margin-left: 50px; text-align: left;">
				User: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input
					type="text" class="small" name="username"></input><br />Password:
				<input type="password" class="small" name="password"></input> <br />
				<button type="submit" class="btn small primary" name="submit"
					value="submit">login</button>
			</div>
		</form>
	</div>
</body>
</html>
	<?php } ?>