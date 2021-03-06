<!DOCTYPE html>
<html>
<head>
<title>Sparkle Profile Data Lookup</title>
<link rel="stylesheet" type="text/css" href="style.css">
<meta charset="UTF-8">
</head>
<body>
<?php

require("profileDB.php");
$debug = 0;

/*
   always do a lookup.
   if start and end specified, use them if valid.
   if only end set, use start of 1 month ago
   if neither set, use default start & end
   */
// If end is set, make sure it looks like a date
if (isset($_GET['end']) && (dateValidate($_GET['end']))) {
	$end_date = $_GET['end'];
} else {
	// default end date is now
	$end_date = strftime("%Y-%m-%d %H:%M:%S");
}
// check that start looks like a date
if (isset($_GET['start']) AND dateValidate($_GET['start'])) {
	$start_date = $_GET['start'];
} else {
	// default start date is one month before end date
	$start_timestamp = strtotime("$end_date 1 month ago");
	$start_date = strftime("%Y-%m-%d %H:%M:%S", $start_timestamp);
}

profileLookup();

function dateValidate($date) {
	if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/', $date)) {
		$timestamp = strtotime($date);
		return checkdate(date('m',$timestamp), date('d',$timestamp), date('Y',$timestamp));
	} else {
		return 0;
	}
}

function profileLookupForm() {
}

function profileLookup() {
	global $DbLink, $DbError, $start_date, $end_date, $debug;
	// connect to the database
	if (!TryOpenDB()) {
		abortAndExit();
	}

	// Get REPORT_ID for all reports between specified dates.
	//$start_date = '2006-01-01';
	//$end_date = strftime("%Y-%m-%d %H:%m:%S");

	print "<table id=\"date-table\">\n";
	print "<tr><th>Start date:</th><td>$start_date</td>\n";
	print "<tr><th>End date:</th><td>$end_date</td>\n";
	print "</table>\n";

	$queryString = "select REPORT_ID,REPORT_DATE from profileReport where REPORT_DATE >= ? and REPORT_DATE <= ? ORDER BY REPORT_DATE";
	$stmt = $DbLink->prepare($queryString);
	$stmt->bind_param("ss", $start_date, $end_date);
	// report_ids will be an associative array: keys=report_ids, values=dates
	$stmt->execute();
	$report_ids_lookup = $stmt->get_result();
	$stmt->close();
	if (!$report_ids_lookup) {
		$DbError = $DbLink->error;
		abortAndExit();
		//print "Could not look up row IDs with query $queryString<br />\n";
	}
	if ($report_ids_lookup->num_rows == 0) {
		print "<p>No reports found in this date range</p>\n";
		return;
	}
	while ($row = $report_ids_lookup->fetch_assoc()) {
		//print $row['REPORT_ID'] . ": " . $row['REPORT_DATE'];
		$report_ids[$row['REPORT_ID']] = $row['REPORT_DATE'];
	}
	$report_ids_lookup->free();

	if ($debug) {
		print "Report IDs:<br />\n";
		print_r($report_ids);
		print "<br \>\n";
	}

	// Now dsplay a table of reported data for these REPORT_IDs.
	// Could find keys in advance using "select REPORT_KEY from reportRecord group by REPORT_KEY"
	// knownReportKeys is a (non-associative) array where each entry is a key used in a profile report.
	$knownReportKeysLookup = $DbLink->query("select distinct REPORT_KEY from reportRecord order by REPORT_KEY;");
	if (!$knownReportKeysLookup) {
		$DbError = $DbLink->error;
		abortAndExit();
	}
	while ($row = $knownReportKeysLookup->fetch_array()) {
		$knownReportKeys[] = $row[0];
	}
	$knownReportKeysLookup->free();

	if ($debug) {
		print "known keys:<br />\n";
		print_r($knownReportKeys);
	}
	print "<table id=\"detail-table\"><tr><th>Date</th>\n";
	foreach($knownReportKeys as $reportKey) {
		print "<th>$reportKey</th>\n";
	}
	print "</tr>\n";
	$queryString = "select REPORT_KEY,REPORT_VALUE from reportRecord where REPORT_ID=?";
	$stmt = $DbLink->prepare($queryString);
	while(list($report_id, $report_date) = each($report_ids)) {
		$stmt->bind_param("i",$report_id);
		$stmt->execute();
		// report_records will be an assoc array, keys from knownReportKeys, values with the corresponding value
		$reportRecordsLookup = $stmt->get_result();
		if (!$reportRecordsLookup) {
			$DbError = $DbLink->error;
			abortAndExit();
		}
		while ($row = $reportRecordsLookup->fetch_assoc()) {
			$reportRecords[$row['REPORT_KEY']] = $row['REPORT_VALUE'];
		}
		$reportRecordsLookup->free();
		if ($debug) {
			print "<br />report records: <br />\n";
			print_r($reportRecords);
			print "<br />\n";
		}
		print "<tr><td>$report_date</td>\n";
		foreach($knownReportKeys as $reportKey) {
			if (array_key_exists($reportKey, $reportRecords)) {
				print "<td>" . $reportRecords[$reportKey] . "</td>\n";
			} else {
				print "<td></td>\n";
			}
		}
		print "</tr>\n";
		$reportRecords = array();
	}
	$stmt->close();
	print "</table>\n";

	CloseDB();
}
?>

</body>
</html>

