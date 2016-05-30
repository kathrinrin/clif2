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
			$step = "numerator";
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
				if ($commentnewprep != $commentcomment) {
					if ($comment_num_rows == 0) {
						$sql = "INSERT INTO comment (userid, indicatorid, step, comment, inserted) VALUES (
						$userid, $indicatorid, '$step', '$commentnewprep', NOW())";
						mysql_query($sql);
						$comment = $commentnewprep;
					} else if ($comment_num_rows > 0) {
						if (strcmp($commentnew, $commentold) != 0) {
							$sqlupdate = "UPDATE comment SET `comment` = '$commentnewprep', `updated` = NOW() WHERE `userid` = '$userid' AND `indicatorid` = '$indicatorid' AND `step` = '$step'";
							mysql_query($sqlupdate);
							$comment = $commentnewprep;
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
			if ($_POST['form'] == "numeratorconstraints") {
				$allconstraints = mysql_query(
						"SELECT * FROM formalised_constraint WHERE indicatorid = '$indicatorid' AND userid = '$userid'");
				if (!$allconstraints)
					error(mysql_error());
				while ($constraintinfo = mysql_fetch_array($allconstraints)) {
					$id = $constraintinfo['id'];
					$sqlupdate = "UPDATE formalised_constraint SET numeratoronly = '0', updated = NOW() WHERE id = '$id'";
					mysql_query($sqlupdate);
				}
				mysql_free_result($allconstraints);
				$selconstraints = $_POST['constraints'];
				if (!empty($selconstraints)) {
					$N = count($selconstraints);
					for ($i = 0; $i < $N; $i++) {
						$sqlupdate = "UPDATE formalised_constraint SET numeratoronly = '1' WHERE id = '$selconstraints[$i]'";
						mysql_query($sqlupdate);
					}
				}
			}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>CLIF: Numerator</title>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet"></link>
<script type="text/javascript" src="http://code.jquery.com/jquery-latest.js"></script>
<script type="text/javascript" src="js/bootstrap.min.js"></script>
</head>
<body>
	<?php include_once("inc/head.inc") ?>
	<div style="height: 50px"></div>
	 <div class="container">
		<h1>Numerator: Identify constraints that only aim at the numerator</h1>
		<p>For the constraints that you formalised, state which ones only aim
			at the numerator. Also select the constraints of the information
			model that only aim at those constraints. All constraints that you do
			not select belong only to the denominator. A constraint should not be
			both an exclusion criterion and only aim at the numerator.</p>
			<?php include_once("inc/indicator.inc") ?>
		<h2>Select constraints that only aim at the numerator</h2>
		<div class="workwell">
			<div style="margin-left: 10px;">
				<a name="constraints"></a>
				<form action="<?php echo $_SERVER['PHP_SELF'] . '#constraints' ?>"
					method="post">
					<h3>
						 <a href="informationmodel.php">Information Model</a>
					</h3>
					<?php
			$informationmodelconstraint = mysql_query(
					"SELECT * FROM formalised_constraint WHERE indicatorid = '$indicatorid' AND userid = '$userid' AND constrainttype = 'informationmodel'");
			if (!$informationmodelconstraint)
				error(mysql_error());
			while ($informationmodelconstraintrow = mysql_fetch_array($informationmodelconstraint)) {
				$id = $informationmodelconstraintrow['id'];
				$table = $informationmodelconstraintrow['table'];
				$attribute = $informationmodelconstraintrow['attribute'];
				$relation = $informationmodelconstraintrow['relation'];
				$conceptid = $informationmodelconstraintrow['conceptid'];
				$numeratoronly = $informationmodelconstraintrow['numeratoronly'];
				$color = "black";
				if ((bool) $numeratoronly)
					$color = "green";
				$fsn = mysql_query("SELECT FULLYSPECIFIEDNAME FROM concepts WHERE CONCEPTID = '$conceptid' ");
				if (!$fsn)
					error(mysql_error());
				$snorow = mysql_fetch_row($fsn);
				echo "<input type='checkbox' name='constraints[]' value='$id' onchange='this.form.submit();'";
				if ((bool) $numeratoronly) {
					echo " checked = 'checked' ";
				}
				echo " />&nbsp;<span style='color:$color' >";
				echo "$table.$attribute&nbsp;$relation&nbsp;$conceptid&nbsp;$snorow[0]";
				echo "</span><br />";
			}
			mysql_free_result($informationmodelconstraint);
					?>
					<h3>
						 <a href="temporal.php">Temporal constraints</a>
					</h3>
					<?php
			$dateconstraint = mysql_query(
					"SELECT * FROM formalised_constraint WHERE indicatorid = '$indicatorid' AND userid = '$userid' AND constrainttype = 'temporal_date'");
			if (!$dateconstraint)
				error(mysql_error());
			while ($dateconstraintrow = mysql_fetch_array($dateconstraint)) {
				$id = $dateconstraintrow['id'];
				$table = $dateconstraintrow['table'];
				$attribute = $dateconstraintrow['attribute'];
				$relation = $dateconstraintrow['relation'];
				$date = $dateconstraintrow['date'];
				$numeratoronly = $dateconstraintrow['numeratoronly'];
				$color = "black";
				if ((bool) $numeratoronly)
					$color = "green";
				echo "<input type='checkbox' name='constraints[]' value='$id' onchange='this.form.submit();'";
				if ((bool) $numeratoronly) {
					echo " checked = 'checked' ";
				}
				echo " />&nbsp;<span style='color:$color' >";
				echo "$table.$attribute&nbsp;$relation&nbsp;$date";
				echo "</span><br />";
			}
			mysql_free_result($dateconstraint);
			$daterelationconstraint = mysql_query(
					"SELECT * FROM formalised_constraint WHERE indicatorid = '$indicatorid' AND userid = '$userid' AND constrainttype = 'temporal_relation'");
			if (!$daterelationconstraint)
				error(mysql_error());
			while ($daterelationconstraintrow = mysql_fetch_array($daterelationconstraint)) {
				$id = $daterelationconstraintrow['id'];
				$table = $daterelationconstraintrow['table'];
				$attribute = $daterelationconstraintrow['attribute'];
				$relation = $daterelationconstraintrow['relation'];
				$table2 = $daterelationconstraintrow['table2'];
				$attribute2 = $daterelationconstraintrow['attribute2'];
				$numeratoronly = $daterelationconstraintrow['numeratoronly'];
				$color = "black";
				if ((bool) $numeratoronly)
					$color = "green";
				echo "<input type='checkbox' name='constraints[]' value='$id' onchange='this.form.submit();'";
				if ((bool) $numeratoronly) {
					echo " checked = 'checked' ";
				}
				echo " />&nbsp;<span style='color:$color' >";
				echo "$table.$attribute&nbsp;$relation&nbsp;$table2.$attribute2";
				echo "</span><br />";
			}
			mysql_free_result($daterelationconstraint);
					?>
					<h3>
						 <a href="numeric.php">Numeric constraints</a>
					</h3>
					<?php
			$numericconstraint = mysql_query(
					"SELECT * FROM formalised_constraint WHERE indicatorid = '$indicatorid' AND userid = '$userid' AND constrainttype = 'numeric'");
			if (!$numericconstraint)
				error(mysql_error());
			while ($numericconstraintrow = mysql_fetch_array($numericconstraint)) {
				$id = $numericconstraintrow['id'];
				$table = $numericconstraintrow['table'];
				$attribute = $numericconstraintrow['attribute'];
				$relation = $numericconstraintrow['relation'];
				$number = $numericconstraintrow['number'];
				$numeratoronly = $numericconstraintrow['numeratoronly'];
				$color = "black";
				if ((bool) $numeratoronly)
					$color = "green";
				echo "<input type='checkbox' name='constraints[]' value='$id' onchange='this.form.submit();'";
				if ((bool) $numeratoronly) {
					echo " checked = 'checked' ";
				}
				echo " />&nbsp;<span style='color:$color' >";
				echo "$table.$attribute&nbsp;$relation&nbsp;$number";
				echo "</span><br />";
			}
			mysql_free_result($numericconstraint);
					?>
					
					<h3>
						 <a href="textual.php">Textual constraints</a>
					</h3>
					<?php
			$textualconstraint = mysql_query(
					"SELECT * FROM formalised_constraint WHERE indicatorid = '$indicatorid' AND userid = '$userid' AND constrainttype = 'textual'");
			if (!$textualconstraint)
				error(mysql_error());
			while ($textualconstraintrow = mysql_fetch_array($textualconstraint)) {
				$id = $textualconstraintrow['id'];
				$table = $textualconstraintrow['table'];
				$attribute = $textualconstraintrow['attribute'];
				$relation = $textualconstraintrow['relation'];
				$txt = $textualconstraintrow['txt'];
				$numeratoronly = $textualconstraintrow['numeratoronly'];
				$color = "black";
				if ((bool) $numeratoronly)
					$color = "green";
				echo "<input type='checkbox' name='constraints[]' value='$id' onchange='this.form.submit();'";
				if ((bool) $numeratoronly) {
					echo " checked = 'checked' ";
				}
				echo " />&nbsp;<span style='color:$color' >";
				echo "$table.$attribute&nbsp;$relation&nbsp;$txt";
				echo "</span><br />";
			}
			mysql_free_result($textualconstraint);
					?>
					
					
					<h3>
						<a href="boolean.php">Boolean constraints</a>
					</h3>
					<?php
			$booleanconstraint = mysql_query(
					"SELECT * FROM formalised_constraint WHERE indicatorid = '$indicatorid' AND userid = '$userid' AND constrainttype = 'boolean'");
			if (!$booleanconstraint)
				error(mysql_error());
			while ($booleanconstraintrow = mysql_fetch_array($booleanconstraint)) {
				$id = $booleanconstraintrow['id'];
				$table = $booleanconstraintrow['table'];
				$attribute = $booleanconstraintrow['attribute'];
				$boolean = $booleanconstraintrow['boolean'];
				$numeratoronly = $booleanconstraintrow['numeratoronly'];
				$color = "black";
				if ((bool) $numeratoronly)
					$color = "green";
				echo "<input type='checkbox' name='constraints[]' value='$id' onchange='this.form.submit();'";
				if ((bool) $numeratoronly) {
					echo " checked = 'checked' ";
				}
				echo " />&nbsp;<span style='color:$color' >";
				echo "$table.$attribute&nbsp;=&nbsp;$boolean";
				echo "</span><br />";
			}
			mysql_free_result($booleanconstraint);
					?>
					<input type="hidden" name="form" value="numeratorconstraints" />
				</form>
			</div>
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