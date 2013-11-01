<?
// This is where all the server-side configuration happens.
// Location of the real appcast file
$appcastURL = "http://ironcoder.org/svn/SparklePlus/trunk/sparkletestcast.xml";

// This is an associative array of all "good" keys expected from clients.
$appcastKeys = array('appName' => 1, 'appVersion' => 1, 'cpuFreqMHz' => 1, 'cpu64bit' => 1, 'cpusubtype' => 1, 'cputype' => 1, 'lang' => 1, 'model' => 1, 'ncpu' => 1, 'osVersion' => 1, 'ramMB' => 1);

// Database connectivity
// Change the username, password, and database to the correct values for your database
$db_host	= "DATABASE HOST";
$db_user	= "DATABASE USER NAME";
$db_password	= "DATABASE PASSWORD";
$db_name	= "DATABASE_NAME";
// end configuration
?>
