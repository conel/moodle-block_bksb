<?php

$sapi_type = php_sapi_name();
$method = substr($sapi_type, 0, 3);
if ($method != 'cli') {
    die("Cron script can't be run directly. It only comes out at night (so not to mess with active BKSB assessment sessions).");
}

define('CLI_SCRIPT', true);
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // global moodle config file.
require(dirname(dirname(__FILE__)).'/BksbReporting.class.php');
$bksb = new BksbReporting();

// Time how long it takes to update and echo the output
$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$begintime = $time;

// First part of cron is to sync Moodle user postcodes and dob with EBS
$bksb->syncUserDobAndPostcode();

// Second part of cron is to find all invalid BKSB users and then update all matched users
$invalid_users = $bksb->getInvalidBksbUsers();
$no_invalids = count($invalid_users);

if ($no_invalids > 0) {
    $bksb->updateInvalidUsers($invalid_users);
}

$time = microtime();
$time = explode(" ", $time);
$time = $time[1] + $time[0];
$endtime = $time;
$totaltime = round(($endtime - $begintime), 2);
echo "The BKSB cron took $totaltime seconds to run." . PHP_EOL;

?>
