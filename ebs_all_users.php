<?php

define('CLI_SCRIPT', true);

require('../../config.php'); 

global $CFG, $DB;

$mis_server = get_config('block_bksb', 'mis_db_server');
$mis_user = get_config('block_bksb', 'mis_db_user');
$mis_password = get_config('block_bksb', 'mis_db_password');
$mis_db = get_config('block_bksb', 'mis_db_name');

// MIS connection
include($CFG->dirroot.'/lib/adodb/adodb.inc.php');
$mis = NewADOConnection('oci8');
$mis->SetFetchMode(ADODB_FETCH_ASSOC);
$mis->debug = false;
$mis->NLS_DATE_FORMAT ='DD-MON-YYYY'; // required
$mis->Connect($mis_server, $mis_user, $mis_password, $mis_db);

//print_r($mis);

$query = "SELECT COUNT(STUDENT_ID) FROM FES.MOODLE_PEOPLE"; // WHERE POST_CODE != 'ZZ99 ZZZ'";

$result = $mis->Execute($query);

print_r($result);

       
/*
        $query = "SELECT STUDENT_ID, TO_CHAR(DATE_OF_BIRTH, 'DD/MM/YYYY') AS DOB, POST_CODE FROM FES.MOODLE_PEOPLE WHERE POST_CODE != 'ZZ99 ZZZ'";

        $ebs_users = array();
        
        if ($users = $mis->Execute($query)) {
            while (!$users->EOF) {
                $ebs_users[] = array(
                    'idnumber' => $users->fields['STUDENT_ID'], 
                    'dob' => $users->fields['DOB'], 
                    'postcode' => $users->fields['POST_CODE']
                );
                $users->moveNext();
            }
        }
		
		print_r($users);
*/
