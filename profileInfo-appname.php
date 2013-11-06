<?php
// A different profileInfo is required for each of your apps
// It contains the configuration unique to a single app
// The SUFeedURL on the client side should be pointed at this file

// Location of the real appcast file (can be a filepath or a URL)
$appcastURL = "http://ironcoder.org/svn/SparklePlus/trunk/sparkletestcast.xml";

// This is an associative array of all "good" keys expected from clients
$appcastKeys = array('appName' => 1, 'appVersion' => 1, 'cpuFreqMHz' => 1, 'cpu64bit' => 1, 'cpusubtype' => 1, 'cputype' => 1, 'lang' => 1, 'model' => 1, 'ncpu' => 1, 'osVersion' => 1, 'ramMB' => 1);

// Debugging
$debug = 0;

require("profileDB.php");
require("profileInfo-common.php");