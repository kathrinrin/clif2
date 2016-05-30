<?php
// require_once 'calendar/classes/tc_calendar.php';
require_once 'inc/util.inc';
session_start();
$_SESSION['error'] = "";
$_SESSION['yearerror'] = "";
$_SESSION['errorrelation'] = "";
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
			$step = "temporal";
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
						$userid,
						$indicatorid,
						'$step', '$commentnewprep', NOW())";
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
			if (isset($_POST['tableattributeYearSelect']) || isset($_POST['yearSelect'])) {
				$selectedtableyearattribute = $_POST['tableattributeYearSelect'];
				$year = $_POST['yearSelect'];
				$beginyear = $year . "-01-01";
				$endyear = $year . "-12-31";
				// all empty
				if (empty($selectedtableyearattribute) && empty($year)) {
					$_SESSION['yearerror'] = "Nothing saved: all fields empty";
				} else if (empty($selectedtableyearattribute) || empty($year)) {
					$_SESSION['yearerror'] = "Nothing saved: one or more empty fields";
				} else {
					$pos = strpos($selectedtableyearattribute, ".");
					$table = substr($selectedtableyearattribute, 0, $pos);
					$attribute = substr($selectedtableyearattribute, ($pos + 1), strlen($selectedtableyearattribute));
					$numquery = "SELECT * FROM formalised_constraint WHERE `userid` = '$userid' AND `indicatorid` = '$indicatorid' AND `constrainttype` = 'temporal_date' AND `table` = '$table' AND `attribute` = '$attribute' AND `indicatortext` = 'reporting year' AND `relation` = 'greater-than-or-equal-to' AND `date` = '$beginyear'";
					$result = mysql_query($numquery);
					if (!$result)
						error(mysql_error());
					$num_rows = mysql_num_rows($result);
					mysql_free_result($result);
					$numquery = "SELECT * FROM formalised_constraint WHERE `userid` = '$userid' AND `indicatorid` = '$indicatorid' AND `constrainttype` = 'temporal_date' AND `table` = '$table' AND `attribute` = '$attribute' AND `indicatortext` = 'reporting year' AND `relation` = 'less-than-or-equal-to' AND `date` = '$endyear'";
					$endresult = mysql_query($numquery);
					if (!$endresult)
						error(mysql_error());
					$end_num_rows = mysql_num_rows($endresult);
					mysql_free_result($endresult);
					if (($num_rows > 0) || ($end_num_rows > 0)) {
						$_SESSION['yearerror'] = "Nothing saved: same row already in database. ";
					} else {
						$sql = "INSERT INTO formalised_constraint (`id`, `userid`, `indicatorid`, `constrainttype`, `indicatortext`, `table`, `attribute`, `conceptid`, `relation`, `date`, `table2`, `attribute2`, `number`, `isexclusion`, `numeratoronly`, `inserted`) VALUES (
						NULL,
						$userid,
						$indicatorid,
						'temporal_date',
						'reporting year',
						'$table',
						'$attribute',
						NULL, 'greater-than-or-equal-to', '$beginyear', NULL, NULL, NULL, NULL, NULL, NOW())";
						mysql_query($sql);
						$sql = "INSERT INTO formalised_constraint (`id`, `userid`, `indicatorid`, `constrainttype`, `indicatortext`, `table`, `attribute`, `conceptid`, `relation`, `date`, `table2`, `attribute2`, `number`, `isexclusion`, `numeratoronly`, `inserted`) VALUES (
						NULL,
						$userid,
						$indicatorid,
						'temporal_date',
						'reporting year',
						'$table',
						'$attribute',
						NULL, 'less-than-or-equal-to', '$endyear', NULL, NULL, NULL, NULL, NULL, NOW())";
						mysql_query($sql);
						$selectedtableyearattribute = "";
						$year = "";
						$beginyear = "";
						$endyear = "";
					}
				}
			}
			if (($_POST['submitDate'] == "submit") || isset($_POST['tableattributeSelect'])
					|| isset($_POST['relationSelect'])) {
				$selectedtableattribute = $_POST['tableattributeSelect'];
				$relation = $_POST['relationSelect'];
				$theDate = $_POST['date5'];
				$indicatorText = $_POST['indicatorText'];
				$indicatortextprep = PrepSQL($indicatorText);
				// all empty
				if (empty($selectedtableattribute) && empty($relation) && empty($theDate) && empty($indicatortextprep)) {
					$_SESSION['error'] = "Nothing saved: all fields empty";
				} else if (empty($selectedtableattribute) || empty($relation) || (empty($theDate))
						|| empty($indicatortextprep)) {
					$_SESSION['error'] = "Nothing saved: one or more empty fields";
				} else {
					$pos = strpos($selectedtableattribute, ".");
					$table = substr($selectedtableattribute, 0, $pos);
					$attribute = substr($selectedtableattribute, ($pos + 1), strlen($selectedtableattribute));
					$numquery = "SELECT * FROM formalised_constraint WHERE `userid` = '$userid' AND `indicatorid` = '$indicatorid' AND `constrainttype` = 'temporal_date' AND `table` = '$table' AND `attribute` = '$attribute' AND `indicatortext` = '$indicatortextprep' AND `relation` = '$relation' AND `date` = '$theDate'";
					$result = mysql_query($numquery);
					if (!$result)
						error(mysql_error());
					$num_rows = mysql_num_rows($result);
					mysql_free_result($result);
					if ($num_rows > 0) {
						$_SESSION['error'] = "Nothing saved: same row already in database. ";
					} else {
						$sql = "INSERT INTO formalised_constraint (`id`, `userid`, `indicatorid`, `constrainttype`, `indicatortext`, `table`, `attribute`, `conceptid`, `relation`, `date`, `table2`, `attribute2`, `number`, `isexclusion`, `numeratoronly`, `inserted`) VALUES (
						NULL,
						$userid,
						$indicatorid,
						'temporal_date',
						'$indicatortextprep',
						'$table',
						'$attribute',
						NULL, '$relation', '$theDate', NULL, NULL, NULL, NULL, NULL, NOW())";
						mysql_query($sql);
						$selectedtableattribute = "";
						$relation = "";
						$theDate = "";
						$indicatorText = "";
					}
				}
			}
			if (($_POST['submitRelation'] == "submit") || isset($_POST['tableattribute1Select'])
					|| isset($_POST['relation1Select']) || isset($_POST['tableattribute2Select'])) {
				$selectedtableattribute1 = $_POST['tableattribute1Select'];
				$relation1 = $_POST['relation1Select'];
				$selectedtableattribute2 = $_POST['tableattribute2Select'];
				$indicatorText = $_POST['indicatorText'];
				$indicatortextprep = PrepSQL($indicatorText);
				// all empty
				if (empty($selectedtableattribute1) && empty($relation1) && empty($selectedtableattribute2)
						&& empty($indicatortextprep)) {
					$_SESSION['errorrelation'] = "Nothing saved: all fields empty";
				} else if (empty($selectedtableattribute1) || empty($relation1)
						|| (empty($selectedtableattribute2) || (strcmp($theDate, "0000-00-00") == 0))
						|| empty($indicatortextprep)) {
					$_SESSION['errorrelation'] = "Nothing saved: one or more empty fields";
				} else {
					$pos = strpos($selectedtableattribute1, ".");
					$table1 = substr($selectedtableattribute1, 0, $pos);
					$attribute1 = substr($selectedtableattribute1, ($pos + 1), strlen($selectedtableattribute1));
					$pos = strpos($selectedtableattribute2, ".");
					$table2 = substr($selectedtableattribute2, 0, $pos);
					$attribute2 = substr($selectedtableattribute2, ($pos + 1), strlen($selectedtableattribute2));
					$numquery = "SELECT * FROM formalised_constraint WHERE `userid` = '$userid' AND `indicatorid` = '$indicatorid' AND `constrainttype` = 'temporal_relation' AND `table` = '$table1' AND `attribute` = '$attribute1' AND `indicatortext` = '$indicatortextprep' AND `relation` = '$relation1' AND `table2` = '$table2' AND `attribute2` = '$attribute2'";
					$result = mysql_query($numquery);
					if (!$result)
						error(mysql_error());
					$num_rows = mysql_num_rows($result);
					mysql_free_result($result);
					if ($num_rows > 0) {
						$_SESSION['errorrelation'] = "Nothing saved: same row already in database. ";
					} else {
						$sql = "INSERT INTO formalised_constraint (`id`, `userid`, `indicatorid`, `constrainttype`, `indicatortext`, `table`, `attribute`, `conceptid`, `relation`, `date`, `table2`, `attribute2`, `number`, `isexclusion`, `numeratoronly`, `inserted`) VALUES (
						NULL, $userid,
						$indicatorid,
						'temporal_relation',
						'$indicatortextprep',
						'$table1', '$attribute1', NULL, '$relation1', NULL, '$table2', '$attribute2', NULL, NULL, NULL, NOW())";
						mysql_query($sql);
						$selectedtableattribute1 = "";
						$selectedtableattribute2 = "";
						$relation1 = "";
						$indicatorText = "";
					}
				}
			}

			if ($_POST['form'] == "lastvalue") {
				$allconstraints = mysql_query(
						"SELECT * FROM formalised_constraint WHERE indicatorid = '$indicatorid' AND userid = '$userid'");
				if (!$allconstraints)
					error(mysql_error());
				while ($constraintinfo = mysql_fetch_array($allconstraints)) {
					$id = $constraintinfo['id'];
					$sqlupdate = "UPDATE formalised_constraint SET lastvalue = '0' WHERE id = '$id'";
					mysql_query($sqlupdate);
				}
				mysql_free_result($allconstraints);
				$selconstraints = $_POST['constraints'];
				if (!empty($selconstraints)) {
					$N = count($selconstraints);
					for ($i = 0; $i < $N; $i++) {
						$sqlupdate = "UPDATE formalised_constraint SET `lastvalue` = '1', `updated` = NOW() WHERE id = '$selconstraints[$i]'";
						mysql_query($sqlupdate);
					}
				}
			}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>CLIF: Temporal Constraints</title>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet"></link>
<script type="text/javascript" src="http://code.jquery.com/jquery-latest.js"></script>
<script type="text/javascript" src="js/bootstrap.min.js"></script>
</head>
<body>
	<?php include_once("inc/head.inc") ?>
	<div style="height: 50px"></div>
	 <div class="container">
		<h1>Formalise temporal constraints and aggregations</h1>
		<p>
			Please scan through the text and search for all temporal constraints
			that occur in the <i>numerator</i> and in the <i>in- and exclusion
				criteria</i> of the indicator. There
			are two kinds of temporal constraints: those that compare an
			attribute with a certain date (<a
				href="<?php echo $_SERVER['PHP_SELF'] . '#reportingyear' ?>">substep 1</a>
			and <a href="<?php echo $_SERVER['PHP_SELF'] . '#temporaldate' ?>">substep
				2</a>), and those that compare two attributes with each other (<a
				href="<?php echo $_SERVER['PHP_SELF'] . '#temporalrelation' ?>">substep
				3</a>). Begin with the reporting year in <a
				href="<?php echo $_SERVER['PHP_SELF'] . '#reportingyear' ?>">substep 3</a>.
		</p>
		<?php include_once("inc/indicator.inc") ?>

		<a name="reportingyear"></a>
		<h2>1. Define the reporting year</h2>
		This box makes it easy for you to define the reporting year. It
		inserts two constraints that actually belong to <a
			href="<?php echo $_SERVER['PHP_SELF'] . '#temporaldate' ?>">step 3.2</a>
		and compare an attribute to a certain date. You need to insert the
		reporting year and the table attribute it refers to, which is
		typically the date of a certain procedure. The formalised constraints
		are displayed in the box that belongs to <a
			href="<?php echo $_SERVER['PHP_SELF'] . '#temporaldate' ?>">step 3.2</a>.
		The reporting year should refer to the population of the denominator.
		<div class="workwell">
			<form action="<?php echo $_SERVER['PHP_SELF'] . '#reportingyear' ?>"
				method="post" name="yearForm">
				<br />Reporting year: <select name="yearSelect"
					onchange="this.form.submit();">
					<option value="">please choose</option>
					<?php
			$yearnow = 2015;
			for ($currentyear = $yearnow - 10; $currentyear <= $yearnow; $currentyear++) {
				echo "<option value='$currentyear'";
				if ($year == $currentyear)
					echo " selected='selected' ";
				echo ">$currentyear</option>";
			}
					?></select> Refers to: <select name="tableattributeYearSelect"
					onchange="this.form.submit();">
					<option value="">please choose</option>
					<?php
			$variablesql = "SELECT * FROM `query_variable` WHERE `userid` = '$userid'  AND `indicatorid` = '$indicatorid' ORDER BY `variable`";
			$variables = mysql_query($variablesql);
			if (!$variables)
				error(mysql_error());
			mysql_select_db($patientsdbname);
			while ($variablerow = mysql_fetch_array($variables)) {
				$variableattributes = mysql_query("DESCRIBE `$variablerow[table]`");
				if (!$variableattributes)
					error(mysql_error());
				while ($variableattributerow = mysql_fetch_array($variableattributes)) {
					$datatype = mysql_query(
							"SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '$variablerow[table]' AND COLUMN_NAME = '$variableattributerow[0]'");

					if (!$datatype)
						error(mysql_error());
					$datatyperow = mysql_fetch_row($datatype);
					if (strcmp($datatyperow[0], "date") == 0) {
						$option = "$variablerow[variable].$variableattributerow[0]";
						echo "<option value='$option'";
						if (strcmp($selectedtableyearattribute, $option) == 0) {
							echo "selected='selected'";
						}
						;
						echo ">$option</option>";
					}
				}
				mysql_free_result($variableattributes);
			}
			mysql_free_result($variables);
			foreach ($patienttables as &$patienttable) {
				$attributes = mysql_query("DESCRIBE `$patienttable`");
				if (!$attributes)
					error(mysql_error());
				while ($attributerow = mysql_fetch_array($attributes)) {
					$datatype = mysql_query(
							"SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '$patienttable' AND COLUMN_NAME = '$attributerow[0]'");
					if (!$datatype)
						error(mysql_error());
					$datatyperow = mysql_fetch_row($datatype);
					if (strcmp($datatyperow[0], "date") == 0) {
						$option = "$patienttable.$attributerow[0]";
						echo "<option value='$option'";
						if (strcmp($selectedtableyearattribute, $option) == 0) {
							echo "selected='selected'";
						}
						;
						echo ">$option</option>";
					}
				}
			}
			mysql_free_result($attributes);
			mysql_select_db($dbname);
					?>
				</select> <span style="color: red"> <?php echo $_SESSION['yearerror']; ?>
				</span>
			</form>
		</div>
		<div style="height: 30px"></div>
		<a name="temporaldate"></a>
		<h2>2. Define temporal constraints that compare an attribute with a
			certain date.</h2>
		If you defined a query variable for a diagnosis or procedure, use this
		instead of the table name.
		<div class="workwell">
			<form action="<?php echo $_SERVER['PHP_SELF'] . '#temporaldate' ?>"
				method="post" name="dateForm">
				<table class="table table-condensed">
					<tr>
						<th style="width: 15%">Comment</th>
						<th style="width: 25%">Table.Date</th>
						<th style="width: 25%">Relation</th>
						<th style="width: 25%">Date (yyyy-mm-dd)</th>
						<th style="width: 10%">Delete</th>
					</tr>
					<?php
			$selectconstraints = "SELECT * FROM formalised_constraint WHERE userid = '$userid' AND indicatorid = '$indicatorid' AND constrainttype = 'temporal_date'";
			$content = mysql_query($selectconstraints);
			if (!$content)
				error(mysql_error());
			while ($row = mysql_fetch_array($content)) {
				$id = $row['id'];
				$table = $row['table'];
				$attribute = $row['attribute'];
				echo "<tr>";
				echo "<td>" . $row['indicatortext'] . "</td>";
				echo "<td>" . $table . "." . $attribute . "</td>";
				echo "<td>" . $row['relation'] . "</td>";
				echo "<td>" . $row['date'] . "</td>";
				echo "<td><a href='delete.php?id=$id&amp;step=$step&amp;anchor=temporaldate'>x</a></td>";
				echo "</tr>\n";
			}
			mysql_free_result($content);
					?>
					<tr valign="top">
							
						<td><input class="input-medium" type="text" name="indicatorText" maxlength="50"
							value="<?php echo $indicatorText; ?>" />
						</td>
						<td><select name="tableattributeSelect"
							onchange="this.form.submit();">
								<option value="">please choose</option>
								<?php
			$variablesql = "SELECT * FROM `query_variable` WHERE `userid` = '$userid'  AND `indicatorid` = '$indicatorid' ORDER BY `variable`";
			$variables = mysql_query($variablesql);
			if (!$variables)
				error(mysql_error());
			mysql_select_db($patientsdbname);
			while ($variablerow = mysql_fetch_array($variables)) {
				$variableattributes = mysql_query("DESCRIBE `$variablerow[table]`");
				if (!$variableattributes)
					error(mysql_error());
				while ($variableattributerow = mysql_fetch_array($variableattributes)) {
					$datatype = mysql_query(
							"SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '$variablerow[table]' AND COLUMN_NAME = '$variableattributerow[0]'");
					if (!$datatype)
						error(mysql_error());
					$datatyperow = mysql_fetch_row($datatype);
					if (strcmp($datatyperow[0], "date") == 0) {
						$option = "$variablerow[variable].$variableattributerow[0]";
						echo "<option value='$option'";
						if (strcmp($selectedtableattribute, $option) == 0) {
							echo "selected='selected'";
						}
						;
						echo ">$option</option>";
					}
				}
				mysql_free_result($variableattributes);
			}
			mysql_free_result($variables);
			foreach ($patienttables as &$patienttable) {
				$attributes = mysql_query("DESCRIBE `$patienttable`");
				if (!$attributes)
					error(mysql_error());
				while ($attributerow = mysql_fetch_array($attributes)) {
					$datatype = mysql_query(
							"SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '$patienttable' AND COLUMN_NAME = '$attributerow[0]'");
					if (!$datatype)
						error(mysql_error());
					$datatyperow = mysql_fetch_row($datatype);
					if (strcmp($datatyperow[0], "date") == 0) {
						$option = "$patienttable.$attributerow[0]";
						echo "<option value='$option'";
						if (strcmp($selectedtableattribute, $option) == 0) {
							echo "selected='selected'";
						}
						;
						echo ">$option</option>";
					}
				}
			}
			mysql_free_result($attributes);
			mysql_select_db($dbname);
								?>
						</select></td>
						<td><select name="relationSelect" onchange="this.form.submit();">
								<option value="">please choose</option>
								<option
								<? if ($relation == "less-than")
				echo (" selected='selected'");
								?>
									value="less-than">&lt;</option>
								<option
								<? if ($relation == "less-than-or-equal-to")
				echo (" selected='selected'");
								?>
									value="less-than-or-equal-to">&le;</option>
								<option
								<? if ($relation == "equal-to")
				echo (" selected='selected'");
								?>
									value="equal-to">=</option>
								<option
								<? if ($relation == "not-equal-to")
				echo (" selected='selected'");
								?>
									value="not-equal-to">!=</option>
								<option
								<? if ($relation == "greater-than-or-equal-to")
				echo (" selected='selected'");
								?>
									value="greater-than-or-equal-to">&ge;</option>
								<option
								<? if ($relation == "greater-than")
				echo (" selected='selected'");
								?>
									value="greater-than">&gt;</option>
						</select></td>
						<td><input class="input-medium" type="text" name="date5" maxlength="50"
							value="<?php echo $theDate; ?>" />
						</td>						<td></td>
					</tr>
				</table>
				<button type="submit" class="btn small primary" name="submitDate"
					value="submit">save changes</button>
				<span style="color: red"> <?php echo $_SESSION['error']; ?>
				</span>
			</form>
		</div>
		<div style="height: 30px"></div>
		<a name="temporalrelation"></a>
		<h2>3. Define temporal constraints that compare two attributes</h2>
		If you defined a query variable for a diagnosis or procedure, use this
		instead of the table name.
		<div class="workwell">
			<form action="<?php echo $_SERVER['PHP_SELF'] . '#temporalrelation' ?>"
				method="post">
				<table class="table table-condensed">
					<tr>
						<th style="width: 15%">Comment</th>
						<th style="width: 25%">Table.Date 1</th>
						<th style="width: 25%">Relation</th>
						<th style="width: 25%">Table.Date 2</th>
						<th style="width: 10%">Delete</th>
					</tr>
					<?php
			$selectconstraints = "SELECT * FROM formalised_constraint WHERE userid = '$userid' AND indicatorid = '$indicatorid' AND constrainttype = 'temporal_relation'";
			$content = mysql_query($selectconstraints);
			if (!$content)
				error(mysql_error());
			while ($row = mysql_fetch_array($content)) {
				$id = $row['id'];
				$table = $row['table'];
				$attribute = $row['attribute'];
				$table2 = $row['table2'];
				$attribute2 = $row['attribute2'];
				echo "<tr>";
				echo "<td>" . $row['indicatortext'] . "</td>";
				echo "<td>" . $table . "." . $attribute . "</td>";
				echo "<td>" . $row['relation'] . "</td>";
				echo "<td>" . $table2 . "." . $attribute2 . "</td>";
				echo "<td><a href='delete.php?id=$id&amp;step=$step&amp;anchor=temporalrelation'>x</a></td>";
				echo "</tr>\n";
			}
			mysql_free_result($content);
					?>
					<tr valign="top">
						<td><input class="input-medium" type="text" name="indicatorText" maxlength="50"
							value="<?php echo $indicatorText; ?>" />
						</td>
						<td><select name="tableattribute1Select"
							onchange="this.form.submit();">
								<option value="">please choose</option>
								<?php
			$variablesql = "SELECT * FROM `query_variable` WHERE `userid` = '$userid'  AND `indicatorid` = '$indicatorid' ORDER BY `variable`";
			$variables = mysql_query($variablesql);
			if (!$variables)
				error(mysql_error());
			mysql_select_db($patientsdbname);
			while ($variablerow = mysql_fetch_array($variables)) {
				$variableattributes = mysql_query("DESCRIBE `$variablerow[table]`");
				if (!$variableattributes)
					error(mysql_error());
				while ($variableattributerow = mysql_fetch_array($variableattributes)) {
					$datatype = mysql_query(
							"SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '$variablerow[table]' AND COLUMN_NAME = '$variableattributerow[0]'");
					if (!$datatype)
						error(mysql_error());
					$datatyperow = mysql_fetch_row($datatype);
					if (strcmp($datatyperow[0], "date") == 0) {
						$option = "$variablerow[variable].$variableattributerow[0]";
						echo "<option value='$option'";
						if (strcmp($selectedtableattribute1, $option) == 0) {
							echo "selected='selected'";
						}
						;
						echo ">$option</option>";
					}
				}
				mysql_free_result($variableattributes);
			}
			mysql_free_result($variables);
			foreach ($patienttables as &$patienttable) {
				$attributes = mysql_query("DESCRIBE `$patienttable`");
				if (!$attributes)
					error(mysql_error());
				while ($attributerow = mysql_fetch_array($attributes)) {
					$datatype = mysql_query(
							"SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '$patienttable' AND COLUMN_NAME = '$attributerow[0]'");
					if (!$datatype)
						error(mysql_error());
					$datatyperow = mysql_fetch_row($datatype);
					if (strcmp($datatyperow[0], "date") == 0) {
						$option = "$patienttable.$attributerow[0]";
						echo "<option value='$option'";
						if (strcmp($selectedtableattribute1, $option) == 0) {
							echo "selected='selected'";
						}
						;
						echo ">$option</option>";
					}
				}
			}
			mysql_free_result($attributes);
			mysql_select_db($dbname);
								?>
						</select></td>
						<td><select name="relation1Select" onchange="this.form.submit();">
								<option value="">please choose</option>
								<option
								<? if ($relation1 == "less-than")
				echo (" selected='selected'");
								?>
									value="less-than">&lt;</option>
								<option
								<? if ($relation1 == "less-than-or-equal-to")
				echo (" selected='selected'");
								?>
									value="less-than-or-equal-to">&le;</option>
								<option
								<? if ($relation1 == "equal-to")
				echo (" selected='selected'");
								?>
									value="equal-to">=</option>
								<option
								<? if ($relation1 == "not-equal-to")
				echo (" selected='selected'");
								?>
									value="not-equal-to">!=</option>
								<option
								<? if ($relation1 == "greater-than-or-equal-to")
				echo (" selected='selected'");
								?>
									value="greater-than-or-equal-to">&ge;</option>
								<option
								<? if ($relation1 == "greater-than")
				echo (" selected='selected'");
								?>
									value="greater-than">&gt;</option>
						</select></td>
						<td><select name="tableattribute2Select"
							onchange="this.form.submit();">
								<option value="">please choose</option>
								<?php
			$variablesql = "SELECT * FROM `query_variable` WHERE `userid` = '$userid'  AND `indicatorid` = '$indicatorid' ORDER BY `variable`";
			$variables = mysql_query($variablesql);
			if (!$variables)
				error(mysql_error());
			mysql_select_db($patientsdbname);
			while ($variablerow = mysql_fetch_array($variables)) {
				$variableattributes = mysql_query("DESCRIBE `$variablerow[table]`");
				if (!$variableattributes)
					error(mysql_error());
				while ($variableattributerow = mysql_fetch_array($variableattributes)) {
					$datatype = mysql_query(
							"SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '$variablerow[table]' AND COLUMN_NAME = '$variableattributerow[0]'");
					if (!$datatype)
						error(mysql_error());
					$datatyperow = mysql_fetch_row($datatype);
					if (strcmp($datatyperow[0], "datetime") == 0) {
						$option = "$variablerow[variable].$variableattributerow[0]";
						echo "<option value='$option'";
						if (strcmp($selectedtableattribute2, $option) == 0) {
							echo "selected='selected'";
						}
						;
						echo ">$option</option>";
					}
				}
				mysql_free_result($variableattributes);
			}
			mysql_free_result($variables);
			foreach ($patienttables as &$patienttable) {
				$attributes = mysql_query("DESCRIBE `$patienttable`");
				if (!$attributes)
					error(mysql_error());
				while ($attributerow = mysql_fetch_array($attributes)) {
					$datatype = mysql_query(
							"SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '$patienttable' AND COLUMN_NAME = '$attributerow[0]'");
					if (!$datatype)
						error(mysql_error());
					$datatyperow = mysql_fetch_row($datatype);
					if (strcmp($datatyperow[0], "datetime") == 0) {
						$option = "$patienttable.$attributerow[0]";
						echo "<option value='$option'";
						if (strcmp($selectedtableattribute2, $option) == 0) {
							echo "selected='selected'";
						}
						;
						echo ">$option</option>";
					}
				}
			}
			mysql_free_result($attributes);
			mysql_select_db($dbname);
								?>
						</select></td>
						<td></td>
					</tr>
				</table>
				<button type="submit" class="btn small primary"
					name="submitRelation" value="submit">save changes</button>
				<span style="color: red"> <?php echo $_SESSION['errorrelation']; ?>
				</span>
			</form>
		</div>
		
		
			<div style="height: 30px"></div>
		<a name="lastvalue"></a>
		<h2>4. Select only if the last code has a certain value.</h2>
		<div class="workwell">
			<form action="<?php echo $_SERVER['PHP_SELF'] . '#lastvalue' ?>"
				method="post">
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
				$islastvalue = $informationmodelconstraintrow['lastvalue'];
				$color = "black";
				if ((bool) $islastvalue)
					$color = "blue";
				$fsn = mysql_query("SELECT FULLYSPECIFIEDNAME FROM concepts WHERE CONCEPTID = '$conceptid' ");
				if (!$fsn)
					error(mysql_error());
				$snorow = mysql_fetch_row($fsn);
				echo "<input type='checkbox' name='constraints[]' value='$id' onchange='this.form.submit();'";
				if ((bool) $islastvalue) {
					echo " checked = 'checked' ";
				}
				echo " />&nbsp;<span style='color:$color' >";
				echo "$table.$attribute&nbsp;$relation&nbsp;$conceptid&nbsp;$snorow[0]";
				echo "</span><br />";
			}
			mysql_free_result($informationmodelconstraint);
								?>
									<input type="hidden" name="form" value="lastvalue" />
				<br />
				<span style="color: red"> <?php echo $_SESSION['errorrelation']; ?>
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