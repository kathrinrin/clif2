<?php
require_once 'calendar/classes/tc_calendar.php';
require_once 'inc/util.inc';
session_start();
$_SESSION['lymphcodeerror'] = "";
$_SESSION['imerror'] = "";
$_SESSION['im2010error'] = "";
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
			if ((bool) $_SESSION['unlocked']) {
				header("Location: index.php");
				return;
			}
			$step = "unlock";
			$userid = $userinfo['id'];
			$successmessage = "";
			$selectedall = $userinfo['selectedall'];
			$insertedcode = $userinfo['insertedlymphcode'];
			$definedimlne = $userinfo['definedimlne'];
			$selectedlne = $userinfo['selectedlne'];
			$definedimlne2010 = $userinfo['definedimlne2010'];
			$selectedlne2010 = $userinfo['selectedlne2010'];
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
						mysql_query($sql);
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
			$group = $_GET["group"];
			if ($group == "all" || (bool) $selectedall) {
				mysql_select_db($patientsdbname);
				$sql = "SELECT * FROM patient";
				$result = mysql_query($sql);
				mysql_select_db($dbname);
				if (!$result)
					error(mysql_error());
				$allpatients = mysql_num_rows($result);
				mysql_free_result($result);
				$sqlupdate = "UPDATE user SET selectedall = '1' WHERE id = '$userid';";
				mysql_query($sqlupdate);
				$selectedall = 1;
			}
			if ($group == "lne" || (bool) $selectedlne) {
				mysql_select_db($patientsdbname);
				$sql = "SELECT * FROM `procedure` , `patient` WHERE `procedure`.`snomedctconceptid` = 284427004 AND `patient`.`patientid` = `procedure`.`patientid`";
				$result = mysql_query($sql);
				mysql_select_db($dbname);
				if (!$result)
					error(mysql_error());
				$lnepatients = mysql_num_rows($result);
				mysql_free_result($result);
				$sqlupdate = "UPDATE user SET selectedlne = '1' WHERE id = '$userid';";
				mysql_query($sqlupdate);
				$selectedlne = 1;
			}
			if ($group == "lne2010" || (bool) $selectedlne2010) {
				mysql_select_db($patientsdbname);
				$sql = "SELECT * FROM `procedure` , `patient` WHERE `procedure`.`snomedctconceptid` = 284427004 AND  `procedure`.`datetime` >=  '2010-01-01' AND `patient`.`patientid` = `procedure`.`patientid`";
				$result = mysql_query($sql);
				mysql_select_db($dbname);
				if (!$result)
					error(mysql_error());
				$lne2010patients = mysql_num_rows($result);
				mysql_free_result($result);
				$sqlupdate = "UPDATE user SET selectedlne2010 = '1' WHERE id = '$userid';";
				mysql_query($sqlupdate);
				$selectedlne2010 = 1;
			}
			if ($_POST['submitAll'] == "submit") {
				// 1. select lymph code
				$lymphCode = $_POST['lymphCode'];
				if (empty($lymphCode) || $lymphCode != "284427004") {
					$_SESSION['lymphcodeerror'] = "Field empty or incorrect SNOMED CT ID";
				} else {
					$sqlupdate = "UPDATE user SET insertedlymphcode = '1' WHERE id = '$userid';";
					mysql_query($sqlupdate);
					$sql = "INSERT INTO concept (userid, indicatorid, step, comment, inserted) VALUES (
					$userid, 'NULL', '$step', '$commentnewprep', NOW())";
					mysql_query($sql);
					$insertedcode = 1;
				}
			}
			if (!empty($_POST['tableattributeSelect']) || !empty($_POST['conceptidSelect'])) {
				// 3. define information model
				$selectedattribute = $_POST['tableattributeSelect'];
				$selectedconceptid = $_POST['conceptidSelect'];
				// all empty
				if (empty($selectedattribute) && empty($selectedconceptid)) {
					$_SESSION['imerror'] = "All fields empty";
				} else if (empty($selectedattribute) || empty($selectedconceptid)) {
					$_SESSION['imerror'] = "One or more empty fields";
				} else if ($selectedattribute == "procedure_undertaken.Procedure_snomedctconceptid"
						&& $selectedconceptid == "284427004") {
					$sqlupdate = "UPDATE user SET definedimlne = '1' WHERE id = '$userid';";
					mysql_query($sqlupdate);
					$definedimlne = 1;
				} else {
					$_SESSION['imerror'] = "One or more of the values not correct";
				}
			}
			if (!empty($_POST['tableattribute2010Select']) || !empty($_POST['relationSelect'])
					|| !(empty($_POST['date5']) || (strcmp($_POST['date5'], "0000-00-00") == 0))) {
				// 5. define information model 2010
				$selectedattribute2010 = $_POST['tableattribute2010Select'];
				$relation = $_POST['relationSelect'];
				$theDate = $_POST['date5'];
				// all empty
				if (empty($selectedattribute2010) && empty($relation) && empty($theDate)) {
					$_SESSION['im2010error'] = "All fields empty";
				} else if (empty($selectedattribute2010) || empty($relation)
						|| (strcmp($_POST['date5'], "0000-00-00") == 0)) {
					$_SESSION['im2010error'] = "One or more empty fields";
				} else if ($selectedattribute2010 == "Procedure_undertaken.Date_and_time_performed"
						&& $relation == "greater-than-or-equal-to" && $theDate == "2010-01-01") {
					$sqlupdate = "UPDATE user SET definedimlne2010 = '1' WHERE id = '$userid';";
					mysql_query($sqlupdate);
					$definedimlne2010 = 1;
				} else {
					$_SESSION['im2010error'] = "One or more of the values not correct";
				}
			}
			if ((bool) $selectedall && (bool) $insertedcode && (bool) $definedimlne && (bool) $selectedlne
					&& (bool) $definedimlne2010 && (bool) $selectedlne2010) {
				$sqlupdate = "UPDATE user SET unlocked = '1', unlock_date = NOW() WHERE id = '$userid';";
				mysql_query($sqlupdate);
				$successmessage = "yes!";
			}
			if ((bool) $insertedcode) {
				$lymphCode = "284427004";
			}
			if ((bool) $definedimlne) {
				$selectedattribute = "Procedure_undertaken.Procedure_snomedctconceptid";
				$selectedconceptid = "284427004";
			}
			if ((bool) $definedimlne2010) {
				$selectedattribute2010 = "Procedure_undertaken.Date_and_time_performed";
				$relation = "greater-than-or-equal-to";
				$theDate = "2010-01-01";
			}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>Indicator Formalisation: Unlock</title>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<link rel="stylesheet/less"
	href=" twitter-bootstrap-0f11410/lib/bootstrap.less" />
<script type="text/javascript" src="js/less-1.1.3.min.js"></script>
<script type="text/javascript" src="js/jquery-1.6.2.min.js"></script>
</head>
<body>
	<div class="container">
		<p align="right">
			logged in as
			<?php echo $username; ?>
			+ <a href="logout.php">logout</a>
		</p>
		<div style="height: 10px"></div>
		<h1>CLIF: Indicator Formalisation</h1>
		<p>You have to do some little exercises in order to unlock the page.
			The goal is to give you a feeling of how the method and this page
			work. We start out by selecting all patients, and then add
			constraints to the query to select more specific, i.e. less patients.
		</p>
		<div style="height: 30px"></div>
		<div class="workwell">
			<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post"
				name="unlockForm">
				<table style="border: 2px solid #B43104; valign: top">
					<tr>
						<td
						<?php if ((bool) $selectedall)
				echo " style='background-color: #66CC66'";
						?>>1.</td>
						<td style="width: 310px">Click here to select all patients: <a
							href="<?php $_SERVER['PHP_SELF'] ?>?group=all">run query</a>
						</td>
						<td><?php echo $allpatients; ?></td>
					</tr>
					<tr>
						<td
						<?php if ((bool) $insertedcode)
				echo " style='background-color: #66CC66'";
						?>>2.</td>
						<td><?php if ($_SESSION['lymphcodeerror'] != "")
				echo "<div style='color:red'>" . $_SESSION['lymphcodeerror'] . "</div>";
							?>
							Search the concept ID of the SNOMED CT concept "Examination of
							lymph nodes (procedure)". For this, you can use any SNOMED CT
							browser, such as <a href="http://terminologie.nictiz.nl/"
							target="_blank">the one of Nictiz</a>, <a
							href="http://www.medicalclassifications.com/SNOMEDbrowser/"
							target="_blank">The SNOMED CT Browser</a> or the <a
							href="http://vtsl.vetmed.vt.edu/" target="_blank">VTSL
								Terminology Browser</a>. Insert it and save it by hitting Enter
							or the button:</td>
						<td><input type="text" name="lymphCode" maxlength="50"
							value="<?php echo $lymphCode; ?>" />
							<button type="submit" class="btn small primary" name="submitAll"
								value="submit">save changes</button></td>
					</tr>
					<tr>
						<td
						<?php if ((bool) $definedimlne)
				echo " style='background-color: #66CC66'";
						?>>3.</td>
						<td><?php if ($_SESSION['imerror'] != "")
				echo "<div style='color:red'>" . $_SESSION['imerror'] . "</div>";
							?>Define
							the information model: state that patient must have had a
							procedure of type lymph node examination</td>
						<td><table>
								<tr>
									<th>Attribute</th>
									<th></th>
									<th>Value</th>
								</tr>
								<tr valign="top">
									<td><select name="tableattributeSelect"
										onchange="this.form.submit();">
											<option value="">please choose</option>
											<?php
			mysql_select_db($patientsdbname);
			foreach ($patienttables as &$patienttable) {
				$attributes = mysql_query("DESCRIBE `$patienttable`");
				if (!$attributes)
					error(mysql_error());
				while ($attributerow = mysql_fetch_array($attributes)) {
					if (endsWith($attributerow[0], "Procedure_snomedctconceptid")) {
						$option = "$patienttable.$attributerow[0]";
						echo "<option value='$option'";
						if (strcmp($selectedattribute, $option) == 0) {
							echo " selected='selected'";
						}
						;
						echo ">$option</option>";
					}
				}
				mysql_free_result($attributes);
			}
			mysql_select_db($dbname);
											?>
									</select>
									</td>
									<td>=</td>
									<td><select name="conceptidSelect"
										onchange="this.form.submit();">
											<option value="">please choose...</option>
											<?php
			if (($lymphCode == "284427004") && $insertedcode) {
				$conceptid = "284427004";
				$fsn = mysql_query("SELECT FULLYSPECIFIEDNAME FROM concepts WHERE CONCEPTID = '$conceptid' ");
				if (!$fsn)
					error(mysql_error());
				$row = mysql_fetch_row($fsn);
				mysql_free_result($fsn);
				echo "<option value='$conceptid' selected='selected'";
				echo ">$conceptid $row[0]</option>";
			}
			mysql_free_result($concepts);
											?>
									</select></td>
								</tr>
							</table></td>
					</tr>
					<tr>
						<td
						<?php if ((bool) $selectedlne)
				echo " style='background-color: #66CC66'";
						?>>4.</td>
						<td>Click here to select all patients who had a lymph node
							examination: <a
							<?php if (!(bool) $definedimlne) {
				echo "style='color: gray;'";
			}
							?>
							href="<?php if ((bool) $definedimlne) {
				echo "" . $_SERVER['PHP_SELF'] . "?group=lne";
			} else {
				echo $_SERVER['PHP_SELF'];
			}
								  ?>">run
								query</a>
						</td>
						<td><?php echo $lnepatients; ?>
						</td>
					</tr>
					<tr>
						<td
						<?php if ((bool) $definedimlne2010)
				echo " style='background-color: #66CC66'";
						?>>5.</td>
						<td><?php if ($_SESSION['im2010error'] != "")
				echo "<div style='color:red'>" . $_SESSION['im2010error'] . "</div>";
							?>Define
							a temporal constraint: state that the procedure must have been
							after or on the first of January 2010</td>
						<td><table>
								<tr>
									<th>Attribute</th>
									<th>Relation</th>
									<th style="width: 150px">Date</th>
								</tr>
								<tr valign="top">
									<td><select name="tableattribute2010Select"
										onchange="this.form.submit();">
											<option value="">please choose</option>
											<?php
			mysql_select_db($patientsdbname);
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
						if (strcmp($selectedattribute2010, $option) == 0) {
							echo " selected='selected'";
						}
						;
						echo ">$option</option>";
					}
				}
				mysql_free_result($attributes);
			}
			mysql_select_db($dbname);
											?>
									</select>
									</td>
									<td><select name="relationSelect"
										onchange="this.form.submit();">
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
									<td><?php
			$myCalendar = new tc_calendar("date5", true, false);
			$myCalendar->setIcon("calendar/images/iconCalendar.gif");
			if (!empty($theDate) && (strcmp($theDate, "0000-00-00") != 0)) {
				$myCalendar
						->setDate(date('d', strtotime($theDate)), date('m', strtotime($theDate)),
								date('Y', strtotime($theDate)));
			}
			$myCalendar->autoSubmit(true, "unlockForm");
			$myCalendar->setPath("calendar/");
			$myCalendar->setYearInterval(1900, 2015);
			$myCalendar->dateAllow('1900-01-01', '2015-01-01');
			$myCalendar->setDateFormat('j F Y');
			$myCalendar->setAlignment('left', 'bottom');
			$myCalendar->writeScript();
										?>
									</td>
								</tr>
							</table></td>
					</tr>
					<tr>
						<td
						<?php if ((bool) $selectedlne2010)
				echo " style='background-color: #66CC66'";
						?>>6.</td>
						<td>Select all patients who had a lymph node examination after the
							first of January 2010: <a
							<?php if (!((bool) $definedimlne && (bool) $definedimlne2010)) {
				echo "style='color: gray;'";
			}
							?>
							href="<?php if ((bool) $definedimlne && (bool) $definedimlne2010) {
				echo "" . $_SERVER['PHP_SELF'] . "?group=lne2010";
			} else {
				echo $_SERVER['PHP_SELF'];
			}
								  ?>">run
								query</a>
						</td>
						<td><?php echo $lne2010patients; ?></td>
					</tr>
				</table>
			</form>
		</div>
		<br />
		<?php
			if ($successmessage != "") {
				echo "<div class='alert-message success'><p><strong>Well done!</strong> ";
				echo "You unlocked the application. ";
				echo "Click <a
			style='color: white; text-decoration: underline' href='index.php'> ";
				echo "<strong>here</strong></a> to go to the main page.</p></div>";
			}
		?>
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