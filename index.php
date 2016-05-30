<?php
require_once 'inc/util.inc';
session_start();
header('Content-Type: text/html; charset=UTF-8');
$_SESSION['error'] = "";
if (isset($_COOKIE['user'])) {
	$username = $_COOKIE['user'];
	$password = $_COOKIE['pass'];
	$db_link = mysql_connect($db, $db_user, $db_pass, TRUE, MYSQL_CLIENT_INTERACTIVE);
	if (!$db_link)
		error(mysql_error());
	mysql_query("SET NAMES utf8", $db_link);
	mysql_query("SET CHARACTER SET utf8", $db_link);
	mysql_query("SET SESSION interactive_timeout=30", $db_link);
	mysql_select_db($dbname);
	$user = mysql_query("SELECT * FROM user WHERE firstname = '$username'");
	if (!$user)
		error(mysql_error());
	while ($userinfo = mysql_fetch_array($user)) {
		if ($password != $userinfo['password']) {
			header("Location: login.php");
			return;
		} else {
			$_SESSION['unlocked'] = $userinfo['unlocked'];
			if (!(bool) $_SESSION['unlocked']) {
				header("Location: unlock.php");
				return;
			}
			$step = "index";
			$userid = $userinfo['id'];
			if (isset($_POST['indicatorSelect'])) {
				$_SESSION['indicatorid'] = $_POST['indicatorSelect'];
			}
			if (!isset($_SESSION['indicatorid'])) {
				$_SESSION['indicatorid'] = 1;
			}
			$commentresult = mysql_query("SELECT * FROM comment WHERE userid = '$userid' AND step = '$step'");
			if (!$commentresult)
				error(mysql_error());
			$comment_num_rows = mysql_num_rows($commentresult);
			while ($commentinfo = mysql_fetch_array($commentresult)) {
				$comment = $commentinfo['comment'];
				$commentold = $commentinfo['comment'];
			}
			mysql_free_result($commentresult);
			if ($_POST['submitComment'] == "submit") {
				$commentnew = $_POST['comment'];
				$commentnewprep = PrepSQL($commentnew);
				if ($commentnewprep != $commentcomment) {
					if ($comment_num_rows == 0) {
						$sql = "INSERT INTO comment (userid, indicatorid, step, comment, inserted) VALUES (
						$userid, 'NULL', '$step', '$commentnewprep', NOW())";
						mysql_query($sql) or die(mysql_error());
						$comment = $commentnewprep;
					} else if ($comment_num_rows > 0) {
						if (strcmp($commentnewprep, $commentold) != 0) {
							$sqlupdate = "UPDATE comment SET comment = '$commentnewprep', `updated` = NOW() WHERE userid = '$userid' AND step = '$step'";
							mysql_query($sqlupdate);
							$comment = $commentnewprep;
						}
					}
				}
				$commentresult = mysql_query("SELECT * FROM comment WHERE userid = '$userid' AND step = '$step'");
				if (!$commentresult)
					error(mysql_error());
				while ($commentinfo = mysql_fetch_array($commentresult)) {
					$comment = $commentinfo['comment'];
				}
				mysql_free_result($commentresult);
			}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>CLIF: Clinical Quality Indicator Formalisation</title>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet"></link>
<script type="text/javascript" src="http://code.jquery.com/jquery-latest.js"></script>
<script type="text/javascript" src="bootstrap/js/bootstrap.min.js"></script>
</head>
<body>
	<?php include_once("inc/head.inc") ?>
	<div style="height: 50px"></div>
	 <div class="container">
		<h1>CLIF: Clinical Quality Indicator Formalisation</h1>
		<?php include("inc/indicator.inc") ?>
		<p>The formalisation method consists of several steps:</p>
		<ol>
		<li><a href="concepts.php">Encode relevant concepts from the indicator
					by concepts from a terminology</a>
			</li>
			<li><a href="informationmodel.php">Define the information model</a>
			</li>
			<li><a href="temporal.php">Formalise temporal constraints</a>
			</li>
			<li><a href="numeric.php">Formalise numeric constraints</a>
			</li>
			<li><a href="textual.php">Formalise textual constraints</a>
			</li>
			<li><a href="boolean.php">Formalise boolean constraints</a>
			</li>
			<li><a href="exclusion.php">Identify exclusion criteria</a>
			</li>
			<li><a href="numerator.php">Identify constraints that only aim at the
					numerator</a>
			</li>
		</ol>
			<p>On the <a href="query.php">query
				page</a>, you can run the query. If you run the query before having started
			with the formalisation, you will select all patients, and then
			(possibly) less for each formalised constraint that you add. It is
			advisable to formalise the indicator in little steps and run the
			query in between. For example, first query for all patients with the
			main diagnosis or procedure that is mentioned in the indicator. If
			you retrieve zero patients, you could enable the subclass search or revise the SNOMED CT concepts you chose.
		</p>
		<p>
			In case that it is unclear how to formalise something, check the
			original version of the indicator, which is linked, ask a medical
			expert, call the responsible institution (IGZ or ZiZo), or <a
				href="mailto:k.dentler@vu.nl">mail me</a>. Also check the <a
				target="_blank" href="schema/tables/Patient.html">database schema</a>.
			<i>Please document all questions that you had, ambiguities and how
				you resolved them, i.e. why you modelled the indicator the way you
				did. Also, please give me as much feedback as possible of what you
				think of the method, what you found easy and what difficult and the
				reasons for it. This feedback is valuable for improving both this
				application and the indicators. </i>
		</p>
		<p>
		<?php if (!(bool) $_SESSION['unlocked']) {
				echo "Before you start, you need to <a href='unlock.php'>unlock</a> the steps.";
			} else {
				echo "Please start with <a
				href='concepts.php'>the concepts</a>.";
			}
		?>
		</p>
		<?php include_once("inc/comment.inc") ?>
	</div>
</body>
</html>
<?php
		}
	}
	mysql_free_result($user);
	mysql_close($db_link);
} else {
	header("Location: login.php");
	return;
}
?>