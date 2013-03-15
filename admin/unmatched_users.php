<?php 
// Users entered incorrect IDs when they did their BKSB Initial Assessments
// This page allows administrators to update user details

include('../../../config.php');

$userid = optional_param('userid', 0, PARAM_INT);
$firstname = optional_param('fname', '', PARAM_RAW);
$lastname = optional_param('lname', '', PARAM_RAW);
$order_field = optional_param('order', '', PARAM_RAW);

require_login();

if (!has_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM))) {
    error("Only the administrator can access this page!", $CFG->wwwroot);
}

include('../BksbReporting.class.php');
include('../Cache.class.php');
$bksb = new BksbReporting();

$baseurl = new moodle_url('unmatched_users.php');
$title = 'BKSB - Update Usernames';
$PAGE->set_context(get_system_context());
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_url($baseurl);
$PAGE->requires->css('/blocks/bksb/styles.css', true);
$PAGE->requires->css('/blocks/bksb/js/colorbox.css', true);
$PAGE->requires->js('/blocks/bksb/js/jquery-1.7.2.min.js', true);
$PAGE->requires->js('/blocks/bksb/js/functions.js', true);
$PAGE->requires->js('/blocks/bksb/js/jquery.colorbox-min.js', true);

echo $OUTPUT->header();

echo '<img src="'.$OUTPUT->pix_url('logo-bksb', 'block_bksb').'" alt="BKSB logo" width="261" height="52" class="bksb_logo" />';

echo '<a href="'.$CFG->wwwroot.'/admin/settings.php?section=blocksettingbksb">&lt; Back to BKSB settings</a>';
echo "<h2>BKSB - Invalid Usernames</h2>";

// Insert best viewed with Chrome or Firefox banner here.
if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false)) {
    echo '<div id="best_viewed"><img src="'.$CFG->wwwroot.'/blocks/bksb/js/images/best-viewed-with.png" alt="Best viewed with Chrome or Firefox" width="468" height="59" /></div>';
}

// We need to ability to search by first and lastname
echo '<hr />';
echo '<h4>Filter Invalid Users</h4>';
echo '<form action="unmatched_users.php" method="get">
<table>
    <tr><td>Firstname:</td><td><input type="text" name="fname" value="'.$firstname.'" /></td></tr>
    <tr><td>Lastname:</td><td><input type="text" name="lname" value="'.$lastname.'" /></td></tr>
    <tr><td>&nbsp;</td><td><input type="submit" value="Filter Users" /></td>
    <tr><td>&nbsp;</td><td><a href="unmatched_users.php">Clear Filters</a></td>
</table>
</form>';
echo '<hr />';

// If no filters are set: get results from Cache
$invalid_users = $bksb->getInvalidBksbUsers($firstname, $lastname, $order_field);
$no_invalids = count($invalid_users);

$no_invalid_users = number_format(count($invalid_users));
$username_txt = ($no_invalid_users > 1) ? 'usernames' : 'username';
echo "<p><b>$no_invalid_users</b> invalid $username_txt found in BKSB</p>";

// Pagination
$per_page = 500;
$no_pages = ceil($no_invalids / $per_page);
$page_no = 1;

echo '<div id="invalid_users">';

// "Pagination" here. Really just javascript which shows/hides groups of 500
// Only show pagination if there's more than 'per_page' results
if ($no_invalids > $per_page) {
    $start_count = 1;
    $current_pp = $per_page;
    echo '<div id="pagination">';
    for ($p = 1; $p <= $no_pages; $p++) {
        if ($p == $no_pages) {
            $current_pp = $no_invalid_users;
        }
        $current_class = ($p == 1) ? ' class="show_table current"' : 'class="show_table"';
        echo '<a href="#" '.$current_class.' name="'.$p.'" id="link_'.$p.'">'.$start_count.' &ndash; '.$current_pp.'</a>';
        $start_count += $per_page;
        $current_pp += $per_page;
    }
    echo '<br class="clear_both" />';
    echo '</div>';
}

echo '<br />';

// Split invalid users into n ($no_pages) groups of ($per_page) items
$c = 1;
$p = 1;
$page_limit = $per_page;

foreach ($invalid_users as $user) {
    if ($c <= $page_limit) {
        $invalids[$p][] = $user;
        $c++;
    } else {
        $page_limit += $per_page;
        $p++;
        $c++;
        $invalids[$p][] = $user;
    }
}

// Print the table seven times.
$i = 1;
for ($j=1; $j <= $no_pages; $j++) {
    echo "<div id=\"invalid_users_$j\" class=\"tbl_invalid_users\">";
        echo "<table class=\"bksb_results\">";
        echo "<tr class=\"header\">
            <th>No</th>
            <th><a href=\"unmatched_users.php?order=userName\" title=\"Order by Username\">Username</a></th>
            <th><a href=\"unmatched_users.php?order=FirstName\" title=\"Order by Firstname\">Firstname</a></th>
            <th><a href=\"unmatched_users.php?order=LastName\" title=\"Order by Lastname\">Lastname</a></th>
            <th>DOB</th>
            <th>Postcode</th>
            <th>Why invalid?</th><th>Action</th>
            </tr>";
        foreach ($invalids[$j] as $user) {
            echo "<tr id=\"user_$i\">
                <td style=\"text-align:center;\">$i</td>
                
                <td class=\"bksb_username\"><span>".$user['username']."</span></td>
                <td class=\"bksb_firstname\"><span>".$user['firstname']."</span></td>
                <td class=\"bksb_lastname\"><span>".$user['lastname']."</span></td>
                <td><span>".$user['dob']."</span></td>
                <td><span>".$user['postcode']."</span></td>
                <td>".$user['reason']."</td>
                <td>&nbsp;<a href=\"".$CFG->wwwroot."/blocks/bksb/admin/update.php?old_username=".urlencode($user['username'])."&amp;firstname=".urlencode($user['firstname'])."&amp;lastname=".urlencode($user['lastname'])."&amp;row=$i\" target=\"_blank\" class=\"update_user\" id=\"update_$i\">Update</a>&nbsp;</td>

                </tr>";
            $i++;
        }
        echo "</table>";
    echo "</div>";
}

echo '</div>';

echo $OUTPUT->footer();
?>
