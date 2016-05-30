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
			$_SESSION['unlocked'] = $userinfo['unlocked'];
			if (!(bool) $_SESSION['unlocked']) {
				header("Location: unlock.php");
				return;
			}
			if (isset($_POST['indicatorSelect'])) {
				$_SESSION['indicatorid'] = $_POST['indicatorSelect'];
			}
			if (!isset($_SESSION['indicatorid'])) {
				$_SESSION['indicatorid'] = 1;
			}
			$indicatorid = $_SESSION['indicatorid'];
			$step = "concepts";
			$userid = $userinfo['id'];
			$commentresult = mysql_query(
					"SELECT * FROM comment WHERE userid = '$userid' AND indicatorid = '$indicatorid' AND step = '$step'");
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
				$comment = $commentnewprep;
				if ($commentnewprep != $commentcomment) {
					if ($comment_num_rows == 0) {
						$sql = "INSERT INTO comment (userid, indicatorid, step, comment, inserted) VALUES (
						$userid, $indicatorid, '$step', '$commentnewprep', NOW())";
						mysql_query($sql);
					} else if ($comment_num_rows > 0) {
						if (strcmp($commentnew, $commentold) != 0) {
							$sqlupdate = "UPDATE comment SET `comment` = '$commentnewprep', `updated` = NOW() WHERE `userid` = '$userid' AND `indicatorid` = '$indicatorid' AND `step` = '$step'";
							mysql_query($sqlupdate);
						}
					}
				}
				$commentresult = mysql_query(
						"SELECT * FROM comment WHERE userid = '$userid' AND indicatorid = '$indicatorid' AND step = '$step'");
				if (!$commentresult)
					error(mysql_error());
				while ($commentinfo = mysql_fetch_array($commentresult)) {
					$comment = $commentinfo['comment'];
				}
				mysql_free_result($commentresult);
			}
			if ($_POST['submitConcept'] == "submit") {
				$textConcept = $_POST['textConcept'];
				$textSNOMED = $_POST['textSNOMED'];
				if (empty($textConcept) && empty($textSNOMED)) {
					$_SESSION['error'] = "Nothing saved: both fields empty";
				} else if (empty($textConcept) || empty($textSNOMED)) {
					$_SESSION['error'] = "Nothing saved: one of the fields is empty.";
				} else if (!empty($textSNOMED)) {

					// if snomed is to be used, these comments need to be uncommented!

					// $fsnresult = mysql_query("SELECT CONCEPTSTATUS FROM `$snomeddbname`.concepts WHERE CONCEPTID = '$textSNOMED' ");
					// if (!$fsnresult) error(mysql_error());
					// $num_rows = mysql_num_rows($fsnresult);
					// if ($num_rows == 0) {
					// $_SESSION['error']= "Nothing saved: concept not valid ($textSNOMED)";
					// }
					//if ($num_rows > 0) {
					//$snorow = mysql_fetch_row($fsnresult);
					//$isprocedure= mysql_query("SELECT COUNT(*) FROM `$snomeddbname`.`sct_transitiveclosure` WHERE `SubtypeId` = $textSNOMED AND `SupertypeId` = 71388002");
					//if (!$isprocedure) error(mysql_error());
					//$procedurerow = mysql_fetch_row($isprocedure);
					//$isfinding= mysql_query("SELECT COUNT(*) FROM `$snomeddbname`.`sct_transitiveclosure` WHERE `SubtypeId` = $textSNOMED AND `SupertypeId` = 404684003");
					//if (!$isfinding) error(mysql_error());
					//$findingrow = mysql_fetch_row($isfinding);
					//mysql_select_db($dbname);
					//	if ($snorow[0] != 0) {
					//$_SESSION['error']= "Nothing saved: concept not active ($textSNOMED). ";
					//}
					//if (! ((strcmp($procedurerow[0], "1") == 0) || (strcmp($findingrow[0], "1") == 0))) {
					//$_SESSION['error']= "Nothing saved: concept is neither a procedure nor a finding ($textSNOMED). ";
					//}
					//	else {
					$indicatortext = PrepSQL($textConcept);
					$numquery = "SELECT * FROM concept WHERE userid = '$userid' AND indicatorid = '$indicatorid' AND indicatortext = '$indicatortext' AND conceptid = '$textSNOMED'";
					$result = mysql_query($numquery);
					if (!$result)
						error(mysql_error());
					$num_rows = mysql_num_rows($result);
					mysql_free_result($result);
					if ($num_rows > 0) {
						$_SESSION['error'] = "Nothing saved: same row already in database. ";
					} else {
						$sql = "INSERT INTO concept (userid, indicatorid, indicatortext, conceptid, inserted) VALUES (
								$userid,
								$indicatorid,
								'$indicatortext',
								'$textSNOMED',
								NOW())";
						mysql_query($sql);
						$textConcept = "";
						$textSNOMED = "";
					}
					//}
					//}
					mysql_free_result($fsnresult);
				}
			}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>CLIF: Concepts</title>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet"></link>
<script type="text/javascript" src="http://code.jquery.com/jquery-latest.js"></script>
<script type="text/javascript" src="js/bootstrap.min.js"></script>
</head>
<body>
	<?php include_once("inc/head.inc") ?>
	<div style="height: 50px"></div>
	 <div class="container">
		<h1>Encode relevant concepts from the indicator by concepts
			from a terminology</h1>
		<p>
			Please scan through the text and search for relevant diagnoses and
			procedures that occur in the <i>numerator</i> and in the <i>in- and
				exclusion criteria</i> of the indicator. Then, for each concept
			(i.e. diagnosis or procedure) that you found, find the corresponding
			SNOMED CT Concept ID in the finding/disorder or procedure hierarchy.
			For this, you can use any SNOMED CT browser, such as <a
				href="http://terminologie.nictiz.nl/" target="_blank">the one of
				Nictiz</a>, <a
				href="http://www.medicalclassifications.com/SNOMEDbrowser/"
				target="_blank">The SNOMED CT Browser</a> or the <a
				href="http://vtsl.vetmed.vt.edu/" target="_blank">VTSL Terminology
				Browser</a>. In case that you find those browsers too difficult to
			use, you can also download and install a browser on your machine,
			such as <a href="http://www.cliniclue.com/" target="_blank">CliniClue&reg; Xplore</a>,
			<a href="http://snob.eggbird.eu/" target="_blank">SNOB</a> or <a
				href="http://www.b2international.com/portal/snow-owl" target="_blank">Snow Owl</a>
			(you have to request a licence). Only insert the Concept ID, the
			corresponding fully specified name is filled in automatically. In
			case you are doubting which concept to choose within a hierarchy, as
			a rule of thumb it is advisable to choose the higher one, as it
			includes the lower one (but it does not make sense to go too high up
			in the hierarchy).
		</p>
		<?php include_once("inc/indicator.inc") ?>
		<div class="workwell">
			<a name="concepts"></a>
			<form action="<?php echo $_SERVER['PHP_SELF'] . '#concepts' ?>"
				method="post">
				<table class="table table-condensed">
					<tr>
						<th>Indicator text (copy the relevant piece of the indicator)</th>
						<th>Concept ID</th>
						<th>delete</th>
					</tr>
					<?php
			$content = mysql_query("SELECT * FROM concept WHERE userid = '$userid' AND indicatorid = '$indicatorid' ");
			if (!$content)
				error(mysql_error());
			while ($row = mysql_fetch_array($content)) {
				$id = $row['id'];
				$conceptid = $row['conceptid'];
				$fsn = mysql_query("SELECT Term FROM `$snomeddbname`.sct2_description WHERE CONCEPTID = '$conceptid' ");
				if (!$fsn)
				 	error(mysql_error());
				$snorow = mysql_fetch_row($fsn);
				echo "<tr>";
				echo "<td>" . $row['indicatortext'] . "</td>";
				echo "<td>" . $row['conceptid'] . " " . $snorow[0] . "</td>";
				echo "<td><a href='deleteconcept.php?id=$id'>x</a></td>";
				echo "</tr>\n";
			}
			if (is_resource($fsn))
				mysql_free_result($fsn);
					?>
					<tr valign="top">
						<td><input type="text" name="textConcept" maxlength="100"
							value="<?php echo $textConcept; ?>" />
						</td>
						<td><input type="text" name="textSNOMED" maxlength="50"
							value="<?php echo $textSNOMED; ?>" />
						</td>
						<td></td>
					</tr>
				</table>
				<button type="submit" class="btn small primary" name="submitConcept"
					value="submit">save changes</button>
				<span style="color: red"> <?php echo $_SESSION['error']; ?>
				</span>
			</form>
		</div>
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