<?php
// This script takes 4 fields in the query string
// "chart" which takes either os or appver. Default: os. Example: chart=appver
// "app" which limits the query to a single application. Default: All applications. Example: app=iFilm. Required when chart=appver
// "days" which changes the number of days of data used. Default: 1 month. Example: days=14
// "color" which changes the color used in the chart. Valid options are: yellow, green, red, purple, blue, mediumGray, pink, aqua, orange, or lightGray. Default: blue. Example: color=red

if (isset($_GET['chart'])) {
	$chart = $_GET['chart'];
} else {
	$chart = "os";
}

if ($chart != "os" AND $chart != "appver") {
	$output["graph"]["error"]["message"] = "chart type not supported";
	$output["graph"]["error"]["detail"] = "To correct: The chart field can only be set to os or appver";
}

if (isset($_GET['app'])) {
	$app = $_GET['app'];
} else if ($chart == "appver") {
	$output["graph"]["error"]["message"] = "app field not specified in query string";
	$output["graph"]["error"]["detail"] = "To correct: add app=YourAppNameHere to the URL for this chart";
}

if (isset($output["graph"]["error"])) {
	encodeAndExit($output);
}

require("profileDB.php");

$end_date = strftime("%Y-%m-%d %H:%M:%S");

if (isset($_GET['days'])) {
	$days = $_GET['days'] . " days";
} else {
	$days = "1 month";
}

$start_timestamp = strtotime("$end_date $days ago");
$start_date = strftime("%Y-%m-%d %H:%M:%S", $start_timestamp);

if (!TryOpenDB()) {
	$output["graph"]["error"]["message"] = "Database error: " . $DbError;
	encodeAndExit($output);
}

// Get earliest profile report
$queryString = "select report_id from profileReport where report_date >= ? order by report_id limit 1";
$stmt = $DbLink->prepare($queryString);
$stmt->bind_param("s", $start_date);
$stmt->execute();
$report_ids = $stmt->get_result();
$stmt->close();
if ($report_ids->num_rows == 0) {
	$output["graph"]["error"]["message"] = "No reports found in this date range";
	encodeAndExit($output);
}
$row = $report_ids->fetch_assoc();
$earliestReportID = $row['report_id'];

// Get latest profile report
$queryString = "select report_id from profileReport where report_date <= ? order by report_id desc limit 1";
$stmt = $DbLink->prepare($queryString);
$stmt->bind_param("s", $end_date);
$stmt->execute();
$report_ids = $stmt->get_result();
$stmt->close();
$row = $report_ids->fetch_assoc();
$latestReportID = $row['report_id'];

if ($chart == "os") {
	if (isset($_GET['app'])) {
		$appsubquery = "and report_id in (select report_id from reportRecord where report_key = 'appName' and report_value=?)";
		$app = $_GET['app'];
		$output["graph"]["datasequences"][0]["title"] = $app;
	} else {
		$output["graph"]["datasequences"][0]["title"] = "All apps";
	}

	$query = "select (case when substr(report_value,1,6) = '10.10.' then 'Yosemite' when substr(report_value,1,5) = '10.9.' then 'Mavericks' when substr(report_value,1,5) = '10.8.' then 'Mountain Lion' when substr(report_value,1,5) = '10.7.' then 'Lion' when substr(report_value,1,5) = '10.6.' then 'Snow Leopard' when substr(report_value,1,5) = '10.5.' then 'Leopard' when substr(report_value,1,5) = '10.4.' then 'Tiger' when substr(report_value,1,5) = '10.3.' then 'Panther' else 'Other' end) p, count(*) c from reportRecord where report_key = 'osVersion' and report_id between $earliestReportID and $latestReportID " . $appsubquery . " group by p order by c desc;";
	$output["graph"]["title"] = "OS Major Version";
}

if ($chart == "appver") {
	$app = $_GET['app'];
	$output["graph"]["datasequences"][0]["title"] = $app;

	$query = "select convert(report_value, unsigned integer) ver, count(*) c from reportRecord where report_key = 'appVersion' and report_id between $earliestReportID and $latestReportID and report_id in (select report_id from reportRecord where report_key = 'appName' and report_value=?) group by report_value order by ver";
	$output["graph"]["title"] = "App Version";
}

$stmt = $DbLink->prepare($query);
if (isset($app)) $stmt->bind_param("s", $app);
$stmt->execute();
$results = $stmt->get_result();
while ($row = $results->fetch_array()) {
	$resultsArray[] = $row;
	$count += $row[1];
}
$output["graph"]["type"] = "bar";
$output["graph"]["yAxis"]["units"]["suffix"] = "%";
$output["graph"]["xAxis"]["showEveryLabel"] = TRUE;
if (isset($_GET['color'])) {
	$color = $_GET['color'];
} else {
	$color = "blue";
}
$output["graph"]["datasequences"][0]["color"] = $color;

$i = 0;
foreach ($resultsArray as $bar) {
	$label = (string) $bar[0];
	$output["graph"]["datasequences"][0]["datapoints"][$i]["title"] = $label;
	$output["graph"]["datasequences"][0]["datapoints"][$i]["value"] = round($bar[1]/$count*100,2);
	$i++;
}

encodeAndExit($output);

function encodeAndExit($a) {
	$json_output = json_encode($a);
	echo $json_output;
	exit();
}
