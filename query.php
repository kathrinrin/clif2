<?php
require_once 'inc/util.inc';
require_once 'inc/queryutil.inc';
session_start();
$_SESSION['error_num'] = "";
$_SESSION['error_denom'] = "";

function concatenateconstraints($constraints, $string) {
	foreach ($constraints as $k => $v) {
		if (is_array($v)) {
			foreach ($v as $key => $val) {
				if (($k != '') || ($key != '')) {
					if (strcmp($string, "") != 0) {
						$string = $string . "\r\n AND ";
					}
					$string = $string . $val;
				}
			}
		}
	}
	return $string;
}

function groupconstraints($constraintsgroup, $more_than_one1, $more_than_one2, $more_than_one3) {

	$constraintsstring = '';
	if (more_than_one($constraintsgroup, $more_than_one1) || more_than_one($constraintsgroup, $more_than_one2)
			|| more_than_one($constraintsgroup, $more_than_one3)) {
		$constraintsstring = "( " . $constraintsgroup[0];
		for ($e = 1; $e < count($constraintsgroup); $e++) {
			$constraintsstring = $constraintsstring . " \r\n  OR " . $constraintsgroup[$e];
		}
		$constraintsstring = $constraintsstring . " )";

	} else {
		$constraintsstring = $constraintsgroup[0];
	}
	return $constraintsstring;
}

function addstring($basestring, $toadd) {
	if ($basestring == "") {
		$basestring = $toadd;
	} else {
		$basestring = $basestring . " \r\n AND " . $toadd . " ";
	}
	return $basestring;
}

function pusharray($array, $querytable, $newconstraint) {
	$constraintsarray = $array[$querytable];
	if (!isset($constraintsarray)) {
		$constraintsarray = array();
	}
	if (!in_array($newconstraint, $constraintsarray)) {
		array_push($constraintsarray, $newconstraint);
		$array[$querytable] = $constraintsarray;
	}
	return $array;
}

function in_multiarray($elem, $array) {
	foreach ($array as $k => $v) {
		if (count($v) > 0) {
			foreach ($v as $key => $val) {
				if ($val == $elem) {
					return true;
				}
			}
		}
	}
}

function more_than_one($array, $string) {
	$count = 0;
	for ($q = 0; $q < count($array); $q++) {
		if (strstr($array[$q], '.`' . $string . '`')) {
			$count = $count + 1;
		}
	}
	if ($count > 1)
		return true;
	return false;
}

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
			$step = "query";
			$userid = $userinfo['id'];
			$run = $_GET["run"];
			if ($_SERVER['REQUEST_METHOD'] == 'POST') {
				if ($_POST['subclassReasoning'] == 'subclassReasoning') {
					$subclassreasoning = "yes";
				} else {
					$subclassreasoning = "no";
				}
			}
			if ($_POST['form'] == "activeconstraints") {
				$allconstraints = mysql_query(
						"SELECT * FROM formalised_constraint WHERE indicatorid = '$indicatorid' AND userid = '$userid'");
				if (!$allconstraints)
					error(mysql_error());
				while ($constraintinfo = mysql_fetch_array($allconstraints)) {
					$id = $constraintinfo['id'];
					$sqlupdate = "UPDATE formalised_constraint SET active = '0', updated = NOW() WHERE id = '$id'";
					mysql_query($sqlupdate);
				}
				mysql_free_result($allconstraints);
				$selconstraints = $_POST['activeconstraints'];
				if (!empty($selconstraints)) {
					$N = count($selconstraints);
					for ($i = 0; $i < $N; $i++) {
						$sqlupdate = "UPDATE formalised_constraint SET active = '1' WHERE id = '$selconstraints[$i]'";
						mysql_query($sqlupdate);
					}
				}
			}
			$select = 'SELECT DISTINCT `patient`.`patientid` ';
			$fromnumerator = "";
			$fromdenominator = "FROM `$patientsdbname`.`patient` \r\n";
			$queryvariableexclusionstringnumerator = "";
			$wherenumerator = "";
			$wheredenominator = "";
			$subselectnumerator = "";
			$subselectdenominator = "";
			$denominatoronly = "";
			$denominatorexclusionsstring = "";
			$numeratorexclusionsstring = "";

			$querytablesnumerator = array();
			$aliastablesnumerator = array(array());
			$querytablesdenominator = array();
			$aliastablesdenominator = array(array());
			$extrajoins = array();
			$exclusions = array(array());
			$denominatorconstraintsbyquerytable = array(array());
			$lastvalues = array(array());
			$numeratorconstraintsbyquerytable = array(array());
			$wheredenominatorconstraintsbyquerytable = array(array());
			$wherenumeratorconstraintsbyquerytable = array(array());
			$datesdenominator = array(array());
			$datesnumerator = array(array());
			$variableexclusionsnumerator = array(array());
			$iswaardennumerator = array(array());
			$iswaardendenominator = array(array());

			$informationmodel = "SELECT * FROM `formalised_constraint` WHERE `indicatorid` = $indicatorid AND `userid` = $userid ORDER BY `constrainttype`";
			$constraintsquery = mysql_query($informationmodel);
			if (!$constraintsquery)
				error(mysql_error());
			while ($constraint = mysql_fetch_array($constraintsquery)) {
				$constrainttype = $constraint['constrainttype'];
				$querytable = $constraint['table'];
				$attribute = $constraint['attribute'];
				$attribute2 = $constraint['attribute2'];
				$conceptid = $constraint['conceptid'];
				$number = $constraint['number'];
				$txt = $constraint['txt'];
				$querytable2 = $constraint['table2'];
				$isexclusion = $constraint['isexclusion'];
				$numeratoronly = $constraint['numeratoronly'];
				$relation = getRelation($constraint['relation']);
				$date = $constraint['date'];
				$boolean = $constraint['boolean'];
				$active = $constraint['active'];
				$lastvalue = $constraint['lastvalue'];
				$newconstraint = "";
				if ((bool) $active) {
					if ($constrainttype == "informationmodel") {
						if ($subclassreasoning == "yes") {
							$newconstraint = "(`$querytable`.`$attribute` = '$conceptid' OR EXISTS (SELECT * FROM `$snomeddbname`.`sct_transitiveclosure` AS tc WHERE `tc`.`SupertypeId` = '$conceptid' AND `tc`.`SubtypeId` = `$querytable`.`$attribute` )) ";
						} else {
							if ($attribute == 'Nummer') {
								$newconstraint = "`$querytable`.`$attribute` = $conceptid ";
							} else if ($attribute == 'ATC_Code') {
								$newconstraint = "`$querytable`.`$attribute` LIKE '%$conceptid%' ";
							} else {
								$newconstraint = "`$querytable`.`$attribute` = '$conceptid' ";
							}
						}
					}
					if ($constrainttype == "numeric") {
						$newconstraint = "`" . $querytable . "`.`" . $attribute . "` " . $relation . " " . $number
								. " ";
					}
					if ($constrainttype == "textual") {
						if ($attribute == 'Waarde') {
							$newconstraint = "`" . $querytable . "`.`" . $attribute . "` " . $relation . " " . $txt
									. " ";
						} else {
							$newconstraint = "`" . $querytable . "`.`" . $attribute . "` " . $relation . " '" . $txt
									. "' ";
						}
					}
					if ($constrainttype == "boolean") {
						$newconstraint = "`" . $querytable . "`.`" . $attribute . "` = " . $boolean . " ";
					}
					if ($constrainttype == "temporal_relation") {
						$newconstraint = "`" . $querytable . "`.`" . $attribute . "` " . $relation . " `"
								. $querytable2 . "`.`" . $attribute2 . "` ";
					}
					if ($constrainttype == "temporal_date") {
						$newconstraint = "`" . $querytable . "`.`" . $attribute . "` " . $relation . " '" . $date
								. "' ";
					}
					if ($constrainttype == "idjoin") {
						$extrajoin = "AND `$querytable`.`$attribute` = `$querytable2`.`$attribute2` \r\n";
						$extrajoins[$querytable] = $extrajoin;
						$extrajoins[$querytable2] = $extrajoin;
					}

					// last value
					if ($lastvalue) {
						$lastvalues = pusharray($lastvalues, $querytable, $newconstraint);
					}

					// numerator
					if ((bool) $numeratoronly && !(bool) $isexclusion && !(bool) $lastvalue) {
						(bool) $added = false;

						// variable exclusions numerator
						$selecttable = "SELECT `isexclusion` FROM `query_variable` WHERE `indicatorid` = $indicatorid AND `userid` = $userid AND `variable` = '$querytable'";
						$fsn = mysql_query($selecttable);
						if (!$fsn)
							error(mysql_error());
						$table_num_rows = mysql_num_rows($fsn);

						if ($table_num_rows > 0) {
							$dbtablerow = mysql_fetch_row($fsn);
							(bool) $variablesexclusion = $dbtablerow[0];
							if ($variablesexclusion) {
								$variableexclusionsnumerator = pusharray($variableexclusionsnumerator, $querytable,
										$newconstraint);
								$added = true;
							}
						}
						if ($constrainttype == "informationmodel" && !$added) {
							$numeratorconstraintsbyquerytable = pusharray($numeratorconstraintsbyquerytable,
									$querytable, $newconstraint);
							$added = true;
						}
						if (($constrainttype == "temporal_date") && ($attribute == "Uitschrijfdatum") && !$added) {
							$datesnumerator = pusharray($datesnumerator, $querytable, $newconstraint);
							$added = true;
						}

						if (($constrainttype == 'textual') && ($attribute == 'Waarde') && ($relation == '=') && !$added) {
							$iswaardennumerator = pusharray($iswaardennumerator, $querytable, $newconstraint);
							$added = true;
						}

						if (!$added) {
							if (($constrainttype != "idjoin") && ($constrainttype != "informationmodel")) {
								$wherenumeratorconstraintsbyquerytable = pusharray(
										$wherenumeratorconstraintsbyquerytable, $querytable, $newconstraint);
								$added = true;
							}
						}
					}
					// exclusions
					if ((bool) $isexclusion) {
						if ($constrainttype != "idjoin")
							$exclusions = pusharray($exclusions, $querytable, $newconstraint);
					}

					// denominator
					if (!(bool) $numeratoronly && !(bool) $isexclusion && !(bool) $lastvalue) {
						(bool) $added = false;

						if (($constrainttype == "informationmodel") && !$added) {
							$denominatorconstraintsbyquerytable = pusharray($denominatorconstraintsbyquerytable,
									$querytable, $newconstraint);
							$added = true;
						}

						if ((($constrainttype == "temporal_date") && (($attribute == "Uitschrijfdatum"))) && !$added) {
							$datesdenominator = pusharray($datesdenominator, $querytable, $newconstraint);
							$added = true;
						}

						if (($constrainttype == 'textual') && ($attribute == 'Waarde') && ($relation == '=') && !$added) {
							$iswaardendenominator = pusharray($iswaardendenominator, $querytable, $newconstraint);
							$added = true;
						}
						if (!$added) {
							$wheredenominatorconstraintsbyquerytable = pusharray(
									$wheredenominatorconstraintsbyquerytable, $querytable, $newconstraint);
							$added = true;
						}
					}
					if ($constrainttype != "idjoin") {

						if ((bool) $numeratoronly) {

							if (!in_array($querytable, $querytablesnumerator) && (isset($querytable))) {
								array_push($querytablesnumerator, $querytable);
							}

							if (!in_array($querytable2, $querytablesnumerator) && (isset($querytable2))) {
								array_push($querytablesnumerator, $querytable2);
							}
						}

						if (!(bool) $numeratoronly) {
							if (!in_array($querytable, $querytablesdenominator) && (isset($querytable)))
								array_push($querytablesdenominator, $querytable);
							if (!in_array($querytable2, $querytablesdenominator) && (isset($querytable2))) {
								array_push($querytablesdenominator, $querytable2);
							}
						}
					}
				}

				$selecttable = "SELECT `table` FROM `query_variable` WHERE `indicatorid` = $indicatorid AND `userid` = $userid AND `variable` = '$querytable'";
				$fsn = mysql_query($selecttable);
				if (!$fsn)
					error(mysql_error());
				$table_num_rows = mysql_num_rows($fsn);

				if ($table_num_rows > 0) {
					$dbtablerow = mysql_fetch_row($fsn);

					if ((bool) $numeratoronly) {
						$aliastablesnumerator = pusharray($aliastablesnumerator, $dbtablerow[0], $querytable);
					}

					if (!(bool) $numeratoronly) {
						$aliastablesdenominator = pusharray($aliastablesdenominator, $dbtablerow[0], $querytable);
					}
				}
				mysql_free_result($fsn);
			}
			mysql_free_result($constraintsquery);

			// numerator
			$fromnumerator = "";

			for ($q = 0; $q < count($querytablesnumerator); $q++) {

				if ((!in_multiarray($querytablesnumerator[$q], $aliastablesnumerator))
						&& ($querytablesnumerator[$q] != "patient")) {

					$newconstraintjoin = " JOIN `$querytablesnumerator[$q]` ON `patient`.`patientid` = `$querytablesnumerator[$q]`.`patientid` \r\n ";
					if (count($extrajoins[$querytablesnumerator[$q]]) > 0) {
						$substrings = explode("`", $extrajoins[$querytablesnumerator[$q]]);
						$subtable1 = $substrings[1];
						$subtable2 = $substrings[5];
						if (((in_array($subtable1, $querytablesnumerator)
								|| in_multiarray($subtable1, $aliastablesnumerator)))
								|| ((in_array($subtable2, $querytablesnumerator)
										|| in_multiarray($subtable2, $aliastablesnumerator)))) {
							$fromnumerator = $fromnumerator . $newconstraintjoin
									. $extrajoins[$querytablesnumerator[$q]];
							unset($extrajoins[$subtable1]);
							unset($extrajoins[$subtable2]);
						} else {
							$fromnumerator = $newconstraintjoin . $fromnumerator;
						}
					} else {
						$fromnumerator = $newconstraintjoin . $fromnumerator;
					}
				}
			}
			foreach ($aliastablesnumerator as $k => $v) {

				$newk = str_replace('bepaling', 'bepaling_subset', $k);

				if (is_array($v)) {

					foreach ($v as $key => $val) {

						if (($newk != '') || ($key != '')) {

							if (!in_multiarray($val, $aliastablesdenominator)) {

								$selecttable = "SELECT `isexclusion` FROM `query_variable` WHERE `indicatorid` = $indicatorid AND `userid` = $userid AND `variable` = '$val'";
								$fsn = mysql_query($selecttable);
								if (!$fsn)
									error(mysql_error());
								$table_num_rows = mysql_num_rows($fsn);

								if ($table_num_rows > 0) {
									$dbtablerow = mysql_fetch_row($fsn);
									$isexclusion = $dbtablerow[0];

									if (!$isexclusion) {

										$newconstraintjoin = " JOIN `$newk` AS `$val` ON `patient`.`patientid` = `$val`.`patientid` \r\n ";
										if (count($extrajoins[$val]) > 0) {
											$substrings = explode("`", $extrajoins[$val]);
											$subtable1 = $substrings[1];
											$subtable2 = $substrings[5];
											if (((in_array($subtable1, $querytablesnumerator)
													|| in_multiarray($subtable1, $aliastablesnumerator)))
													&& ((in_array($subtable2, $querytablesnumerator)
															|| in_multiarray($subtable2, $aliastablesnumerator)))) {
												$fromnumerator = $fromnumerator . $newconstraintjoin
														. $extrajoins[$val];
												unset($extrajoins[$subtable1]);
												unset($extrajoins[$subtable2]);
											} else {
												$fromnumerator = $newconstraintjoin . $fromnumerator;
											}
										} else {
											$fromnumerator = $newconstraintjoin . $fromnumerator;
										}
									}
								}
							}
						}
					}
				}
			}
			// constraints groups
			for ($q = 0; $q < count($querytablesnumerator); $q++) {
				if (count($numeratorconstraintsbyquerytable[$querytablesnumerator[$q]]) > 0) {
					$constraintsgroup = $numeratorconstraintsbyquerytable[$querytablesnumerator[$q]];
					$constraintsstring = groupconstraints($constraintsgroup, 'SNOMED_CT_Code', 'Nummer', 'ATC_Code');
					$wherenumerator = addstring($wherenumerator, $constraintsstring);
				}

				if (count($iswaardennumerator[$querytablesnumerator[$q]]) > 0) {
					$constraintsgroup = $iswaardennumerator[$querytablesnumerator[$q]];
					$constraintsstring = groupconstraints($constraintsgroup, 'Waarde');
					$wherenumerator = addstring($wherenumerator, $constraintsstring);
				}

				if (count($lastvalues[$querytablesnumerator[$q]]) > 0) {
					$conceptgroup = $lastvalues[$querytablesnumerator[$q]];

					if (count($conceptgroup) > 0) {
						$subselectnumerator = $subselectnumerator . "\r\nAND ( ";
					}

					for ($c = 0; $c < count($conceptgroup); $c++) {

						if ($subselectnumerator == "\r\nAND ( ") {
							$subselectnumerator = $subselectnumerator . "( " . $conceptgroup[$c];

						} else {
							$subselectnumerator = $subselectnumerator . "\r\nOR (" . $conceptgroup[$c];
						}

						$conceptgroup[$c] = str_replace($querytablesnumerator[$q], 'bepaling_subset', $conceptgroup[$c]);

						$subselectnumerator = $subselectnumerator . "\r\nAND `" . $querytablesnumerator[$q]
								. "`.`Datum` = \r\n" . "( \r\n" . " SELECT MAX(`Datum`) FROM `bepaling_subset` \r\n"
								. " WHERE `patient`.`patientid` = `bepaling_subset`.`patientid` \r\n" . " AND "
								. $conceptgroup[$c] . "\r\n";

						if (count($wherenumeratorconstraintsbyquerytable[$querytablesnumerator[$q]]) > 0) {
							$constraintgroup = $wherenumeratorconstraintsbyquerytable[$querytablesnumerator[$q]];

							for ($s = 0; $s < count($constraintgroup); $s++) {
								if (preg_match("/`Datum`/", $constraintgroup[$s])) {
									$constraintgroup[$s] = str_replace($querytablesnumerator[$q], 'bepaling_subset',
											$constraintgroup[$s]);
									$subselectnumerator = $subselectnumerator . " AND " . $constraintgroup[$s] . "\r\n";
								}
							}
						}
						$subselectnumerator = $subselectnumerator . "))";
					}

					if (count($conceptgroup) > 0) {
						$subselectnumerator = $subselectnumerator . " )";
					}
				}
			}

			for ($q = 0; $q < count($querytablesnumerator); $q++) {
				if (count($datesnumerator[$querytablesnumerator[$q]]) > 0) {
					$constraintsgroup = $datesnumerator[$querytablesnumerator[$q]];
					$constraintsstring = groupconstraints($constraintsgroup, 'Uitschrijfdatum');
					$wherenumerator = addstring($wherenumerator, $constraintsstring);
				}
			}

			// add simple where numerator constraints
			$wherenumerator = concatenateconstraints($wherenumeratorconstraintsbyquerytable, $wherenumerator);

			// exclusions
			for ($q = 0; $q < count($querytablesnumerator); $q++) {
				if (count($exclusions[$querytablesnumerator[$q]]) > 0) {
					$exclusiongroup = $exclusions[$querytablesnumerator[$q]];
					$connector = "AND";
					if (more_than_one($exclusiongroup, 'SNOMED_CT_Code') || more_than_one($exclusiongroup, 'Nummer')
							|| more_than_one($exclusiongroup, 'Uitschrijfdatum')) {
						$connector = "OR";
					}
					$numeratorexclusionsstringtable = "";
					for ($e = 0; $e < count($exclusiongroup); $e++) {
						if ($e > 0) {
							$numeratorexclusionsstringtable = $numeratorexclusionsstringtable . " \r\n" . $connector
									. " ";
						}
						$numeratorexclusionsstringtable = $numeratorexclusionsstringtable . $exclusiongroup[$e];
					}
					if ($numeratorexclusionsstringtable != "") {
						if ($numeratorexclusionsstring != "") {
							$numeratorexclusionsstring = $numeratorexclusionsstring . " AND ";
						}
						$numeratorexclusionsstring = $numeratorexclusionsstring
								. " NOT ($numeratorexclusionsstringtable) \r\n ";
					}
				}
			}

			// not exist exclusions

			for ($q = 0; $q < count($querytablesnumerator); $q++) {

				if (count($variableexclusionsnumerator[$querytablesnumerator[$q]]) > 0) {
					$exclusiongroup = $variableexclusionsnumerator[$querytablesnumerator[$q]];
					$nummerexclusions = array();

					$table = 'treatment';
					if (strstr(implode($exclusiongroup), '%')) {
						$table = 'diagnosis';
					}

					$queryvariableexclusionstringnumerator = $queryvariableexclusionstringnumerator
							. "\r\n AND NOT EXISTS \r\n" . "(SELECT `patientid` \r\n " . "FROM `$table` AS `"
							. $querytablesnumerator[$q] . "` \r\n";

					$exclusionwhere = "WHERE `" . $querytablesnumerator[$q]
							. "`.`patientid` = `patient`.`patientid`";

					for ($e = 0; $e < count($exclusiongroup); $e++) {

						if (strstr($exclusiongroup[$e], 'Nummer') || strstr($exclusiongroup[$e], 'ATC_Code')) {
							array_push($nummerexclusions, $exclusiongroup[$e]);
						} else {
							$exclusionwhere = $exclusionwhere . " \r\n  AND " . $exclusiongroup[$e];
						}
					}

					if (more_than_one($nummerexclusions, 'Nummer') || more_than_one($nummerexclusions, 'ATC_Code')) {
						$exclusionor = "( ";
						for ($f = 0; $f < count($nummerexclusions); $f++) {
							if ($exclusionor == '( ') {
								$exclusionor = $exclusionor . $nummerexclusions[$f];
							} else {
								$exclusionor = $exclusionor . " \r\n OR " . $nummerexclusions[$f];
							}
						}
						$exclusionor = $exclusionor . " )";
						$exclusionwhere = $exclusionwhere . "\r\nAND " . $exclusionor . "\r\n";
					} else if (count($nummerexclusions) == 1) {
						$exclusionwhere = $exclusionwhere . "AND " . $nummerexclusions[0] . "\r\n";
					}

					$queryvariableexclusionstringnumerator = $queryvariableexclusionstringnumerator . $exclusionwhere
							. ")";
				}
			}

			// denominator
			$alltablesfromdenominator = "";
			for ($q = 0; $q < count($querytablesdenominator); $q++) {
				if ((!in_multiarray($querytablesdenominator[$q], $aliastablesdenominator))
						&& (strcmp($querytablesdenominator[$q], "patient") != 0)) {
					$newconstraintjoin = " JOIN `$querytablesdenominator[$q]` ON `patient`.`patientid` = `$querytablesdenominator[$q]`.`patientid` \r\n ";
					if (count($extrajoins[$querytablesdenominator[$q]]) > 0) {
						$substrings = explode("`", $extrajoins[$querytablesdenominator[$q]]);
						$subtable1 = $substrings[1];
						$subtable2 = $substrings[5];
						if ((!in_array($subtable1, $querytablesnumerator))
								&& (!in_multiarray($subtable1, $aliastablesnumerator))
								&& (!in_array($subtable2, $querytablesnumerator))
								&& (!in_multiarray($subtable2, $aliastablesnumerator))
								&& ((in_array($subtable1, $querytablesdenominator)
										|| in_multiarray($subtable1, $aliastablesdenominator)))
								&& ((in_array($subtable2, $querytablesdenominator)
										|| in_multiarray($subtable2, $aliastablesdenominator)))) {
							$alltablesfromdenominator = $alltablesfromdenominator . $newconstraintjoin
									. $extrajoins[$querytablesdenominator[$q]];
							unset($extrajoins[$subtable1]);
							unset($extrajoins[$subtable2]);
						} else {
							$alltablesfromdenominator = $newconstraintjoin . $alltablesfromdenominator;
						}
					} else {
						$alltablesfromdenominator = $newconstraintjoin . $alltablesfromdenominator;
					}
				}
			}
			foreach ($aliastablesdenominator as $k => $v) {

				$k = str_replace('bepaling', 'bepaling_subset', $k);
				if (is_array($v)) {
					foreach ($v as $key => $val) {

						if (($k != '') || ($key != '')) {
							$newconstraintjoin = " JOIN `$k` AS `$val` ON `patient`.`patientid` = `$val`.`patientid` \r\n ";
							if (count($extrajoins[$val]) > 0) {
								$substrings = explode("`", $extrajoins[$val]);
								$subtable1 = $substrings[1];
								$subtable2 = $substrings[5];
								if ((!in_array($subtable1, $querytablesnumerator))
										&& (!in_multiarray($subtable1, $aliastablesnumerator))
										&& (!in_array($subtable2, $querytablesnumerator))
										&& (!in_multiarray($subtable2, $aliastablesnumerator))
										&& ((in_array($subtable1, $querytablesdenominator)
												|| in_multiarray($subtable1, $aliastablesdenominator)))
										&& ((in_array($subtable2, $querytablesdenominator)
												|| in_multiarray($subtable2, $aliastablesdenominator)))) {
									$alltablesfromdenominator = $alltablesfromdenominator . $newconstraintjoin
											. $extrajoins[$val];
									unset($extrajoins[$subtable1]);
									unset($extrajoins[$subtable2]);
								} else {
									$alltablesfromdenominator = $newconstraintjoin . $alltablesfromdenominator;
								}
							} else {
								$alltablesfromdenominator = $newconstraintjoin . $alltablesfromdenominator;
							}
						}
					}
				}
			}
			$fromdenominator = $fromdenominator . $alltablesfromdenominator;

			// add simple where denominator constraints
			$wheredenominator = concatenateconstraints($wheredenominatorconstraintsbyquerytable, $wheredenominator);

			for ($q = 0; $q < count($querytablesdenominator); $q++) {

				// concept groups
				if (count($denominatorconstraintsbyquerytable[$querytablesdenominator[$q]]) > 0) {
					$constraintsgroup = $denominatorconstraintsbyquerytable[$querytablesdenominator[$q]];
					$constraintsstring = groupconstraints($constraintsgroup, 'SNOMED_CT_Code', 'Nummer', 'ATC_Code');
					$wheredenominator = addstring($wheredenominator, $constraintsstring);
				}

				if (count($lastvalues[$querytablesdenominator[$q]]) > 0) {

					$nummerconstraints = array();
					$conceptgroup = $lastvalues[$querytablesdenominator[$q]];

					if (count($conceptgroup) > 0) {
						$subselectdenominator = $subselectdenominator . "\r\nAND ( ";
					}

					for ($e = 0; $e < count($conceptgroup); $e++) {

						$qt = substr($conceptgroup[$e], strpos($conceptgroup[0], '`') + 1,
								strrpos($conceptgroup[$e], '.') - 2);

						if (!count($wherenumeratorconstraintsbyquerytable[$qt]) > 0) {

							// 							$subselectdenominator = $subselectdenominator . "\r\nAND " . $conceptgroup[$e];

							if ($subselectdenominator == "\r\nAND ( ") {
								$subselectdenominator = $subselectdenominator . "( " . $conceptgroup[$e];

							} else {
								$subselectdenominator = $subselectdenominator . "\r\nOR (" . $conceptgroup[$e];
							}

							$conceptgroup[$e] = str_replace($querytablesdenominator[$q], 'bepaling_subset',
									$conceptgroup[$e]);

							$subselectdenominator = $subselectdenominator . "\r\nAND `" . $querytablesdenominator[$q]
									. "`.`Datum` = \r\n" . "( \r\n"
									. " SELECT MAX(`Datum`) FROM `bepaling_subset` \r\n"
									. " WHERE `patient`.`patientid` = `bepaling_subset`.`patientid` \r\n"
									. " AND " . $conceptgroup[$e] . "\r\n";

							if (count($wheredenominatorconstraintsbyquerytable[$querytablesdenominator[$q]]) > 0) {
								$constraintgroup = $wheredenominatorconstraintsbyquerytable[$querytablesdenominator[$q]];

								for ($s = 0; $s < count($constraintgroup); $s++) {
									if (preg_match("/`Datum`/", $constraintgroup[$s])) {
										$constraintgroup[$s] = str_replace($querytablesdenominator[$q],
												'bepaling_subset', $constraintgroup[$s]);
										$subselectdenominator = $subselectdenominator . " AND " . $constraintgroup[$s]
												. "\r\n";
									}
								}
							}
							$subselectdenominator = $subselectdenominator . "))";

						} else {
							if (in_multiarray($querytablesdenominator[$q], $aliastablesdenominator)) {
								array_push($nummerconstraints, $conceptgroup[$e]);
							}
						}
					}

					if (count($conceptgroup) > 0) {
						$subselectdenominator = $subselectdenominator . " )";
					}

					if (count($nummerconstraints) > 0) {
						$nummerstring = "( ";

						for ($n = 0; $n < count($nummerconstraints); $n++) {

							if ($nummerstring == '( ') {
								$nummerstring = $nummerstring . $nummerconstraints[$n];
							} else
								$nummerstring = $nummerstring . " \r\n OR " . $nummerconstraints[$n];
						}

						$nummerstring = $nummerstring . " )";

						$denominatoronly = " \r\n AND " . $nummerstring . " ";

					} else if (count($nummerconstraints) == 1) {
						$denominatoronly = $denominatoronly . " \r\n AND " . $nummerconstraints[0] . " ";
					}
				}
			}

			for ($q = 0; $q < count($querytablesdenominator); $q++) {
				// date groups
				if (count($datesdenominator[$querytablesdenominator[$q]]) > 0) {
					$constraintsgroup = $datesdenominator[$querytablesdenominator[$q]];
					$constraintsstring = groupconstraints($constraintsgroup, 'SNOMED_CT_Code', 'Uitschrijfdatum');
					$wheredenominator = addstring($wheredenominator, $constraintsstring);
				}

				// value groups
				if (count($iswaardendenominator[$querytablesdenominator[$q]]) > 0) {
					$constraintsgroup = $iswaardendenominator[$querytablesdenominator[$q]];
					$constraintsstring = groupconstraints($constraintsgroup, 'Waarde');
					$wheredenominator = addstring($wheredenominator, $constraintsstring);
				}
			}
			// exclusions
			for ($q = 0; $q < count($querytablesdenominator); $q++) {
				if (count($exclusions[$querytablesdenominator[$q]]) > 0) {
					$exclusiongroup = $exclusions[$querytablesdenominator[$q]];
					$connector = "AND";
					if (more_than_one($exclusiongroup, 'SNOMED_CT_Code') || more_than_one($exclusiongroup, 'Nummer')
							|| more_than_one($exclusiongroup, 'Uitschrijfdatum')) {
						$connector = "OR";
					}
					$denominatorexclusionsstringtable = "";
					for ($e = 0; $e < count($exclusiongroup); $e++) {
						if ($e > 0) {
							$denominatorexclusionsstringtable = $denominatorexclusionsstringtable . " \r\n"
									. $connector . " ";
						}
						$denominatorexclusionsstringtable = $denominatorexclusionsstringtable . $exclusiongroup[$e];
					}
					if ($denominatorexclusionsstringtable != "") {
						if ($denominatorexclusionsstring != "") {
							$denominatorexclusionsstring = $denominatorexclusionsstring . " AND ";
						}
						$denominatorexclusionsstring = $denominatorexclusionsstring
								. " NOT ($denominatorexclusionsstringtable) \r\n ";

					}
				}
			}

			if ($wheredenominator != "") {
				$wheredenominator = " WHERE " . $wheredenominator;
			}
			if (($wheredenominator == "") && ($denominatorexclusionsstring != "")) {
				$denominatorexclusionsstring = " WHERE " . $denominatorexclusionsstring;
			}
			if (($wherenumerator == "") && ($numeratorexclusionsstring != "")) {
				$numeratorexclusionsstring = " WHERE " . $numeratorexclusionsstring;
			}
			if (($wheredenominator != "") && ($wherenumerator != "")) {
				$wherenumerator = " \r\n AND " . $wherenumerator;
			}
			if ((($wheredenominator != "") || ($wherenumerator != "")) && ($denominatorexclusionsstring != "")
					&& (!preg_match("/^[AND]/", $denominatorexclusionsstring))) {
				$denominatorexclusionsstring = " \r\n AND " . $denominatorexclusionsstring;
			}
			if ((($wheredenominator != "") || ($wherenumerator != "")) && ($numeratorexclusionsstring != "")
					&& (!preg_match("/^[AND]/", $numeratorexclusionsstring))) {
				$numeratorexclusionsstring = " \r\n AND " . $numeratorexclusionsstring;
			}

			if (($wheredenominator == "") && ($wherenumerator != "")) {
				$wherenumerator = "WHERE " . $wherenumerator;
			}

			$numeratorsql = $select . $fromdenominator . $fromnumerator . $wheredenominator . $wherenumerator
					. $subselectdenominator . $subselectnumerator . $numeratorexclusionsstring
					. $denominatorexclusionsstring . $queryvariableexclusionstringnumerator;

			// 			echo "<br />";
			// 			echo "<br />";
			// 			echo "<br />";
			// 			echo "<br />";
			// 			echo $numeratorsql;

			$denominatorsql = $select . $fromdenominator . $wheredenominator . $subselectdenominator . $denominatoronly
					. $denominatorexclusionsstring;

			// 			echo "<br />";
			// 			echo "<br />";
			// 			echo $denominatorsql;

			if ($_POST['submitQuery'] == "submit") {
				mysql_select_db($patientsdbname);
				$result = mysql_query($numeratorsql);

				if (!$result) {
					error(mysql_error(), $numeratorsql, $step, $userid, $indicatorid);
					$_SESSION['error_num'] = mysql_error();
				}
				$numberautonumerator = mysql_num_rows($result);
				$result = mysql_query($denominatorsql);

				if (!$result) {
					error(mysql_error(), $denominatorsql, $step, $userid, $indicatorid);
					$_SESSION['error_denom'] = mysql_error();
				}
				$numberautodenominator = mysql_num_rows($result);
				mysql_free_result($autodenominatorresult);
				$autorate = ($numberautonumerator / $numberautodenominator);
				$rate = $autorate * 100;
				$autoresult = "Numerator: " . $numberautonumerator . " patients / Denominator: "
						. $numberautodenominator . " patients = " . round($rate, 2) . "%";
				mysql_select_db($dbname);
			}
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
						$commentsql = "INSERT INTO comment (userid, indicatorid, step, comment, inserted) VALUES (
						$userid, $indicatorid, '$step', '$commentnewprep', NOW())";
						mysql_query($commentsql);
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
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>CLIF: Query</title>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet"></link>
<script type="text/javascript" src="http://code.jquery.com/jquery-latest.js"></script>
<script type="text/javascript" src="js/bootstrap.min.js"></script>
</head>
<body>
	<?php include_once("inc/head.inc") ?>
	<div style="height: 50px"></div>
	 <div class="container">
		<h1>CLIF: Query</h1>
		<p>This page shows the query you constructed. You can run it by
			clicking on the button on the bottom of this page. Constraints in red
			are exclusion criteria and those in green only aim at the numerator. You can enable the "subclass search", which allows you to retrieve not only patients with the exact SNOMED CT concepts that you defined, but also patients with subconcepts of these concepts.
		</p>
		<?php include_once("inc/indicator.inc") ?>
		<p>Below, the constraints are listed. A constraint is active and thus
			checked by default, and the query is built based on all active
			constraints. You can deselect constraints to make them inactive, so
			that you can run the query without them. This is useful in case you
			want to test which constraint is causing an error or leads to zero
			retrieved patients.</p>
		<a name="constraints"></a>
		<form action="<?php echo $_SERVER['PHP_SELF'] . '#constraints' ?>"
			method="post">
			<h3>
				<a href="informationmodel.php">Information model</a>
			</h3>
			<?php
			$content = mysql_query(
					"SELECT * FROM formalised_constraint WHERE userid = '$userid' AND indicatorid = '$indicatorid' AND constrainttype = 'informationmodel'");
			if (!$content)
				error(mysql_error());
			while ($row = mysql_fetch_array($content)) {
				$id = $row['id'];
				$conceptid = $row['conceptid'];
				$table = $row['table'];
				$attribute = $row['attribute'];
				$isexclusion = $row['isexclusion'];
				$numeratoronly = $row['numeratoronly'];
				$active = $row['active'];
				$color = "black";
				if (!(bool) $active)
					$color = "gray";
				if (((bool) $isexclusion) && ((bool) $active))
					$color = "red";
				if (((bool) $isexclusion) && (!((bool) $active)))
					$color = "DarkSalmon";
				if (((bool) $numeratoronly) && ((bool) $active))
					$color = "green";
				if (((bool) $numeratoronly) && (!((bool) $active)))
					$color = "DarkSeaGreen";
				$fsn = mysql_query("SELECT FULLYSPECIFIEDNAME FROM concepts WHERE CONCEPTID = '$conceptid' ");
				if (!$fsn)
					error(mysql_error());
				$snorow = mysql_fetch_row($fsn);
				mysql_free_result($fsn);
				echo "<input type='checkbox' name='activeconstraints[]' value='$id' onchange='this.form.submit();'";
				if ((bool) $active) {
					echo " checked = 'checked'";
				}
				echo " />&nbsp;<span style='color:$color' >";
				echo "$table.$attribute = $conceptid $snorow[0]";
				if ((bool) $isexclusion)
					echo " (<a href='exclusion.php'>exclusion criterion</a>) ";
				if ((bool) $numeratoronly)
					echo " (<a href='numerator.php'>numerator only</a>) ";
				echo "</span><br />";
			}
			mysql_free_result($content);
			?>
			<?php
			$content = mysql_query(
					"SELECT * FROM formalised_constraint WHERE userid = '$userid' AND indicatorid = '$indicatorid' AND constrainttype = 'idjoin'");
			if (!$content)
				error(mysql_error());
			while ($row = mysql_fetch_array($content)) {
				$id = $row['id'];
				$table = $row['table'];
				$attribute = $row['attribute'];
				$table2 = $row['table2'];
				$attribute2 = $row['attribute2'];
				$isexclusion = $row['isexclusion'];
				$active = $row['active'];
				$color = "black";
				if (!(bool) $active)
					$color = "gray";
				if (((bool) $isexclusion) && ((bool) $active))
					$color = "red";
				if (((bool) $isexclusion) && (!((bool) $active)))
					$color = "DarkSalmon";
				if (((bool) $numeratoronly) && ((bool) $active))
					$color = "green";
				if (((bool) $numeratoronly) && (!((bool) $active)))
					$color = "DarkSeaGreen";
				$fsn = mysql_query("SELECT FULLYSPECIFIEDNAME FROM concepts WHERE CONCEPTID = '$conceptid' ");
				if (!$fsn)
					error(mysql_error());
				$snorow = mysql_fetch_row($fsn);
				mysql_free_result($fsn);
				echo "<input type='checkbox' name='activeconstraints[]' value='$id' onchange='this.form.submit();'";
				if ((bool) $active) {
					echo " checked = 'checked'";
				}
				echo " />&nbsp;<span style='color:$color' >";
				echo "$table.$attribute&nbsp;=&nbsp;$table2.$attribute2";
				if ((bool) $isexclusion)
					echo " (<a href='exclusion.php'>exclusion criterion</a>) ";
				if ((bool) $numeratoronly)
					echo " (<a href='numerator.php'>numerator only</a>)";
				echo "</span><br />";
			}
			mysql_free_result($content);
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
				$isexclusion = $dateconstraintrow['isexclusion'];
				$numeratoronly = $dateconstraintrow['numeratoronly'];
				$active = $dateconstraintrow['active'];
				$color = "black";
				if (!(bool) $active)
					$color = "gray";
				if (((bool) $isexclusion) && ((bool) $active))
					$color = "red";
				if (((bool) $isexclusion) && (!((bool) $active)))
					$color = "DarkSalmon";
				if (((bool) $numeratoronly) && ((bool) $active))
					$color = "green";
				if (((bool) $numeratoronly) && (!((bool) $active)))
					$color = "DarkSeaGreen";
				echo "<input type='checkbox' name='activeconstraints[]' value='$id' onchange='this.form.submit();'";
				if ((bool) $active) {
					echo " checked = 'checked'";
				}
				echo " />&nbsp;<span style='color:$color' >";
				echo "$table.$attribute&nbsp;$relation&nbsp;$date&nbsp;";
				if ((bool) $isexclusion)
					echo " (<a href='exclusion.php'>exclusion criterion</a>)";
				if ((bool) $numeratoronly)
					echo " (<a href='numerator.php'>numerator only</a>)";
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
				$isexclusion = $daterelationconstraintrow['isexclusion'];
				$numeratoronly = $daterelationconstraintrow['numeratoronly'];
				$active = $daterelationconstraintrow['active'];
				$color = "black";
				if (!(bool) $active)
					$color = "gray";
				if (((bool) $isexclusion) && ((bool) $active))
					$color = "red";
				if (((bool) $isexclusion) && (!((bool) $active)))
					$color = "DarkSalmon";
				if (((bool) $numeratoronly) && ((bool) $active))
					$color = "green";
				if (((bool) $numeratoronly) && (!((bool) $active)))
					$color = "DarkSeaGreen";
				echo "<input type='checkbox' name='activeconstraints[]' value='$id' onchange='this.form.submit();'";
				if ((bool) $active) {
					echo " checked = 'checked'";
				}
				echo " />&nbsp;<span style='color:$color' >";
				echo "$table.$attribute&nbsp;$relation&nbsp;$table2.$attribute2&nbsp;";
				if ((bool) $isexclusion)
					echo " (<a href='exclusion.php'>exclusion criterion</a>) ";
				if ((bool) $numeratoronly)
					echo " (<a href='numerator.php'>numerator only</a>) ";
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
				$isexclusion = $numericconstraintrow['isexclusion'];
				$numeratoronly = $numericconstraintrow['numeratoronly'];
				$active = $numericconstraintrow['active'];
				$color = "black";
				if (!(bool) $active)
					$color = "gray";
				if (((bool) $isexclusion) && ((bool) $active))
					$color = "red";
				if (((bool) $isexclusion) && (!((bool) $active)))
					$color = "DarkSalmon";
				if (((bool) $numeratoronly) && ((bool) $active))
					$color = "green";
				if (((bool) $numeratoronly) && (!((bool) $active)))
					$color = "DarkSeaGreen";
				echo "<input type='checkbox' name='activeconstraints[]' value='$id' onchange='this.form.submit();'";
				if ((bool) $active) {
					echo " checked = 'checked'";
				}
				echo " />&nbsp;<span style='color:$color' >";
				echo "$table.$attribute&nbsp;$relation&nbsp;$number&nbsp;";
				if ((bool) $isexclusion)
					echo " (<a href='exclusion.php'>exclusion criterion</a>) ";
				if ((bool) $numeratoronly)
					echo " (<a href='numerator.php'>numerator only</a>) ";
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
				$active = $textualconstraintrow['active'];
				$numeratoronly = $textualconstraintrow['numeratoronly'];
				$color = "black";
				if (!(bool) $active)
					$color = "gray";
				if (((bool) $isexclusion) && ((bool) $active))
					$color = "red";
				if (((bool) $isexclusion) && (!((bool) $active)))
					$color = "DarkSalmon";
				if (((bool) $numeratoronly) && ((bool) $active))
					$color = "green";
				if (((bool) $numeratoronly) && (!((bool) $active)))
					$color = "DarkSeaGreen";
				echo "<input type='checkbox' name='activeconstraints[]' value='$id' onchange='this.form.submit();'";
				if ((bool) $active) {
					echo " checked = 'checked' ";
				}
				echo " />&nbsp;<span style='color:$color' >";
				echo "$table.$attribute&nbsp;$relation&nbsp;$txt";
				if ((bool) $isexclusion)
					echo " (<a href='exclusion.php'>exclusion criterion</a>) ";
				if ((bool) $numeratoronly)
					echo " (<a href='numerator.php'>numerator only</a>) ";
				echo "</span><br />";
			}
			mysql_free_result($textualconstraint);
					?>
					
						<?php
			$booleanconstraint = mysql_query(
					"SELECT * FROM formalised_constraint WHERE indicatorid = '$indicatorid' AND userid = '$userid' AND constrainttype = 'boolean'");
			if (!$booleanconstraint)
				error(mysql_error());
			$boolean_num_rows = mysql_num_rows($booleanconstraint);
			if ($boolean_num_rows > 0) {
				echo "<h3><a href='boolean.php'>Boolean constraints</a></h3>";
			}
			while ($booleanconstraintrow = mysql_fetch_array($booleanconstraint)) {
				$id = $booleanconstraintrow['id'];
				$table = $booleanconstraintrow['table'];
				$attribute = $booleanconstraintrow['attribute'];
				$boolean = $booleanconstraintrow['boolean'];
				$isexclusion = $booleanconstraintrow['isexclusion'];
				$numeratoronly = $booleanconstraintrow['numeratoronly'];
				$active = $booleanconstraintrow['active'];
				$color = "black";
				if (!(bool) $active)
					$color = "gray";
				if (((bool) $isexclusion) && ((bool) $active))
					$color = "red";
				if (((bool) $isexclusion) && (!((bool) $active)))
					$color = "DarkSalmon";
				if (((bool) $numeratoronly) && ((bool) $active))
					$color = "green";
				if (((bool) $numeratoronly) && (!((bool) $active)))
					$color = "DarkSeaGreen";
				echo "<input type='checkbox' name='activeconstraints[]' value='$id' onchange='this.form.submit();'";
				if ((bool) $active) {
					echo " checked = 'checked'";
				}
				echo " />&nbsp;<span style='color:$color' >";
				echo "$table.$attribute&nbsp;=&nbsp;$boolean&nbsp;";
				if ((bool) $isexclusion)
					echo " (<a href='exclusion.php'>exclusion criterion</a>) ";
				if ((bool) $numeratoronly)
					echo " (<a href='numerator.php'>numerator only</a>) ";
				echo "</span><br />";
			}
			mysql_free_result($booleanconstraint);
						?>
			<input type="hidden" name="form" value="activeconstraints" />
		</form>
		<div style="height: 20px"></div>
		<a name="subclasses"></a>
		<form action="<?php echo $_SERVER['PHP_SELF'] . '#subclasses' ?>"
			method="post">
			
			<div>
				<input type="checkbox" name="subclassReasoning"
					id="subclassReasoning" value="subclassReasoning"
					onchange="this.form.submit();"
					<?php if ($subclassreasoning == "yes") {
				echo " checked='checked' ";
			}
					?> /> Enable querying for
				subclasses (takes a little longer; queries also for subconcepts of
				the defined concepts).
			</div>
		
			<div class="workwell">
				<div style="margin-left: 10px;">
					<h3>Constructed Query (Numerator constraints green, exclusion
						criteria red):</h3>
					<tt>
						<?php $query = $select . "<br />";
			$query = $query . $fromdenominator . $fromdenominatorjoins . "<span style='color:green'>" . $fromnumerator
					. "</span><br />";
			$query = $query . htmlspecialchars($wheredenominator) . "<span style='color:green'>"
					. htmlspecialchars($wherenumerator) . "</span>";
			$query = $query . "<span style='color:black'><br />" . $subselectdenominator . "</span>";
			$query = $query . "<span style='color:green'><br />" . $subselectnumerator . "</span>";
			$query = $query . "<span style='color:red'><br />" . $denominatorexclusionsstring . "</span>";
			$query = $query . "<span style='color:red'><br />" . $numeratorexclusionsstring . "</span>";
			$query = $query . "<span style='color:red'><br />" . $queryvariableexclusionstringnumerator
					. "</span><br /><br />";

			echo str_replace("\r\n", "<br />", $query);
						?>
					</tt>
					<button type="submit" class="btn small primary" name="submitQuery"
						value="submit">Run constructed query</button>
					<?php echo $autoresult; ?>
					<br /> <span style="color: red"> <?php
			if ($_SESSION['error_num'] != "") {
				echo "<br /> Error Numerator: " . $_SESSION['error_num'];
			}
			if ($_SESSION['error_denom'] != "") {
				echo "<br />Error Denominator: " . $_SESSION['error_denom'];
			}
													 ?>
					</span>
				</div>
			</div>
		</form>
		<div style="height: 10px"></div>
		In case that the query causes an error, read the error message above
		carefully. You probably forgot to select relevant parts that belong to
		the information model. If your query retrieved zero results, this does
		not mean that the query is incorrect. You might query for different
		SNOMED CT concepts than those that occur in the patient data, but
		which might be fitting the indicator text better. Other reasons might
		be that you did not exclude exclusion criteria yet, or that you did not enable the subclass search. You can do that
		<a href="exclusion.php">here</a>.
		<div style="height: 10px"></div>
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