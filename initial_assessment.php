<?php 

include('../../config.php');
include('BksbReporting.class.php');
// To use caching, create a 'bksb' directory inside MoodleData/cache
include('Cache.class.php');
include($CFG->libdir.'/tablelib.php');
include($CFG->dirroot . '/group/lib.php'); // Required to get group members

$user_id = optional_param('id', 0, PARAM_INT);
$course_id = optional_param('course_id', SITEID, PARAM_INT);
$group = optional_param('group', 0, PARAM_INT);
$updatepref = optional_param('updatepref', -1, PARAM_INT);

if (!$course = $DB->get_record('course', array('id' => $course_id))) {
    error("Course ID is incorrect");
}
if (!$coursecontext = get_context_instance(CONTEXT_COURSE, $course->id)) {
    error("Context ID is incorrect");
}
require_login($course);

$access_is_teacher = has_capability('block/bksb:view_all_results', $coursecontext);
$access_is_student = has_capability('block/bksb:view_own_results', $coursecontext);
$access_is_god = false;
if (has_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM))) $access_is_god = true;

if ($course_id != SITEID) {
    $PAGE->set_context($coursecontext);
    //$PAGE->set_pagelayout('course');
} else if ($user_id != 0) {
    $PAGE->set_context(get_context_instance(CONTEXT_USER, $USER->id));
    $PAGE->navigation->extend_for_user($USER);
    //$PAGE->set_pagelayout('user');
}
$bksb = new BksbReporting();
// nkowald - 2012-01-10 - Define $baseurl here, needs to keep all get distinct params
$params = $bksb->getDistinctParams();
$baseurl = $CFG->wwwroot.'/blocks/bksb/initial_assessment.php' . $params;

$title = 'BKSB - Initial Assessment Overview';
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_url($baseurl);
$PAGE->requires->css('/blocks/bksb/styles.css', true);

echo $OUTPUT->header();

// BKSB logo - branding
echo '<img src="'.$OUTPUT->pix_url('logo-bksb', 'block_bksb').'" alt="BKSB logo" width="261" height="52" class="bksb_logo" />';

// Single User Results
if ($user_id != 0) {

    if ($user_id != $USER->id && ($access_is_god === false || $access_is_teacher === false)) { 
        error("You don't have permission to view this user's results");
    }

    if ($user = $DB->get_record('user', array('id' => $user_id), 'id, idnumber, firstname, lastname')) {
        $fullname = $user->firstname . ' ' . $user->lastname;
        $conel_id = $user->idnumber;
    }

    $header = '<div class="bksb_header">';
    $header .= "<h2>Initial Assessment for <span>$fullname</span></h2>";
    $profile_link = $CFG->wwwroot . "/user/profile.php?id=$user_id&amp;courseid=$course_id";
    $header .= '<a href="'.$profile_link.'" title="View Profile">'.$OUTPUT->user_picture($user, array('size'=>100)).'</a>';
    $header .= '<br /><br /></div>';

    // Return from cache if set
    Cache1::init('user-'.$user_id.'-ia-html.cache', $bksb->cache_life);
    if (Cache1::cacheFileExists()) {
        $table_html = Cache1::getCache();
    } else {

        // Get BKSB Result categories
        $cats = $bksb->ass_cats;
        $tablecolumns = $cats;
        $tableheaders = $cats;

        $table = new flexible_table('initial-assessments');
                        
        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);
        $table->define_baseurl($baseurl);
        $table->collapsible(false);
        $table->initialbars(false);
        $table->column_suppress('picture');	
        $table->column_class('picture', 'picture');
        $table->column_class('fullname', 'fullname');
        $table->set_attribute('cellspacing', '0');
        $table->set_attribute('id', 'bksb_results_group');
        $table->set_attribute('class', 'bksb_results');
        $table->set_attribute('width', '95%');
        $table->set_attribute('align', 'center');
        foreach($cats as $cat) {
            $table->no_sorting($cat);
        }
        ob_start();
        echo $header;
        $table->setup();

        $bksb_results = $bksb->getResults($conel_id);
        $row = $bksb_results;
        $table->add_data($row);

        $table->print_html();  // Print the table
        $table_html = ob_get_contents();
        ob_end_clean();
        Cache1::setCache($table_html);

    }

    echo $table_html;

} else if ($course->id && $course->id != $SITE->id) {

    // If student gets to this link, redirect them to their own results
    if ($access_is_teacher === false && $access_is_god === false) {
        $own_results = 'initial_assessment.php?id='.$USER->id.'&amp;course_id='.$course->id;
        error("You don't have permission to view all initial assessment results for this course", $own_results);
    }

    $context = get_context_instance(CONTEXT_COURSE, $course->id);

    if ($updatepref > 0) {
        $perpage = optional_param('perpage', 10, PARAM_INT);
        $perpage = ($perpage <= 0) ? 10 : $perpage ;
        set_user_preference('bksb_ia_perpage', $perpage);
    }

    /* next we get perpage and from database */
    $perpage = get_user_preferences('bksb_ia_perpage', 10);
    $page = optional_param('page', 0, PARAM_INT);

    // Are groups being used in this course?. If so set $currentgroup to reflect the current group
    $groupmode = groups_get_course_groupmode($course); // Groups are being used
    $currentgroup = groups_get_course_group($course, true);
    if (!$currentgroup) $currentgroup = NULL;

    $isseparategroups = ($course->groupmode == SEPARATEGROUPS 
        && $course->groupmodeforce 
        && !has_capability('moodle/site:accessallgroups', $context)
    );

    echo '<div class="bksb_header">';
    echo "<h2>Initial Assessment Overview (<a href=\"".$CFG->wwwroot."/course/view.php?id=".$course->id."\">".$course->shortname."</a>)</h2>";
    groups_print_course_menu($course, $baseurl); 
    echo '<br />';
    echo '</div>';

    // Get BKSB Result categories
    $cols = array('picture', 'fullname');
    $cols_header = array('Picture', 'Name');
    $cats = $bksb->ass_cats;
    $tablecolumns = array_merge($cols, $cats);
    $tableheaders = array_merge($cols_header, $cats);

    $table = new flexible_table('bksb_ia');
    $table->define_columns($tablecolumns);
    $table->define_headers($tableheaders);
    $table->define_baseurl($baseurl);
    $table->sortable(false);
    $table->collapsible(false);
    $table->initialbars(true);
    $table->column_suppress('picture');	
    $table->column_class('picture', 'picture');
    $table->column_class('fullname', 'fullname');
    $table->set_attribute('cellspacing', '0');
    $table->set_attribute('id', 'bksb_results_group');
    $table->set_attribute('class', 'bksb_results');
    $table->set_attribute('width', '95%');
    $table->set_attribute('align', 'center');
    foreach($cats as $cat) {
        $table->no_sorting($cat);
    }
    // Get students by group (if set)
    if ($group != 0) {
        $members = groups_get_members_by_role($group, $course->id, 'u.id, u.firstname, u.lastname, u.idnumber');
        $course_students = $members[5]->users; // students are role '5'
    } else {
        $course_students = $bksb->getStudentsForCourse($course->id);
    }
	
	$course_students2 = array();
	foreach ($course_students as $student) 
		if ($bksb->getResults($student->idnumber)!== false) $course_students2[$student->id] = $student;
	
	$course_students = $course_students2;
		    
    $table->pagesize($perpage, count($course_students));
    $table->setup();
    $offset = $page * $perpage;
    $students = $bksb->filterStudentsByPage($course_students, $offset, $perpage);
    $no_students = count($students);

    if ($no_students > 0) {
        
        foreach ($students as $student) {
            Cache1::init('user-'.$student->idnumber.'-ia-results.cache', $bksb->cache_life);
            if (Cache1::cacheFileExists()) {
                $bksb_results = Cache1::getCache();
            } else {
                $bksb_results = $bksb->getResults($student->idnumber);
                Cache1::setCache($bksb_results);
            }
            if ($bksb_results === false) continue;

            $picture = $OUTPUT->user_picture($student, array('size'=>40));
            $name_html = '<a href="'.$CFG->wwwroot.'/blocks/bksb/initial_assessment.php?id='.$student->id.'&amp;course_id='.$course_id.'">'.fullname($student).'</a>';
            $col_row = array($picture, $name_html);
            $row = array_merge($col_row, $bksb_results);
            $table->add_data($row);
        }


        $per_html = '<form name="options" action="'.$baseurl.'" method="post">';
        $per_html .= '<input type="hidden" id="updatepref" name="updatepref" value="1" />';
        $per_html .= '<table id="optiontable" align="center">';
        $per_html .= '<tr align="right"><td><label for="perpage">Per page</label></td>';
        $per_html .= '<td align="left">';
        $per_html .= '<input type="text" id="perpage" name="perpage" size="1" value="'.$perpage.'" />';
        $per_html .= '</td></tr>';
        $per_html .= '<tr>';
        $per_html .= '<td colspan="2" align="right">';
        $per_html .= '<input type="submit" value="'.get_string('savepreferences').'" />';
        $per_html .= '</td></tr></table></form>';
        echo $per_html;

    }
    $table->print_html();  /// Print the whole table
    if ($no_students == 0) {
        echo '<center><p><strong>No initial assessment results for this course or filter.</strong></p></center>';
    }
}

echo $OUTPUT->footer();

?>
