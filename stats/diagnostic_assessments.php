<?php 

include('../../../config.php');

$userid = optional_param('userid', 0, PARAM_INT);
$group = optional_param('group', '', PARAM_RAW);

require_login();

$sitecontext = get_context_instance(CONTEXT_SYSTEM);
if (!has_capability('block/bksb:view_statistics', $sitecontext)) {
    error("You do not have permission to view this page", $CFG->wwwroot);
}

include('../BksbReporting.class.php');
$bksb = new BksbReporting();

// Print headers
$title = 'BKSB - Diagnostic Assessment Statistics';

$params = $bksb->getDistinctParams();
$baseurl = $CFG->wwwroot . '/blocks/bksb/stats/diagnostic_assessments.php' . $params;

$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
$PAGE->set_pagelayout('admin');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_url($baseurl);

echo $OUTPUT->header();

// BKSB logo - branding
echo '<img src="'.$OUTPUT->pix_url('logo-bksb', 'block_bksb').'" alt="BKSB logo" width="261" height="52" class="bksb_logo" />';

// Get all BKSB groups
$groups = $bksb->getBksbGroups();

echo '<h2>Diagnostic Assessment Statistics</h2>';
echo '<ul><li><a href="initial_assessments.php">View Inital Assessment Statistics</a></li></ul>';

echo '<div id="bksb_stats">';

echo '<form action="diagnostic_assessments.php" method="get">';
echo '<p><strong>Group:</strong>&nbsp;';
echo '<select name="group" onchange="javascript:this.form.submit()">';
echo '<option value="">-- Select Group --</option>';
foreach ($groups as $group_name) {
    $selected = ($group_name == $group) ? ' selected="selected"' : '';
    echo '<option value="'.$group_name.'"'.$selected.'>'.$group_name.'</option>';
}
echo '</select></p>';
echo '</form>';

// Set up array keys to ignore
$ignore_keys = array('total_literacy_e2', 'total_literacy_e3', 'total_literacy_l1', 'total_literacy_l2', 'total_literacy_l3', 'total_numeracy_e2', 'total_numeracy_e3', 'total_numeracy_l1', 'total_numeracy_l2', 'total_numeracy_l3');

if ($group != '') {

    $users = $bksb->getDiagnosticOverviewsForGroup($group);
    $user_count = 0;
    foreach ($users as $key => $value) {
        if (!in_array($key, $ignore_keys) && $key != '') {
            $user_count++;
        }
    }
    
    echo '<h1>'.$group.'</h1>';
    echo "<p>Statistics from <strong>complete</strong> diagnostic assessments.</p>";
    
    echo '<table>';
    echo '<tr><td><strong>Complete Diagnostic Assessments:</strong></td><td>'.$user_count.'</td></tr>';
    echo '</table>';
    echo '<table style="float:left; margin-right:15px;">';
    echo '<tr><td><strong>English Entry 2:</strong></td><td> '.$users['total_literacy_e2'].'</td></tr>';
    echo '<tr><td><strong>English Entry 3:</strong></td><td> '.$users['total_literacy_e3'].'</td></tr>';
    echo '<tr><td><strong>English Level 1:</strong></td><td> '.$users['total_literacy_l1'].'</td></tr>';
    echo '<tr><td><strong>English Level 2:</strong></td><td> '.$users['total_literacy_l2'].'</td></tr>';
    echo '<tr><td><strong>English Level 3:</strong></td><td> '.$users['total_literacy_l3'].'</td></tr>';
    echo '</table>';
    echo '<table>';
    echo '<tr><td><strong>Maths Entry 2:</strong></td><td> '.$users['total_numeracy_e2'].'</td></tr>';
    echo '<tr><td><strong>Maths Entry 3:</strong></td><td> '.$users['total_numeracy_e3'].'</td></tr>';
    echo '<tr><td><strong>Maths Level 1:</strong></td><td> '.$users['total_numeracy_l1'].'</td></tr>';
    echo '<tr><td><strong>Maths Level 2:</strong></td><td> '.$users['total_numeracy_l2'].'</td></tr>';
    echo '<tr><td><strong>Maths Level 3:</strong></td><td> '.$users['total_numeracy_l3'].'</td></tr>';
    echo '</tr></table>';
    echo '<br />';
    
    echo '<table cellspacing="3" class="bksb_results centered_tds">';
    echo '<tr class="header"><th>Username</th><th>English E2</th><th>English E3</th><th>English L1</th><th>English L2</th><th>English L3</th><th>Maths E2</th><th>Maths E3</th><th>Maths L1</th><th>Maths L2</th><th>Maths L3</th></tr>';
    foreach ($users as $key => $user) {
        if (!in_array($key, $ignore_keys) && $key != '') {
            echo '<tr>';
            echo "<td>".$user['user_name']."</td><td>".$user['literacy_e2']."</td><td>".$user['literacy_e3']."</td><td>".$user['literacy_l1']."</td><td>".$user['literacy_l2']."</td><td>".$user['literacy_l3']."</td><td>".$user['numeracy_e2']."</td><td>".$user['numeracy_e3']."</td><td>".$user['numeracy_l1']."</td><td>".$user['numeracy_l2']."</td><td>".$user['numeracy_l3']."</td>";
            echo '</tr>';
        }
    }
    echo '<tr><td><strong>Totals</strong></td>
    <td>'.$users['total_literacy_e2'].'</td><td>'.$users['total_literacy_e3'].'</td><td>'.$users['total_literacy_l1'].'</td><td>'.$users['total_literacy_l2'].'</td><td>'.$users['total_literacy_l3'].'</td><td>'.$users['total_numeracy_e2'].'</td><td>'.$users['total_numeracy_e3'].'</td><td>'.$users['total_numeracy_l1'].'</td><td>'.$users['total_numeracy_l2'].'</td><td>'.$users['total_numeracy_l3'].'</td></tr>';
    echo '</table>';

}

echo '</div>';

echo $OUTPUT->footer();
?>
