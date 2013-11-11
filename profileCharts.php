<html>
<head>
<title>Sparkle Profile Charts</title>
<link rel="stylesheet" type="text/css" href="style.css">

</head>
<body>
<script>
window.onload=function(){var e=document.getElementsByClassName("chart-table");var t=e.length;for(var n=0;n<t;n++){var r=e[n];var i=r.rows[0];var s=1;for(var o=0;o<i.cells.length;o++){var u=i.cells[o].offsetWidth;if(u>s){s=u}}for(var o=0;o<i.cells.length;o++){i.cells[o].style.width=s+"px"}}}
</script>
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

profileCharts();

CloseDB();

echo "</body>\n";
echo "</html>\n";

function dateValidate($date) {
	if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/', $date)) {
		$timestamp = strtotime($date);
		return checkdate(date('m',$timestamp), date('d',$timestamp), date('Y',$timestamp));
	} else {
		return 0;
	}
}

function profileCharts() {
	global $DbLink, $DbError, $start_date, $end_date, $debug;
	// connect to the database
	if (!TryOpenDB()) {
		abortAndExit();
	}
	
	echo "<table id=\"date-table\">\n";
	echo "<tr><th>Start date:</th><td>$start_date</td>\n";
	echo "<tr><th>End date:</th><td>$end_date</td>\n";
	echo "</table>\n";
	
	// Get earliest profile report
	$queryString ="select report_id from profileReport where report_date >= ? order by report_id limit 1";
	$stmt = $DbLink->prepare($queryString);
	$stmt->bind_param("s", $start_date);
	$stmt->execute();
	$report_ids = $stmt->get_result();
	$stmt->close();
	if ($report_ids->num_rows == 0) {
		print "<p>No reports found in this date range</p>\n";
		return;
	}
	$row = $report_ids->fetch_assoc();
	$earliestReportID = $row['report_id'];

	// Get applications
	$queryString = "select distinct report_value from reportRecord where report_key = 'appName' and report_id >=$earliestReportID order by report_value";
	$distinctAppsLookup = $DbLink->query($queryString);
	if (!$distinctAppsLookup) {
		$DbError = $DbLink->error;
		abortAndExit();
	}
	while ($row = $distinctAppsLookup->fetch_array()) {
		$distinctApps[] = $row[0];
	}
	$distinctAppsLookup->free();
	
	// App version
	$charts['appVer']['heading'] = "App Versions";
	$charts['appVer']['query'] = "select convert(report_value, unsigned integer) ver, count(*) c from reportRecord where report_key = 'appVersion' and report_id >= $earliestReportID and report_id in (select report_id from reportRecord where report_key = 'appName' and report_value=?) group by report_value order by ver";

	$charts['os']['heading'] = "OS Version";
	$charts['os']['query'] = "select report_value p, count(*) c from reportRecord where report_key = 'osVersion' and report_id >= $earliestReportID and report_id in (select report_id from reportRecord where report_key = 'appName' and report_value=?) group by p order by c desc;";
	
	$charts['majos']['heading'] = "OS Major Version";
	$charts['majos']['query'] = "select (case when substr(report_value,1,5) = '10.9.' then 'Mavericks' when substr(report_value,1,5) = '10.8.' then 'Mountain Lion' when substr(report_value,1,5) = '10.7.' then 'Lion' when substr(report_value,1,5) = '10.6.' then 'Snow Leopard' when substr(report_value,1,5) = '10.5.' then 'Leopard' else 'Other' end) p, count(*) c from reportRecord where report_key = 'osVersion' and report_id >= $earliestReportID and report_id in (select report_id from reportRecord where report_key = 'appName' and report_value=?) group by p order by c desc;";

	// CPU Type
	$charts['cpuType']['heading'] = "CPU Type";
	$charts['cpuType']['query'] = "select (case when report_value = 7 then 'Intel' when report_value = 18 then 'PowerPC' end) cputype, count(*) c from reportRecord where report_key = 'cputype' and report_id >= $earliestReportID and report_id in (select report_id from reportRecord where report_key = 'appName' and report_value=?) group by report_value order by report_value desc";

	// CPU 64 bit
	$charts['64bit']['heading'] = "64 bit CPU";
	$charts['64bit']['query'] = "select (case when report_value = 0 then 'No' when report_value = 1 then 'Yes' end) 'sixtyfour', count(*) c from reportRecord where report_key = 'cpu64bit' and report_id >= $earliestReportID and report_id in (select report_id from reportRecord where report_key = 'appName' and report_value=?) group by report_value order by report_value desc";

	// CPU speed
	$charts['cpuSpeed']['heading'] = "CPU Speed (GHz)";
	$charts['cpuSpeed']['query'] = "select trim(trailing '0' from round(convert (report_value, unsigned integer),-1)/1000) speed, count(*) c from reportRecord where report_key = 'cpuFreqMHz' and report_id >= $earliestReportID and report_id in (select report_id from reportRecord where report_key = 'appName' and report_value=?) group by speed order by speed";

	// CPU Count
	$charts['cpuCount']['heading'] = "CPU Count";
	$charts['cpuCount']['query'] = "select convert(report_value,unsigned integer) p, count(*) c from reportRecord where report_key = 'ncpu' and report_id >= $earliestReportID and report_id in (select report_id from reportRecord where report_key = 'appName' and report_value=?) group by p order by p;";

	$charts['language']['heading'] = "Language";
	$charts['language']['query'] = "select substring_index(report_value,'-',1) lang, count(*) c from reportRecord where report_key = 'lang' and report_id >= $earliestReportID and report_id in (select report_id from reportRecord where report_key = 'appName' and report_value=?) group by lang order by c desc;";

	$charts['model']['heading'] = "Model";
	$charts['model']['query'] = "select replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(report_value,',',''),'9',''),'8',''),'7',''),'6',''),'5',''),'4',''),'3',''),'2',''),'1',''),'0','') p, count(*) c from reportRecord where report_key = 'model' and report_id >= $earliestReportID and report_id in (select report_id from reportRecord where report_key = 'appName' and report_value=?) group by p order by c desc;";

	$charts['ram']['heading'] = "RAM (GB)";
	$charts['ram']['query'] = "select round(convert(report_value,unsigned integer)/1024,1) p, count(*) c from reportRecord where report_key = 'ramMB' and report_id >= $earliestReportID and report_id in (select report_id from reportRecord where report_key = 'appName' and report_value=?) group by p order by p;";

	foreach ($distinctApps as $app) {
		echo "<h2>$app</h2>";
		foreach ($charts as $chart) {
			drawChart($chart,$app);
		}
	}
}

function drawChart($query,$app) {
function drawChart($chartArray,$app) {
	global $DbLink;
	echo "<h3>" . $chartArray['heading'] . "</h3>";
	$count = 0;
	$greatest = 0;
	$stmt = $DbLink->prepare($chartArray['query']);
	$stmt->bind_param("s",$app);
	$stmt->execute();
	$results = $stmt->get_result();
	while ($row = $results->fetch_array()) {
		$resultsArray[] = $row;
		$count +=$row[1];
		if ($row[1] > $greatest) $greatest = $row[1];
	}
	echo "<table class=\"chart-table\"><tr class=\"chart-table-tr\">\n";
	foreach ($resultsArray as $bar) {
		echo "<td class=\"chart-table-td\">\n";
		echo "<div class=\"chart-table-column\">\n";
		echo "<span style=\"width: 50px; height: " . $bar[1]/$greatest*150 . "px; border: 1px solid black; background-color: rgb(220,238,238); display: block; margin-left:auto;margin-right:auto;\">&nbsp;</span>\n";
		echo "<strong>" . $bar[0] . "</strong><br>" . round($bar[1]/$count*100,2) . "%</div>\n";
		echo "</td>\n";
	}
	echo "</tr></table>\n";
	$stmt->close();
}