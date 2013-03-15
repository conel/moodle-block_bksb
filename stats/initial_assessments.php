<?php 

include('../../../config.php');

$userid = optional_param('userid', 0, PARAM_INT);
$group = optional_param('group', '', PARAM_RAW);
$iatype = optional_param('iatype', '', PARAM_RAW);
$sm = optional_param('sm', '', PARAM_INT);
$sy = optional_param('sy', '', PARAM_INT);
$em = optional_param('em', '', PARAM_INT);
$ey = optional_param('ey', '', PARAM_INT);

require_login();

$sitecontext = get_context_instance(CONTEXT_SYSTEM);
if (!has_capability('block/bksb:view_statistics', $sitecontext)) {
    error("You do not have permission to view this page", $CFG->wwwroot);
}

include('../BksbReporting.class.php');
$bksb = new BksbReporting();

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

$unix_start = '';
$unix_end = '';

// If valid start date, create unixtime
if ( ($sm != '' && $sm <= 12) && ($sy != '' && (strlen($sy) == 4)) ) {
    $unix_start = mktime(0, 0, 0, $sm, 1, $sy);
}
// If valid end date, create unixtime
if ( ($em != '' && $em <= 12) && ($ey != '' && (strlen($ey) == 4)) ) {
    $unix_end = mktime(0, 0, 0, $em, 1, $ey);
}

function getGCSEResults($username) {
    global $mis;

    $html = '-';
    $query = sprintf("SELECT AWARD_TITLE, ACHIEVED_YEAR, GRADE, AWARDING_BODY, QUAL_TYPE, QUAL_DESC FROM FES.MOODLE_ATTAINMENTS WHERE STUDENT_ID = %d AND QUAL_TYPE = 'GCSE' AND AWARD_TITLE IN ('Maths', 'English')", $username);
    if ($quals = $mis->Execute($query)) {
        $user_quals = array();
        $i = 0;
        while (!$quals->EOF) {
            $user_quals[$i]['award_title'] = $quals->fields["AWARD_TITLE"];
            $user_quals[$i]['achieved_year'] = $quals->fields["ACHIEVED_YEAR"];
            $user_quals[$i]['grade'] = $quals->fields["GRADE"];
            $user_quals[$i]['awarding_body'] = ($quals->fields["AWARDING_BODY"] != '') ? $quals->fields["AWARDING_BODY"] : '' ;
            $user_quals[$i]['qual_type'] = $quals->fields["QUAL_TYPE"];
            $user_quals[$i]['qual_desc'] = $quals->fields["QUAL_DESC"];
            
            $quals->moveNext();
            $i++;
        }
        // If we have quals, put them into a nicely formatted bit of text to return
        $html = '-';
        foreach ($user_quals as $qual) {
            $html .= $qual['award_title'] . "&nbsp;";
            $html .= $qual['qual_desc'];
            $html .= "&nbsp;". $qual['grade'] . "&nbsp;";
            if ($qual['awarding_body'] != '') {
                $html .= $qual['awarding_body'] . "&nbsp;";
            }
            $html .= "- " . $qual['achieved_year'] . "&nbsp;";
            $html .= '<br />';
            $i++;
        }
    }
    return $html;
}

$params = $bksb->getDistinctParams();
$baseurl = $CFG->wwwroot . '/blocks/bksb/stats/initial_assessments.php' . $params;

$title = 'BKSB - Initial Assessment Statistics';
$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
$PAGE->set_pagelayout('admin');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_url($baseurl);

echo $OUTPUT->header();

// Get all BKSB groups
$groups = $bksb->getBksbGroups();

// BKSB logo - branding
echo '<img src="'.$OUTPUT->pix_url('logo-bksb', 'block_bksb').'" alt="BKSB logo" width="261" height="52" class="bksb_logo" />';

echo '<h2>Initial Assessment Statistics</h2>';

echo '<ul><li><a href="diagnostic_assessments.php">View Diagnostic Assessment statistics</a></li></ul>';

echo '<form action="initial_assessments.php" method="get">';
echo '<table><tr>';
echo '<td style="text-align:right;"><strong>Initial Assessment:</strong></td>';
echo '<td><select name="iatype">';
echo '<option value="">-- Select Initial Assessment --</option>';
if ($iatype == 'English') {
    echo '<option value="English" selected="selected">English</option>';
} else {
    echo '<option value="English">English</option>';
}
if ($iatype == 'Mathematics') {
    echo '<option value="Mathematics" selected="selected">Mathematics</option>';
} else {
    echo '<option value="Mathematics">Mathematics</option>';
}
if ($iatype == 'ICT') {
    echo '<option value="ICT" selected="selected">ICT</option>';
} else {
    echo '<option value="ICT">ICT</option>';
}

echo '</select></td></tr>';
echo '<tr><td style="text-align:right;"><strong>Group:</strong></td>';
echo '<td><select name="group">';
echo '<option value="">-- Select Group --</option>';
foreach ($groups as $group_name) {
    if ($group_name == $group) {
        echo '<option value="'.$group_name.'" selected="selected">'.$group_name.'</option>';
    } else {
        echo '<option value="'.$group_name.'">'.$group_name.'</option>';
    }
}

echo '</select></td></tr>';

// nkowald - 2012-01-16 - Scott wants to be able to search via month year
$months = array (
    '1' => 'Jan',
    '2' => 'Feb',
    '3' => 'Mar',
    '4' => 'Apr',
    '5' => 'May',
    '6' => 'Jun',
    '7' => 'Jul',
    '8' => 'Aug',
    '9' => 'Sep',
    '10' => 'Oct',
    '11' => 'Nov',
    '12' => 'Dec'
);

$year_start = 2009;
while ($year_start <= date('Y')) {
    $years[] = $year_start;
    $year_start++;
}

// Start Date
echo "<tr><td style=\"text-align:right;\"><strong>Date Start:</strong></td>\n";
echo "<td>\n";

    echo "<select name=\"sm\">\n";
            echo "\t<option value=\"\">&ndash;</option>\n";
        foreach ($months as $key => $val) {
            $selected = ($key == $sm) ? ' selected="selected"' : '';
            echo "\t<option value=\"$key\"$selected>$val</option>\n";
        }
    echo "</select>\n";
    
    echo "<select name=\"sy\">\n";
            echo "\t<option value=\"\">&ndash;</option>\n";
        foreach ($years as $year) {
            $selected = ($year == $sy) ? ' selected="selected"' : '';
            echo "\t<option value=\"$year\"$selected>$year</option>\n";
        }
    echo "</select>\n";

echo "</td>\n";
echo "</tr>\n";

// End Date
echo "<tr><td style=\"text-align:right;\"><strong>Date End:</strong></td>\n";
echo "<td>\n";

    echo "<select name=\"em\">\n";
            echo "\t<option value=\"\">&ndash;</option>\n";
        foreach ($months as $key => $val) {
            $selected = ($key == $em) ? ' selected="selected"' : '';
            echo "\t<option value=\"$key\"$selected>$val</option>\n";
        }
    echo "</select>\n";
    
    echo "<select name=\"ey\">\n";
            echo "\t<option value=\"\">&ndash;</option>\n";
        foreach ($years as $year) {
            $selected = ($year == $ey) ? ' selected="selected"' : '';
            echo "\t<option value=\"$year\"$selected>$year</option>\n";
        }
    echo "</select>\n";

echo "</td>\n";
echo "</tr>\n";

echo '<tr><td>&nbsp;</td><td><input type="submit" value="View Stats" /></td></tr></table>';
echo '</form>';

echo '<div id="bksb_stats">';

// Set up array keys to ignore
$ignore_keys = array('total_literacy_e2', 'total_literacy_e3', 'total_literacy_l1', 'total_literacy_l2', 'total_literacy_l3', 'total_numeracy_e2', 'total_numeracy_e3', 'total_numeracy_l1', 'total_numeracy_l2', 'total_numeracy_l3');

if ($group != '' && $iatype != '') {
    $users = $bksb->getIAForGroup($group, $iatype, $unix_start, $unix_end);
    $user_count = 0;
    foreach ($users as $key => $value) {
        if (!in_array($key, $ignore_keys) && $key != '') {
            $user_count++;
        }
    }
    
    echo '<h1>'.$group.'</h1>';
    
        if ($iatype == 'English') {
        
            echo '<table><tr>';
            echo '<td><strong>Initial Assessments Taken:</strong></td><td>'.$user_count.'</td>';
            echo '</tr></table>';

            $perc_1 = round((($users['total_literacy_e2'] / $user_count) * 100), 1);
            $perc_2 = round((($users['total_literacy_e3'] / $user_count) * 100), 1);
            $perc_3 = round((($users['total_literacy_l1'] / $user_count) * 100), 1);
            $perc_4 = round((($users['total_literacy_l2'] / $user_count) * 100), 1);
            $perc_5 = round((($users['total_literacy_l3'] / $user_count) * 100), 1);

            echo '<table>';
            echo '<tr><td><strong>English Entry 2:</strong></td><td>'.$users['total_literacy_e2'].'</td><td>'.$perc_1.'%</td></tr>';
            echo '<tr><td><strong>English Entry 3:</strong></td><td>'.$users['total_literacy_e3'].'</td><td>'.$perc_2.'%</td></tr>';
            echo '<tr><td><strong>English Level 1:</strong></td><td>'.$users['total_literacy_l1'].'</td><td>'.$perc_3.'%</td></tr>';
            echo '<tr><td><strong>English Level 2:</strong></td><td>'.$users['total_literacy_l2'].'</td><td>'.$perc_4.'%</td></tr>';
            echo '<tr><td><strong>English Level 3:</strong></td><td>'.$users['total_literacy_l3'].'</td><td>'.$perc_5.'%</td></tr>';
            echo '</table>';
            
            echo '<table cellspacing="3" class="bksb_results centered_tds">';
            echo '<tr class="header"><th>Username</th><th>Name</th><th>English Entry 2</th><th>English Entry 3</th><th>English Level 1</th><th>English Level 2</th><th>English Level 3</th><th>GCSE</th></tr>';
            foreach ($users as $key => $user) {
                if (!in_array($key, $ignore_keys) && $key != '') {
                    echo '<tr>';
                    echo "<td>".$user['user_name']."</td>";
                    echo "<td>".$user['name']. "</td>";
                    echo "<td>".$user['literacy_e2']."</td><td>".$user['literacy_e3']."</td><td>".$user['literacy_l1']."</td><td>".$user['literacy_l2']."</td><td>".$user['literacy_l3']."</td>";
                    
                    // Get GCSE result for the student
                    $html = getGCSEResults($user['user_name']);
                    echo "<td>".$html."</td>";
                    echo '</tr>';
                }
            }
            echo '<tr class="totals"><td><strong>Totals</strong></td>
            <td>&nbsp;</td><td>'.$users['total_literacy_e2'].'</td><td>'.$users['total_literacy_e3'].'</td><td>'.$users['total_literacy_l1'].'</td><td>'.$users['total_literacy_l2'].'</td><td>'.$users['total_literacy_l3'].'</td><td>&nbsp;</td></tr>';
            echo '</table>';
            
        } else if ($iatype == 'Mathematics') {
            
            $perc_1 = round((($users['total_numeracy_e2'] / $user_count) * 100), 1);
            $perc_2 = round((($users['total_numeracy_e3'] / $user_count) * 100), 1);
            $perc_3 = round((($users['total_numeracy_l1'] / $user_count) * 100), 1);
            $perc_4 = round((($users['total_numeracy_l2'] / $user_count) * 100), 1);
            $perc_5 = round((($users['total_numeracy_l3'] / $user_count) * 100), 1);

            echo '<table><tr><td><strong>Initial Assessments Taken:</strong></td><td>'.$user_count.'</td></tr></table>';
            echo '<table>';
            echo '<tr><td><strong>Maths Entry 2:</strong></td><td>'.$users['total_numeracy_e2'].'</td><td>'.$perc_1.'%</td></tr>';
            echo '<tr><td><strong>Maths Entry 3:</strong></td><td>'.$users['total_numeracy_e3'].'</td><td>'.$perc_2.'%</td></tr>';
            echo '<tr><td><strong>Maths Level 1:</strong></td><td>'.$users['total_numeracy_l1'].'</td><td>'.$perc_3.'%</td></tr>';
            echo '<tr><td><strong>Maths Level 2:</strong></td><td>'.$users['total_numeracy_l2'].'</td><td>'.$perc_4.'%</td></tr>';
            echo '<tr><td><strong>Maths Level 3:</strong></td><td>'.$users['total_numeracy_l3'].'</td><td>'.$perc_5.'%</td></tr>';
            echo '</table>';
            
            echo '<table cellspacing="3" class="bksb_results centered_tds">';
            echo '<tr class="header"><th>Username</th><th>Name</th><th>Maths Entry 2</th><th>Maths Entry 3</th><th>Maths Level 1</th><th>Maths Level 2</th><th>Maths Level 3</th><th>GCSE</th></tr>';
            foreach ($users as $key => $user) {

                if (!in_array($key, $ignore_keys) && $key != '') {
                    echo '<tr>';
                    echo "<td>".$user['user_name']."</td>";
                    echo "<td>".$user['name']. "</td>";
                    echo "<td>".$user['numeracy_e2']."</td><td>".$user['numeracy_e3']."</td><td>".$user['numeracy_l1']."</td><td>".$user['numeracy_l2']."</td><td>".$user['numeracy_l3']."</td>";
                    // Get GCSE result for the student
                    $html = getGCSEResults($user['user_name']);
                    echo "<td>".$html."</td>";
                    echo '</tr>';
                }
            }
            echo '<tr class="totals"><td><strong>Totals</strong></td>
            <td>&nbsp;</td><td>'.$users['total_numeracy_e2'].'</td><td>'.$users['total_numeracy_e3'].'</td><td>'.$users['total_numeracy_l1'].'</td><td>'.$users['total_numeracy_l2'].'</td><td>'.$users['total_numeracy_l3'].'</td><td>&nbsp;</td></tr>';
            echo '</table>';
            
        } else if ($iatype == 'ICT') {
        
            $valid_types = array('word_processing', 'spreadsheets', 'databases', 'desktop_publishing', 'presentation', 'email', 'general', 'internet');
            
            $user_count = 0;
            foreach ($users as $key => $value) {
                $user_count++;
            }
            
            echo '<strong>ICT Initial Assessments:</strong> '.$user_count.'<br /><br />';
            
            $totals = $bksb->getIctTotals($users);
            echo $totals;
            
            echo '<table cellspacing="3" class="bksb_results centered_tds">';
            echo '<tr class="header"><th>Username</th><th>Name</th><th>Word Processing</th><th>Spreadsheets</th><th>Databases</th><th>Desktop Publishing</th><th>Presentation</th><th>Email</th><th>General</th><th>Internet</th><th>GCSE</th></tr>';
            
            foreach ($users as $user) {
                echo '<tr>';
                echo "<td>".$user['user_name']."</td>";
                echo "<td>".$user['name']. "</td>";
                foreach ($user['results'] as $type => $value) {
                    if (in_array($type, $valid_types)) {
                        echo "<td>".$value."</td>";
                    }
                }
                // Get GCSE result for the student
                $html = getGCSEResults($user['user_name']);
                echo "<td>".$html."</td>";
                echo '</tr>';
            }
            //echo '<tr class="totals"><td><strong>Totals</strong></td>
            //<td>'.$users['total_numeracy_e2'].'</td><td>'.$users['total_numeracy_e3'].'</td><td>'.$users['total_numeracy_l1'].'</td><td>'.$users['total_numeracy_l2'].'</td><td>'.$users['total_numeracy_l3'].'</td></tr>';
            echo '</table>';
            
        }

}
echo '</div>';

echo $OUTPUT->footer();
?>
