<?php
require_once 'inc/util.inc';
session_start();
$_SESSION['queryvariableerror'] = "";
$_SESSION['error'] = "";
$_SESSION['iderror'] = "";
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
			$step = "informationmodel";
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
			// automated generation of query variables
			if ($_POST['generateAutomatically'] == "submit") {
				$concepts = mysql_query(
						"SELECT * FROM concept WHERE userid = '$userid' AND indicatorid = '$indicatorid' ORDER BY `id`");
				if (!$concepts)
					error(mysql_error());
				while ($conceptrow = mysql_fetch_array($concepts)) {
					$conceptid = $conceptrow['conceptid'];
					$boundesult = mysql_query(
							"SELECT * FROM `formalised_constraint` WHERE constrainttype = 'informationmodel' AND userid = '$userid' AND indicatorid = '$indicatorid' AND `conceptid` = '$conceptid'");
					if (!$boundesult)
						error(mysql_error());
					$bound_num_rows = mysql_num_rows($boundesult);
					$fsn = mysql_query(
							"SELECT FULLYSPECIFIEDNAME FROM `$snomeddbname`.concepts WHERE CONCEPTID = '$conceptid' ");
					if (!$fsn)
						error(mysql_error());
					$row = mysql_fetch_row($fsn);
					mysql_free_result($fsn);
					$aliasname = str_replace(' ', '_', $row[0]);
					if ($bound_num_rows == 0) {
						$isprocedure = mysql_query(
								"SELECT COUNT(*) FROM `$snomeddbname`.`sct_transitiveclosure` WHERE `SubtypeId` = $conceptid AND `SupertypeId` = 71388002");
						if (!$isprocedure)
							error(mysql_error());
						$procedurerow = mysql_fetch_row($isprocedure);
						if (strcmp($procedurerow[0], "1") == 0)
							$aliasfortable = "Procedure_undertaken";
						if (strcmp($procedurerow[0], "1") == 0)
							$sctid = "Procedure_snomedctconceptid";
						$isfinding = mysql_query(
								"SELECT COUNT(*) FROM `$snomeddbname`.`sct_transitiveclosure` WHERE `SubtypeId` = $conceptid AND `SupertypeId` = 404684003");
						if (!$isfinding)
							error(mysql_error());
						$findingrow = mysql_fetch_row($isfinding);
						if (strcmp($findingrow[0], "1") == 0)
							$aliasfortable = "Diagnosis";
						if (strcmp($findingrow[0], "1") == 0)
							$sctid = "Diagnosis_snomedctconceptid";
						if ((strcmp($procedurerow[0], "1") == 0) || (strcmp($findingrow[0], "1") == 0)) {
							// save query variable if not already in there!!
							$numquery = "SELECT * FROM `query_variable` WHERE `userid` = '$userid'  AND `indicatorid` = '$indicatorid' AND `variable`	= '$aliasname' AND `table` = '$aliasfortable'";
							$result = mysql_query($numquery);
							if (!$result)
								error(mysql_error());
							$num_rows = mysql_num_rows($result);
							mysql_free_result($result);
							if ($num_rows == 0) {
								$sql = "INSERT INTO query_variable (`variableid`, `userid`, `indicatorid`, `variable`, `table`, `inserted`) VALUES (
								NULL,
								$userid,
								$indicatorid,
								'$aliasname',
								'$aliasfortable', NOW())";
								mysql_query($sql);
							}
							// save constraint
							$sql = "' INTO formalised_constraint (`id`, `userid`, `indicatorid`, `constrainttype`, `indicatortext`, `table`, `attribute`, `conceptid`, `relation`, `date`, `table2`, `attribute2`, `number`, `isexclusion`, `numeratoronly`, `inserted`) VALUES (
							NULL,
							$userid,
							$indicatorid,
							'informationmodel',
							NULL,
							'$aliasname',
							'$sctid',
							'$conceptid', 'is', NULL, NULL, NULL, NULL, NULL, NULL, NOW())";
							mysql_query($sql);
						}
					}
				}
				mysql_free_result($concepts);
			}

			if ($_POST['form'] == "variableexclusionconstraints") {

				// update constraints
				$allconstraints = mysql_query(
						"SELECT * FROM query_variable WHERE indicatorid = '$indicatorid' AND userid = '$userid'");
				if (!$allconstraints)
					error(mysql_error());
				while ($constraintinfo = mysql_fetch_array($allconstraints)) {
					$id = $constraintinfo['variableid'];
					$sqlupdate = "UPDATE query_variable SET isexclusion = '0' WHERE `variableid` = '$id'";
					mysql_query($sqlupdate);
				}
				mysql_free_result($allconstraints);
				$selconstraints = $_POST['constraints'];
				if (!empty($selconstraints)) {

					$N = count($selconstraints);
					for ($i = 0; $i < $N; $i++) {
						$sqlupdate = "UPDATE query_variable SET `isexclusion` = '1' WHERE `variableid` = '$selconstraints[$i]'";
						mysql_query($sqlupdate);
					}
				}
			}

			if (isset($_POST['tableSelect']) || $_POST['submitQueryVariable'] == "submit") {
				$queryvariable = $_POST['queryvariable'];
				$table = $_POST['tableSelect'];
				// all empty
				if (empty($queryvariable) && empty($table)) {
					$_SESSION['queryvariableerror'] = "Nothing saved: all fields empty";
				} else if (empty($queryvariable) || empty($table)) {
					$_SESSION['queryvariableerror'] = "Nothing saved: one or more empty fields";
				} else {
					$numquery = "SELECT * FROM `query_variable` WHERE `userid` = '$userid'  AND `indicatorid` = '$indicatorid' AND `variable`	= '$queryvariable' AND `table` = '$table'";
					$result = mysql_query($numquery);
					if (!$result)
						error(mysql_error());
					$num_rows = mysql_num_rows($result);
					mysql_free_result($result);
					if ($num_rows > 0) {
						$_SESSION['queryvariableerror'] = "Nothing saved: same row already in database. ";
					} else {
						$prepvariable = PrepSQL($queryvariable);
						$sql = "INSERT INTO query_variable (`variableid`, `userid`, `indicatorid`, `variable`, `table`, `inserted`) VALUES (
						NULL,
						$userid,
						$indicatorid,
						'$prepvariable',
						'$table', NOW())";
						mysql_query($sql);
						$queryvariable = "";
						$table = "";
					}
				}
			}
			if (isset($_POST['tableattributeSelect']) || isset($_POST['conceptidSelect'])) {
				$selectedattribute = $_POST['tableattributeSelect'];
				$selectedconceptid = $_POST['conceptidSelect'];
				// all empty
				if (empty($selectedattribute) && empty($selectedconceptid)) {
					$_SESSION['error'] = "Nothing saved: all fields empty";
				} else if (empty($selectedattribute) || empty($selectedconceptid)) {
					$_SESSION['error'] = "Nothing saved: one or more empty fields";
				} else {
					$pos = strpos($selectedattribute, ".");
					$table = substr($selectedattribute, 0, $pos);
					$attribute = substr($selectedattribute, ($pos + 1), strlen($selectedattribute));
					$numquery = "SELECT * FROM `formalised_constraint` WHERE `userid` = '$userid'  AND `indicatorid` = '$indicatorid' AND `constrainttype` = 'informationmodel' AND `table` = '$table' AND `attribute` = '$attribute' AND `conceptid` = $selectedconceptid";
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
						'informationmodel',
						NULL,
						'$table',
						'$attribute',
						'$selectedconceptid', 'is', NULL, NULL, NULL, NULL, NULL, NULL, NOW())";
						mysql_query($sql);
						$selectedattribute = "";
						$selectedconceptid = "";
					}
				}
			}
			if (isset($_POST['tableidSelect']) || isset($_POST['tableidSelect2'])) {
				$selectedid = $_POST['tableidSelect'];
				$selectedid2 = $_POST['tableidSelect2'];
				$pos = strpos($selectedid, ".");
				$table = substr($selectedid, 0, $pos);
				$attribute = substr($selectedid, ($pos + 1), strlen($selectedid));
				$pos2 = strpos($selectedid2, ".");
				$table2 = substr($selectedid2, 0, $pos2);
				$attribute2 = substr($selectedid2, ($pos2 + 1), strlen($selectedid2));
				// all empty
				if (empty($selectedid) && empty($selectedid2)) {
					$_SESSION['iderror'] = "Nothing saved: all fields empty";
				} else if (empty($selectedid) || empty($selectedid2)) {
					$_SESSION['iderror'] = "Nothing saved: one or more empty fields";
				} else if (($attribute != $attribute2)
						&& (!endsWith($attribute, "diagnosisid") && !endsWith($attribute2, "diagnosisid"))) {
					$_SESSION['iderror'] = "Nothing saved: you can only join two tables by a common id";
				} else if (($table == $table2) && ($attribute == $attribute2)) {
					$_SESSION['iderror'] = "Nothing saved: it does not make sense to match the same table on the same id";
				} else {
					$numquery = "SELECT * FROM `formalised_constraint` WHERE `userid` = '$userid'  AND `indicatorid` = '$indicatorid' AND `constrainttype` = 'idjoin' AND `table` = '$table' AND `attribute` = '$attribute' AND `table2` = '$table2' AND `attribute2` = '$attribute2'";
					$result = mysql_query($numquery);
					if (!$result)
						error(mysql_error());
					$num_rows = mysql_num_rows($result);
					mysql_free_result($result);
					if ($num_rows > 0) {
						$_SESSION['iderror'] = "Nothing saved: same row already in database. ";
					} else {
						$sql = "INSERT INTO formalised_constraint (`id`, `userid`, `indicatorid`, `constrainttype`, `indicatortext`, `table`, `attribute`, `conceptid`, `relation`, `date`, `table2`, `attribute2`, `number`, `isexclusion`, `numeratoronly`, `inserted`) VALUES (
						NULL,
						$userid,
						$indicatorid,
						'idjoin',
						NULL,
						'$table',
						'$attribute',
						NULL, 'is', NULL, '$table2', '$attribute2', NULL, NULL, NULL, NOW())";
						mysql_query($sql);
						$selectedid = "";
						$selectedid2 = "";
					}
				}
			}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>CLIF: Information Model</title>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet"></link>
<script type="text/javascript" src="http://code.jquery.com/jquery-latest.js"></script>
<script type="text/javascript" src="js/bootstrap.min.js"></script>
</head>
<body>
	<?php include_once("inc/head.inc") ?>
	<div style="height: 50px"></div>
	 <div class="container">
		<h1>Define the information model</h1>
		<p>
			First, please have a look at the <a target="_blank"
				href="schema/tables/Patient.html">database schema</a>. On this page
			you need to define the information model. This consists of three
			steps: first, in case you need to select several elements of the same
			type / table Procedure_undertaken or Diagnosis, such as the procedures lymph node examination and colectomy,
			you need to define query variables in order to be able to distinguish
			them (<a href="<?php echo $_SERVER['PHP_SELF'] . '#queryvariables' ?>">substeps
				1.</a>). Then, in <a
				href="<?php echo $_SERVER['PHP_SELF'] . '#sctcodes' ?>">substeps 2</a>,
			you bind previously defined SNOMED CT codes to the attribute snomedctconceptid of
			tables and query variables defined before. Additionally, you might
			need to define how the tables and query variables are related to each
			other (<a
				href="<?php echo $_SERVER['PHP_SELF'] . '#join' ?>">substeps 3</a>).
		</p>
		<?php include_once("inc/indicator.inc") ?>
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
			<button type="submit" class="btn small primary"
				name="generateAutomatically" value="submit">Run substeps 1. and 2
				automatically</button>
		</form>
		<a name="queryvariables"></a>
		<h2>1. Define query variables</h2>
		If you need several elements of the type / database table Procedure_undertaken or Diagnosis (check the <a
			target="_blank" href="schema/tables/Patient.html">database schema</a>), please define query variables
			(i.e. alias names) in order to distinguish them.
		<div class="workwell">
			<form action="<?php echo $_SERVER['PHP_SELF'] . '#queryvariables' ?>"
				method="post" name="QueryVariableForm">
				<table class="table table-condensed">
					<tr>
						<th>Assign an intuitive name for a query variable</th>
						<th>=</th>
						<th>Choose database table</th>
						<th>Exluded</th>
						<th>Delete</th>
					</tr>
					<?php
			$content = mysql_query(
					"SELECT * FROM query_variable WHERE userid = '$userid' AND indicatorid = '$indicatorid'");
			if (!$content)
				error(mysql_error());
			while ($row = mysql_fetch_array($content)) {
				$id = $row['variableid'];
				$variable = $row['variable'];
				$querytable = $row['table'];
				$isexclusion = $row['isexclusion'];
				echo "<tr>";
				echo "<td>" . $variable . "</td>";
				echo "<td>=</td>";
				echo "<td>" . $querytable . "</td>";
				echo "<td><input type='checkbox' name='constraints[]' value='$id' onchange='this.form.submit();'";
				if ((bool) $isexclusion) {
					echo " checked = 'checked' ";
				}
				echo " /></td>";
				echo "<td><a href='deletevariable.php?id=$id&amp;step=query_variable'>x</a></td>";
				echo "</tr>";
			}

			mysql_free_result($content);
					?>
					<input type="hidden" name="form" value="variableexclusionconstraints" />
					</form>
					<tr valign="top">
						<td><input type="text" name="queryvariable" maxlength="100"
							value="<?php echo $queryvariable; ?>" /></td>
						<td>=</td>
						<td><select name="tableSelect" onchange="this.form.submit();">
								<option value="">please choose</option>
								<?php
			foreach ($patienttables as &$patienttable) {
				$option = "$patienttable";
				if ((strcmp($option, "firstpage") == 0) || (strcmp($option, "lab_test") == 0)
						|| (strcmp($option, "examination") == 0) || (strcmp($option, "treatment") == 0)) {
					echo "<option value='$option'";
					if (strcmp($table, $option) == 0) {
						echo "selected='selected'";
					}
					;
					echo ">$option</option>";
				}
			}
								?>
						</select></td>
						<td></td>
					</tr>
				</table>
				<button type="submit" class="btn small primary"
					name="submitQueryVariable" value="submit">save changes</button>
				<span style="color: red"> <?php echo $_SESSION['queryvariableerror']; ?>
				</span>
			</form>
		</div>
		<div style="height: 30px"></div>
		<a name="sctcodes"></a>
		<h2>2. Bind the concepts to query variables / database tables</h2>
		In this step, bind the concept ids that you entered in <a
			href="concepts.php">the previous step</a> to the attribute conceptid of
		query variables and tables. If you defined a query variable above, use
		this instead of the table name (this also applies to all further
		steps)!
		<div class="workwell">
			<form action="<?php echo $_SERVER['PHP_SELF'] . '#sctcodes' ?>"
				method="post" name="informationModelForm">
				<table class="table table-condensed">
					<tr>
						<th>queryvariable/table.conceptid</th>
						<th>=</th>
						<th>Value (concept defined previously)</th>
						<th>Delete</th>
					</tr>
					<?php
			$content = mysql_query(
					"SELECT * FROM formalised_constraint WHERE userid = '$userid' AND indicatorid = '$indicatorid' AND constrainttype = 'informationmodel'");
			if (!$content)
				error(mysql_error());
			while ($row = mysql_fetch_array($content)) {
				$id = $row['id'];
				$conceptid = $row['conceptid'];
				$fsn = mysql_query(
						"SELECT FULLYSPECIFIEDNAME FROM `$snomeddbname`.concepts WHERE CONCEPTID = '$conceptid' ");
				if (!$content)
					error(mysql_error());
				$snorow = mysql_fetch_row($fsn);
				mysql_free_result($fsn);
				$conceptexists = mysql_query(
						"SELECT * FROM concept WHERE conceptid = '$conceptid' AND userid = '$userid' AND indicatorid = '$indicatorid'");
				if (!$conceptexists)
					error(mysql_error());
				$conceptexistsrow = mysql_fetch_row($conceptexists);
				$concept_num_rows = mysql_num_rows($conceptexists);
				mysql_free_result($conceptexists);
				echo "<tr>";
				echo "<td>" . $row['table'] . "." . $row['attribute'];
				$found = false;
				foreach ($patienttables as &$patienttable) {
					if (strcmp($row['table'], $patienttable) == 0) {
						$found = true;
					}
				}
				$variablesql = "SELECT * FROM `query_variable` WHERE `userid` = '$userid'  AND `indicatorid` = '$indicatorid'";
				$tables = mysql_query($variablesql);
				if (!$tables)
					error(mysql_error());
				while ($tablerow = mysql_fetch_array($tables)) {
					if (strcmp($tablerow['variable'], $row['table']) == 0) {
						$found = true;
					}
				}
				mysql_free_result($tables);
				if (!$found) {
					echo "<br /><span style='color: red'>This query variable is undefined. Do you really want to use it? </span>";
				}
				echo "</td>";
				echo "<td>=</td>";
				echo "<td>" . $row['conceptid'] . " " . $snorow[0];
				if ($concept_num_rows == 0) {
					echo "<br /><span style='color: red'> This concept has been deleted. Are you sure that you want to use it? </span>";
				}
				echo "</td>";
				echo "<td><a href='delete.php?id=$id&amp;step=$step&amp;anchor=sctcodes'>x</a></td>";
				echo "</tr>\n";
			}
			mysql_free_result($content);
					?>
					<tr valign="top">
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

					foreach ($codecolumns as &$codecolumn) {

						if (endsWith($variableattributerow[0], $codecolumn)) {
							$option = "$variablerow[variable].$variableattributerow[0]";
							echo "<option value='$option'";
							if (strcmp($selectedattribute, $option) == 0) {
								echo "selected='selected'";
							}

							echo ">$option</option>";
						}
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
					foreach ($codecolumns as &$codecolumn) {

						if (endsWith($attributerow[0], $codecolumn)) {

							$option = "$patienttable.$attributerow[0]";
							echo "<option value='$option'";
							if (strcmp($selectedattribute, $option) == 0) {
								echo "selected='selected'";
							}

							echo ">$option</option>";
						}
					}
				}
			}
			mysql_free_result($attributes);
			mysql_select_db($dbname);
								?>
						</select></td>
						<td>=</td>
						<td><select name="conceptidSelect" onchange="this.form.submit();">
								<option value="">please choose</option>
								<?php
			$concepts = mysql_query(
					"SELECT * FROM concept WHERE userid = '$userid' AND indicatorid = '$indicatorid' ORDER BY `id`");
			if (!$concepts)
				error(mysql_error());
			while ($conceptrow = mysql_fetch_array($concepts)) {
				$conceptid = $conceptrow['conceptid'];
				//$fsn = mysql_query(
				//	"SELECT FULLYSPECIFIEDNAME FROM `$snomeddbname`.concepts WHERE CONCEPTID = '$conceptid' ");
				//	if (!$fsn)
				//	error(mysql_error());
				//	$row = mysql_fetch_row($fsn);
				//mysql_free_result($fsn);
				echo "<option value='$conceptid'";
				if ($selectedconceptid == $conceptid)
					echo ("selected='selected'");
				echo ">$conceptid</option>";
			}
			mysql_free_result($concepts);
								?>
						</select></td>
						<td></td>
					</tr>
				</table>
				<span style="color: red"> <?php echo $_SESSION['error']; ?>
				</span>
			</form>
		</div>
		<div style="height: 30px"></div>
		<a name="join"></a>
		<h2>3. Relate bound elements to each other</h2>
		The main goal of this step is to exploit the problem-oriented patient record, that is to relate procedures to the diagnoses due to which they have been carried out. Have a look at the <a target="_blank"
			href="schema/tables/Patient.html">database schema</a> to see which
		tables you can join.
		<div class="workwell">
			<form action="<?php echo $_SERVER['PHP_SELF'] . '#join' ?>"
				method="post" name="IDsForm">
				<table class="table table-condensed">
					<tr>
						<th>queryvariable/table.ID</th>
						<th>=</th>
						<th>queryvariable/table.ID 2</th>
						<th>Delete</th>
					</tr>
					<?php
			$content = mysql_query(
					"SELECT * FROM formalised_constraint WHERE userid = '$userid' AND indicatorid = '$indicatorid' AND constrainttype = 'idjoin'");
			if (!$content)
				error(mysql_error());
			while ($row = mysql_fetch_array($content)) {
				$id = $row['id'];
				echo "<tr>";
				echo "<td>" . $row['table'] . "." . $row['attribute'];
				$found = false;
				foreach ($patienttables as &$patienttable) {
					if (strcmp($row['table'], $patienttable) == 0) {
						$found = true;
					}
				}
				$variablesql = "SELECT * FROM `query_variable` WHERE `userid` = '$userid'  AND `indicatorid` = '$indicatorid'";
				$tables = mysql_query($variablesql);
				if (!$tables)
					error(mysql_error());
				while ($tablerow = mysql_fetch_array($tables)) {
					if (strcmp($tablerow['variable'], $row['table']) == 0) {
						$found = true;
					}
				}
				mysql_free_result($tables);
				if (!$found) {
					echo "<br /><span style='color: red'>This query variable is undefined. Do you really want to use it? </span>";
				}
				echo "</td>";
				echo "<td>=</td>";
				echo "<td>" . $row['table2'] . "." . $row['attribute2'];
				$found = false;
				foreach ($patienttables as &$patienttable) {
					if (strcmp($row['table2'], $patienttable) == 0) {
						$found = true;
					}
				}
				$variablesql = "SELECT * FROM `query_variable` WHERE `userid` = '$userid'  AND `indicatorid` = '$indicatorid'";
				$tables = mysql_query($variablesql);
				if (!$tables)
					error(mysql_error());
				while ($tablerow = mysql_fetch_array($tables)) {
					if (strcmp($tablerow['variable'], $row['table2']) == 0) {
						$found = true;
					}
				}
				mysql_free_result($tables);
				if (!$found) {
					echo "<br /><span style='color: red'>This query variable is undefined. Do you really want to use it? </span>";
				}
				echo "</td>";
				echo "<td><a href='delete.php?id=$id&amp;step=$step&amp;anchor=join'>x</a></td>";
				echo "</tr>\n";
			}
			mysql_free_result($content);
					?>
					<tr valign="top">
						<td><select name="tableidSelect" onchange="this.form.submit();">
								<option value="">please choose</option>
								<?php
			$variablesql = "SELECT * FROM `query_variable` WHERE `userid` = '$userid'  AND `indicatorid` = '$indicatorid'  ORDER BY `variable`";
			$variables = mysql_query($variablesql);
			if (!$variables)
				error(mysql_error());
			mysql_select_db($patientsdbname);
			while ($variablerow = mysql_fetch_array($variables)) {
				$variableattributes = mysql_query("DESCRIBE `$variablerow[table]`");
				if (!$variableattributes)
					error(mysql_error());
				while ($variableattributerow = mysql_fetch_array($variableattributes)) {
					if ((endsWith($variableattributerow[0], "id"))
							&& (!endsWith($variableattributerow[0], "snomedctconceptid"))
							&& (strcmp($variableattributerow[0], "patientid") != 0)) {
						$option = "$variablerow[variable].$variableattributerow[0]";
						echo "<option value='$option'";
						if (strcmp($selectedid, $option) == 0) {
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
					if ((endsWith($attributerow[0], "id")) && (!endsWith($attributerow[0], "snomedctconceptid"))
							&& (strcmp($attributerow[0], "patientid") != 0)) {
						$option = "$patienttable.$attributerow[0]";
						echo "<option value='$option'";
						if (strcmp($selectedid, $option) == 0) {
							echo "selected='selected'";
						}
						;
						echo ">$option</option>";
					}
				}
			}
			mysql_select_db($dbname);
			mysql_free_result($attributes);
								?>
						</select></td>
						<td>=</td>
						<td><select name="tableidSelect2" onchange="this.form.submit();">
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
					if ((endsWith($variableattributerow[0], "id"))
							&& (!endsWith($variableattributerow[0], "snomedctconceptid"))
							&& (strcmp($variableattributerow[0], "patientid") != 0)) {
						$option = "$variablerow[variable].$variableattributerow[0]";
						echo "<option value='$option'";
						if (strcmp($selectedid2, $option) == 0) {
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
					if ((endsWith($attributerow[0], "id")) && (!endsWith($attributerow[0], "snomedctconceptid"))
							&& (strcmp($attributerow[0], "patientid") != 0)) {
						$option = "$patienttable.$attributerow[0]";
						echo "<option value='$option'";
						if (strcmp($selectedid2, $option) == 0) {
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
				<span style="color: red"> <?php echo $_SESSION['iderror']; ?>
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