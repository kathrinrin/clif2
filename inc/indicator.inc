 Choose an indicator:

<div class="input">

	<form action="<?php echo $_SERVER['PHP_SELF'] ?>"
		name="indicatorForm" method="post">
		<select name="indicatorSelect"
			onchange="this.form.submit();" class="input-xxlarge shadow_slect">
			<?php
$indicatorid = $_SESSION['indicatorid'];

$indicators = mysql_query("SELECT * FROM indicator WHERE reportingyear = '2014' ORDER BY id") or die(mysql_error());
while ($row = mysql_fetch_array($indicators)) {
	$title = $row['title'];
	$id = $row['id'];

	echo "<option value='$id'";
	if ($indicatorid == $id)
		echo (" selected='selected'");
	echo ">";
	echo $id . ": " . htmlspecialchars($row[title]);
	echo "</option>";
}
			?>
		</select>
	</form>
</div>

			<?php
$indicator = mysql_query("SELECT * FROM indicator WHERE id = '$indicatorid'") or die(mysql_error());
$indicatorrow = mysql_fetch_array($indicator);
$reportingyear = $indicatorrow['reportingyear'];
$title = $indicatorrow['title'];
$numerator = $indicatorrow['numerator'];
$denominator = $indicatorrow['denominator'];
$inclusion = $indicatorrow['inclusion'];
$exclusion = $indicatorrow['exclusion'];
$original = $indicatorrow['link'];
			?>

<div class="well">
	<table style="width: 100%; text-align: left;">

		<tr valign="top">
			<td colspan="2"><b>Indicator: <?php echo $title; ?> </b></td>
		</tr>

		<tr valign="top">
			<td style="width: 15%;">Numerator</td>
			<td style="width: 85%;"><?php echo $numerator; ?></td>
		</tr>
		
		<tr valign="top">
			<td>Denominator</td>
			<td><?php echo $denominator; ?></td>
		</tr>
		
		<tr valign="top">
			<td>Inclusion</td>
			<td><?php echo $inclusion; ?></td>
		</tr>
		
		<tr valign="top">
			<td>Exclusion</td>
			<td><?php echo $exclusion; ?></td>
		</tr>
		
		<tr valign="top">
			<td>Original Version</td>
			<td><a href="<?php echo htmlspecialchars($original); ?>" target="_blank">click here</a>
			</td>
		</tr>

		<tr valign="top">
			<td>Reporting Year</td>
			<td><?php echo $reportingyear; ?></td>
		</tr>

	</table>
</div>
