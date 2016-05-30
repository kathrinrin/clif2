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
			$step = "help";
			$userid = $userinfo['id'];
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
						$sql = "INSERT INTO comment (`userid`, `indicatorid`, `step`, `comment`, `inserted`) VALUES (
						$userid, 'NULL', '$step', '$commentnewprep', NOW())";
						mysql_query($sql);
						$comment = $commentnewprep;
					} else if ($comment_num_rows > 0) {
						if (strcmp($commentnew, $commentold) != 0) {
							$sqlupdate = "UPDATE comment SET `comment` = '$commentnewprep', `updated` = NOW() WHERE userid = '$userid' AND step = '$step'";
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
<title>CLIF: Help</title>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet"></link>
<script type="text/javascript" src="http://code.jquery.com/jquery-latest.js"></script>
<script type="text/javascript" src="js/bootstrap.min.js"></script>
</head>
<body>
	<?php include_once("inc/head.inc") ?>
	<div style="height: 50px"></div>
	 <div class="container">
		<h1>CLIF: Help</h1>
		<p>Clinical quality indicators are tools to "measure" the quality of
			delivered care in order to monitor and improve it, or to help
			patients to make informed choices. They can be related to structure,
			process or outcome. Process indicators are ideally evidence based and
			derived from guidelines.</p>
		<p>Quality indicators are released in natural language, which is
			inherently ambiguous. If several hospitals interpret the same quality
			indicator, it might happen that the interpretations differ. Results
			that are based on different interpretations are less comparable and
			valid. A second problem is that more and more (obligatory) indicators
			are released, making their manual calculation too expensive. So
			ideally, indicators should be released in an unambiguous,
			machine-processable, sharable, standard representation and computed
			automatically.</p>
		<p>An indicator can be regarded as a query that retrieves patients who
			fulfil constraints.</p>
		<p>
			This application helps you to apply my method to formalise quality
			indicators. The method (described in detail in <a href="kr4hc11.pdf">this
				paper</a>) consists of several steps:
		</p>
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
		<p>The blue areas on the pages is where the actual work is to be done.
			Your actions that concern select-menus and checkboxes are saved
			automatically, for text-boxes you have to save your content by
			hitting enter or the "save changes" button. Comments have to be saved
			by hitting the "save changes" button right below.</p>
		<p>When inserting concepts and constraints, you are asked to insert
			the corresponding piece of the indicator text. This information can
			be used later on. Whenever something is not completely clear, you
			decided for one out of several options or you encountered any
			problems, please make use of the comment boxes that you find on every
			page. This feedback is valuable for improving both this application
			and the indicators.</p>
		<p>
			<b>Final hint:</b> Time spent looking at the <a target="_blank"
				href="schema/tables/Patient.html">database schema</a> is always time
			spent well.
		</p>
		<p>
			If you have any questions or problems, please <a
				href="mailto:k.dentler@vu.nl">mail me</a>.
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