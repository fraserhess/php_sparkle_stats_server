<?php
if (array_key_exists('appName', $_GET)) {
	// connect to the database
	if (TryOpenDB()) {
		writeProfileToDB();
	}
}

returnAppcast();
exit();

function writeProfileToDB() {
	global $debug, $DbLink, $DbError, $appcastKeys;

	if ($debug) {
		print "<html><head><title>debug</title></head><body>\n";
	}
	// Record the report
	$report_date = strftime("%Y-%m-%d %H:%M:%S");
	$remote_addr = $_SERVER['REMOTE_ADDR'];
	$queryString = "INSERT INTO profileReport (IP_ADDR, REPORT_DATE) VALUES (?,?)";
	if ($debug) {
		print "$remote_addr<br />";
		print "$report_date<br />";
	}
	$stmt = $DbLink->prepare($queryString);
	$stmt->bind_param("ss", $remote_addr, $report_date);
	$sqlResult = $stmt->execute();
	$stmt->close();
	if (!$sqlResult) {
		$DbError = $DbLink->error;
		abortAndExit();
	}
	$record_id = $DbLink->insert_id;

	// parse the data report
	$queryString = "INSERT INTO reportRecord (REPORT_KEY, REPORT_VALUE, REPORT_ID) VALUES (?,?,?)";
	$stmt = $DbLink->prepare($queryString);
	while (list($key, $value) = each($_GET)) {

	// Date,
		if (array_key_exists($key, $appcastKeys) && $appcastKeys[$key] == 1) {
			if ($debug) {
				print "$key: $value<br />\n";
			}
			$stmt->bind_param("ssi", $key, $value, $record_id);
			$sqlResult = $stmt->execute();
			if (!$sqlResult) {
				$DbError = $DbLink->error;
				abortAndExit();
			}
		}
	}
	$stmt->close();

	CloseDB();
	if ($debug) {
		print "</body>\n";
		exit();
	}
}

function returnAppcast() {
	global $appcastURL;

	header("content-type: application/xhtml+xml");
	$xml = simplexml_load_file($appcastURL);
	echo $xml->asXML();
}
?>