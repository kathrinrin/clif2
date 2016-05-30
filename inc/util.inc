<?php

ini_set('display_errors', 'Off');
ini_set('display_startup_errors', 'Off');
error_reporting(0);
mb_internal_encoding( 'UTF-8' );

$commentcomment = "Please document all questions that you had, ambiguities and how you resolved them, i.e. why you modelled the indicator the way you did. Also, please give us as much feedback as possible of what you think of the method, what you found easy and what difficult and the reasons for it. Your feedback is valuable for improving both this application and the indicators.";

$db = "localhost";
$db_user = "root";
$db_pass = "";
$dbname = "diabetes";
$patientsdbname = "patients_new";
$snomeddbname = "snomedct_full";

$patienttables = array("patient", "firstpage", "lab_test", "examination", "treatment");
$codecolumns = array("diagnosis_code_SNOMED", "test_code_SNOMED", "examination_code_SNOMED", "treatment_code_SNOMED");


function endsWith($haystack, $needle)
{
	$length = strlen($needle);
	$start  = $length * -1; //negative
	return (substr($haystack, $start) === $needle);
}

function error($error, $query, $step, $userid, $indicatorid)
{
	$message =  "User ID: " . $userid . "\r\n
	Indicator ID: " . $indicatorid . "\r\n
	Error: " . $error . "\r\n
	Query: " . $query . "\r\n
	Step: " . $step . "\r\n
	";

	$to      = 'k.dentler@vu.nl';
	$subject = 'InFo Error';

	$headers = 'From: k.dentler@vu.nl' . "\r\n" .
    'Reply-To: k.dentler@vu.nl' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();

	if (mail($to, $subject, $message, $headers)) {
		// echo "message mailed!!! <br /><br />";
	}
}

function PrepSQL($value)
{

	if(get_magic_quotes_gpc())
	{
		$value = stripslashes($value);
	}

	$value = mysql_real_escape_string($value);
	return($value);
}

function getRelation($relation) {

	if($relation=="less-than") {
		return("<");
	}

	if($relation=="less-than-or-equal-to") {
		return("<=");
	}

	if($relation=="equal-to") {
		return("=");
	}

	if($relation=="not-equal-to") {
		return("!=");
	}

	if($relation=="greater-than-or-equal-to") {
		return(">=");
	}

	if($relation=="greater-than") {
		return(">");
	}

	return("error");
}
?>