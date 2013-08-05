<?php
/******************************************************************
*
*  BKBSReporting
*
*  @usage           Used to retrieve BKSB Initial and Diagnostic Assessment results
*
*  @author			Nathan Kowald
*  @since			26-08-2010
*  @lastmodified    23-07-2012
*
******************************************************************/
class BksbReporting {

    private $server;
    private $password;
    private $selected_db;
    private $num_queries;

    public $errors;
    public $debug;
    public $connection;
    public $ass_cats;
    public $ass_types;
    public $question_counts;
    public $cache_life;

    public function __construct() {

        global $CFG;

        $this->debug = false; // false by default
        $this->errors = array();
        if ($this->connection == null) $this->createBKSBConnection();
        $this->num_queries = 0;
        $this->question_counts = array();

        $this->cache_life = 604800; // 7 days default
        $cache_life = get_config('block_bksb', 'cache_life_seconds');
        if ($cache_life != 0) {
            $this->cache_life = $cache_life;
        }

        // array to hold table columns - ass cats
        $this->ass_cats = array(
            'English Results', 
            'Maths Results', 
            'ICT Results Word', 
            'ICT Results PowerPoint', 
            'ICT Results Email', 
            'ICT Results Database', 
            'ICT Results Excel', 
            'ICT Results Publisher', 
            'ICT Results Internet'
        );

        $this->ass_types = array(
            1 => 'Literacy E2',
            2 => 'Literacy E3',
            3 => 'Literacy L1',
            4 => 'Literacy L2',
            5 => 'Literacy L3',
            6 => 'Numeracy E2',
            7 => 'Numeracy E3',
            8 => 'Numeracy L1',
            9 => 'Numeracy L2',
            10 => 'Numeracy L3'
        );

    }

    private function createBKSBConnection() {
        global $CFG;

        $bksb_server = get_config('block_bksb', 'db_server');
        $bksb_user = get_config('block_bksb', 'db_user');
        $bksb_password = get_config('block_bksb', 'db_password');
        $bksb_db = get_config('block_bksb', 'db_name');

        // create an instance of the  ADO connection object
        $this->connection = new COM ("ADODB.Connection") or die("Cannot start ADO");
        // define connection string, specify database driver
        $con = "PROVIDER=SQLOLEDB;SERVER=".$bksb_server.";UID=".$bksb_user.";PWD=".$bksb_password.";DATABASE=".$bksb_db;
        try {
            $this->connection->open($con);
        } catch (Exception $e) {
            $this->errors[] = 'Incorrect BKSB database credentials. ' . $e->getMessage();
            $settings_url = $CFG->wwwroot . '/admin/settings.php?section=blocksettingbksb';
            error('Incorrect BKSB database credentials. Please update them <a href="'.$settings_url.'">here</a>.', $settings_url);
        }
    }

    
    // Legacy function
    public function getAssTypeFromNo($no) {
        if (isset($this->ass_types[$no])) {
            return $this->ass_types[$no];
        }
        return false;
    }
    
    public function getAllResults($user_id='') {
        if ($user_id == '') return false;
        $details = array();
        
        // nkowald - 2012-01-03 - If username contains single quote: escape it
        $user_id = str_replace("'", "''", $user_id);

        $query = sprintf("SELECT Result FROM dbo.bksb_IAResults WHERE UserName = '%s' ORDER BY DateCompleted DESC", $user_id);
        if ($result = $this->connection->execute($query)) {
            $this->num_queries++;
            while (!$result->EOF) {
                $details[$user_id][] = $result->fields['Result']->value;
                $result->MoveNext();
            }
            $result->Close();
        }
        $query = sprintf("SELECT WordProcessing, Spreadsheets, Databases, DesktopPublishing, Presentation, Email, General, Internet FROM dbo.bksb_ICTIAResults WHERE UserName = '%s' ORDER BY session_id DESC", $user_id);
        if ($result = $this->connection->execute($query)) {
            $this->num_queries++;
            while (!$result->EOF) {
                $details[$user_id][] = 'WordProcessing ' . $result->fields['WordProcessing']->value;
                $details[$user_id][] = 'Spreadsheets ' . $result->fields['Spreadsheets']->value;
                $details[$user_id][] = 'Databases ' . $result->fields['Databases']->value;
                $details[$user_id][] = 'DesktopPublishing ' . $result->fields['DesktopPublishing']->value;
                $details[$user_id][] = 'Presentation ' . $result->fields['Presentation']->value;
                $details[$user_id][] = 'Email ' . $result->fields['Email']->value;
                $details[$user_id][] = 'General ' . $result->fields['General']->value;
                $details[$user_id][] = 'Internet ' .$result->fields['Internet']->value;
                $result->MoveNext();
            }
            $result->Close();
        }

        return $details;
    }

    public function getResults($user_id) {
        // Create the table headings
        if (!$results = $this->getAllResults($user_id)) return false;

        $initial_results = array();
        foreach ($this->ass_cats as $cat) {
            if ($result = $this->getUserResultForCat($cat, $user_id, $results)) {
                $initial_results[] = "$result";
            } else {
                $initial_results[] = "&mdash;";
            }
        }
        return $initial_results;
    }

    public function getUserResultForCat($cat, $user_id='', Array $results) {

        if ($user_id == '' || count($results) == 0 || !in_array($cat, $this->ass_cats)) {
            return false;
        }
        // return the correct result for the given category
        $html = '';
        switch ($cat) {
            case 'English Results':
            foreach($results[$user_id] as $result) {
                if (strpos($result, 'English') !== false) {
                    $html = str_replace('English ','',$result);
                    return $html;
                }
            }
            if ($html == '') return false;
            break;

            case 'Maths Results':
            foreach($results[$user_id] as $result) {
                if (strpos($result, 'Mathematics') !== false) {
                    $html = str_replace('Mathematics ','', $result);
                    return $html;
                }
            }
            if ($html == '') return false;
            break;

            case 'ICT Results Word':
            foreach($results[$user_id] as $result) {
                if (strpos($result, 'WordProcessing') !== false) {
                    $html = str_replace('WordProcessing ','',$result);
                    return $html;
                }
            }
            if ($html == '') return false;
            break;

            case 'ICT Results PowerPoint':
            foreach($results[$user_id] as $result) {
                if (strpos($result, 'Presentation') !== false) {
                    $html = str_replace('Presentation ','',$result);
                    return $html;
                }
            }
            if ($html == '') return false;
            break;

            case 'ICT Results Email':
            foreach($results[$user_id] as $result) {
                if (strpos($result, 'Email') !== false) {
                    $html = str_replace('Email ','',$result);
                    return $html;
                }
            }
            if ($html == '') return false;
            break;

            case 'ICT Results Database':
            foreach($results[$user_id] as $result) {
                if (strpos($result, 'Databases') !== false) {
                    $html = str_replace('Databases ','',$result);
                    return $html;
                }
            }
            if ($html == '') return false;
            break;

            case 'ICT Results Excel':
            foreach($results[$user_id] as $result) {
                if (strpos($result, 'Spreadsheets') !== false) {
                    $html = str_replace('Spreadsheets ','',$result);
                    return $html;
                }
            }
            if ($html == '') return false;
            break;

            case 'ICT Results Publisher':
            foreach($results[$user_id] as $result) {
                if (strpos($result, 'DesktopPublishing') !== false) {
                    $html = str_replace('DesktopPublishing ','',$result);
                    return $html;
                }
            }
            if ($html == '') return false;
            break;

            case 'ICT Results Internet':
            foreach($results[$user_id] as $result) {
                if (strpos($result, 'Internet') !== false) {
                    $html = str_replace('Internet ','',$result);
                    return $html;
                }
            }
            if ($html == '') return false;
            break;
        }
    }

    public function getDiagnosticOverview($user_id='', $assessment_no='') {

        if (!is_numeric($assessment_no) || !is_numeric($user_id)) {
            return false;
        }
        $assessment = $this->ass_types[$assessment_no];
        $query = sprintf("SELECT curric_ref, TrackingComment FROM dbo.vw_student_curric_bestScoreAndComment WHERE Assessment = '%s' AND userName = '%d'", $assessment, $user_id);

        if ($result = $this->connection->execute($query)) {
            $this->num_queries++;
            $overview = array();
            $curric_refs = array();
            while (!$result->EOF) {
                $overview[$result->fields['curric_ref']->value] = ($result->fields['TrackingComment']->value == NULL) ? 'Tick' : $result->fields['TrackingComment']->value;
                $curric_refs[] = $result->fields['curric_ref']->value;
                $result->MoveNext();
            }
            $result->Close();

            // convert curric refs into CSV
            $refs_csv = implode("','",$curric_refs);
            $refs_csv = "'" . $refs_csv . "'";
            // Get correct order for all curric_refs
            $ordered_results = array();
            $query = sprintf("SELECT curric_ref, Title, report_pos FROM dbo.bksb_CurricCodes WHERE curric_ref IN (%s) ORDER BY report_pos ASC", $refs_csv);
            if ($result = $this->connection->execute($query)) {
                $this->num_queries++;
                while (!$result->EOF) {
                    // curriculum reference
                    $ref = $result->fields['curric_ref']->value;
                    // result - retrieved from first query
                    $grade = $overview[$ref];

                    $ordered_results[] = array(
                        'curric_ref' => $ref,
                        'title' => $result->fields['Title']->value,
                        'result' => $grade
                    );
                    $result->MoveNext();
                }
                $result->Close();
            }
            if (count($ordered_results) > 0) {
                return $ordered_results;
            } else {
                return false;
            }
        }
    }

    public function getBestScores($user_id) {
        $scores = array();
        if (!is_numeric($user_id)) return $scores;
        $query = sprintf("SELECT bs.Assessment, bs.curric_ref, bs.TrackingComment, cc.Title FROM vw_student_curric_bestScoreAndComment AS bs INNER JOIN bksb_CurricCodes AS cc ON bs.curric_ref = cc.curric_ref WHERE bs.userName = '%d' ORDER BY bs.Assessment, cc.report_pos", $user_id);

        if ($result = $this->connection->execute($query)) {
            $this->num_queries++;
            while (!$result->EOF) {
                $value = ($result->fields['TrackingComment']->value == NULL) ? 'Tick' : $result->fields['TrackingComment']->value;
                $scores[$result->fields['Assessment']->value][] = array($result->fields['curric_ref']->value, $value, $result->fields['Title']->value);
                $result->MoveNext();
            }
        }
        $result->Close();
        return $scores;
    }

     public function getDiagnosticResults($user_id='', $assessment_no='', Array $results) {

        if (!is_numeric($assessment_no) || !is_numeric($user_id)) {
			return false;
        }
        $assessment = $this->ass_types[$assessment_no];
        $no_questions = $this->getNoQuestions($assessment_no);

        // Get results for this assessment
        if (!isset($results[$assessment])) {
		    //echo "failed no results";
			return false;
		}

        // Sometimes user might not have completed all questions, add dashes if this is the case
        if (count($results[$assessment]) < $no_questions) {
            for ($i=0; $i<$no_questions; $i++) {
                if (!isset($results[$assessment][$i])) {
                    $results[$assessment][$i] = array('', '', '');
                }
            }
        }

        return $results[$assessment];
    }

    // Filter assessment types down to only the diagnostics the user has data for.
    public function filterAssessmentsFromSessions($user_sessions) {
        $existing_diags = $this->ass_types;
        foreach ($existing_diags as $key => $value) {
            if (!array_key_exists($value, $user_sessions)) {
                unset($existing_diags[$key]);
            }
        }
        return $existing_diags;
    }

    public function getHTMLResult($result='') {
        $html = '&ndash;';
        switch($result) {
            case 'P':
                $html = '<span class="bksb_passed">P</span>';
                break;

            case 'Tick':
                $html = '<img src="pix/tick.png" alt="passed" width="20" height="19" />';
                break;

            case 'X':
                $html = '<img src="pix/red-x.gif" alt="Not Yet Passed" width="15" height="15" />';
                break;

        }
        return $html;
    }

    public function getNoAssessmentQuestions() {
        if (count($this->question_counts) > 0) return $this->question_counts;

        $query = "SELECT COUNT(report_pos) AS no_questions, Subject + ' ' + [Level] AS assessment_name FROM bksb_CurricCodes WHERE ([Level] IS NOT NULL) GROUP BY Subject, [Level] ORDER BY Subject, [Level]"; 
        if ($result = $this->connection->execute($query)) {
            $this->num_queries++;
            while (!$result->EOF) {
                $assessment_type = $this->renameDiagName($result->fields['assessment_name']->value);
                $this->question_counts[$assessment_type] = $result->fields['no_questions']->value;
                $result->MoveNext();
            }
        }
        $result->Close();
        return $this->question_counts;
    }

    public function getNoQuestions($assessment_no='') {

        // Assessment numbers map to assessment types
        if (!is_numeric($assessment_no) || $assessment_no == '') {
            return false;
        }
        $type = $this->ass_types[$assessment_no];

        $counts = $this->getNoAssessmentQuestions();
        if (isset($counts[$type])) {
            return $counts[$type];
        }
    }

    public function getAssDetails($assessment_no='') {

        // Assessment numbers map to assessment types
        if (is_numeric($assessment_no) && $assessment_no != '') {
            switch($assessment_no) {
                case 1:
                    $assessment = 'Literacy E2';
                    $query = "SELECT curric_ref, Title FROM dbo.bksb_CurricCodes WHERE Subject = 'Lit' AND Level = 'E2' ORDER BY report_pos";
                    break;

                case 2:
                    $assessment = 'Literacy E3';
                    $query = "SELECT curric_ref, Title FROM dbo.bksb_CurricCodes WHERE Subject = 'Lit' AND Level = 'E3' ORDER BY report_pos";
                    break;

                case 3:
                    $assessment = 'Literacy L1';
                    $query = "SELECT curric_ref, Title FROM dbo.bksb_CurricCodes WHERE Subject = 'Lit' AND Level = 'L1' ORDER BY report_pos";
                    break;

                case 4:
                    $assessment = 'Literacy L2';
                    $query = "SELECT curric_ref, Title FROM dbo.bksb_CurricCodes WHERE Subject = 'Lit' AND Level = 'L2' ORDER BY report_pos";
                    break;

                case 5:
                    $assessment = 'Literacy L3';
                    $query = "SELECT curric_ref, Title FROM dbo.bksb_CurricCodes WHERE Subject = 'Lit' AND Level = 'L3' ORDER BY report_pos";
                    break;

                case 6:
                    $assessment = 'Numeracy E2';
                    $query = "SELECT curric_ref, Title FROM dbo.bksb_CurricCodes WHERE Subject = 'num' AND Level = 'E2' ORDER BY report_pos";
                    break;

                case 7:
                    $assessment = 'Numeracy E3';
                    $query = "SELECT curric_ref, Title FROM dbo.bksb_CurricCodes WHERE Subject = 'Num' AND Level = 'E3' ORDER BY report_pos";
                    break;

                case 8:
                    $assessment = 'Numeracy L1';
                    $query = "SELECT curric_ref, Title FROM dbo.bksb_CurricCodes WHERE Subject = 'Num' AND Level = 'L1' ORDER BY report_pos";
                    break;

                case 9:
                    $assessment = 'Numeracy L2';
                    $query = "SELECT curric_ref, Title FROM dbo.bksb_CurricCodes WHERE Subject = 'Num' AND Level = 'L2' ORDER BY report_pos";
                    break;

                case 10:
                    $assessment = 'Numeracy L3';
                    $query = "SELECT curric_ref, Title FROM dbo.bksb_CurricCodes WHERE Subject = 'Num' AND Level = 'L3' ORDER BY report_pos";
                    break;

                default:
                    return false;
            }

            // Perform SQL query here
            if ($result = $this->connection->execute($query)) {
                $this->num_queries++;
                $ordered_results = array();
                while (!$result->EOF) {
                    $title = $result->fields['Title']->value;
                    $curric_ref = $result->fields['curric_ref']->value;
                    $ordered_results[] = array($title, $curric_ref);
                    $result->MoveNext();
                }
                $result->Close();
                return $ordered_results;
            } // if
        } else {
            return false;
        }

    }

    public function renameDiagName($name) {
        // Strip Diagnostic
        $rename = $name;
        $rename = str_ireplace(' Diagnostic', '', $rename);
        $rename = str_ireplace('Mathematics', 'Numeracy', $rename);
        $rename = str_ireplace('English', 'Literacy', $rename);
        // If one of the above has been replaced: return the replacement text
        if ($name != $rename) return $rename;
        $rename = str_ireplace('lit', 'Literacy', $rename);
        $rename = str_ireplace('num', 'Numeracy', $rename);
        return $rename;
    }

    public function getBksbDiagSessions($username='') {
        $sessions = array();
        $query = sprintf("SELECT bs.session_id, ba.[assessment name] FROM bksb_Sessions AS bs LEFT OUTER JOIN bksb_Assessments AS ba ON bs.assessment_id = ba.ass_ref WHERE (bs.userName = '%d') AND (ba.[assessment report] = 'Reports/DiagReport.aspx') AND (ba.[assessment group] = 1) AND (bs.status = 'Complete') ORDER BY bs.dateCreated", $username);
        if ($result = $this->connection->execute($query)) {
            $this->num_queries++;
            while (!$result->EOF) {
                $diag_name = $this->renameDiagName($result->fields['assessment name']->value);
                $sessions[$diag_name] = $result->fields['session_id']->value;
                $result->MoveNext();
            }
            $result->Close();
        }
        return $sessions;
    }
    
    public function getBksbSessionNo($username='', $ass_type=0) {
        if ($session_id != '') {
            return $session_id;
        } else {
            return false;
        }
    }
    
	
	public function getBksbSessionNo_bypass($username) {
		//RPM dig out the session number so it can be includede in a URL to bksb and allow for results to be viewed.
		$query = sprintf("SELECT bs.session_id, ba.[assessment name] FROM bksb_Sessions AS bs LEFT OUTER JOIN bksb_Assessments AS ba ON bs.assessment_id = ba.ass_ref WHERE (bs.userName = '%d') AND (ba.[assessment report] = 'Reports/DiagReport.aspx') AND (ba.[assessment group] = 1) AND (bs.status = 'Complete') ORDER BY bs.dateCreated", $username);
        if ($result = $this->connection->execute($query)) {
			while (!$result->EOF) {
                $sess = $result->fields['session_id']->value;
                $result->MoveNext();
            }
            $result->Close();
			return $sess;
		}
		
    }
	
	
    public function getBksbPercentage($session_id) {

        // Get the percentage - the BKSB way
        $query = sprintf("SELECT ((SUM(score) / SUM(out_of)) * 100) AS percentage FROM dbo.bksb_QuestionResponses WHERE session_id = '%d'", $session_id);
        
        // Perform SQL query here
        if ($result = $this->connection->execute($query)) {
            $this->num_queries++;
            $percentage = '';
            while (!$result->EOF) {
                $percentage = $result->fields['percentage']->value;
                $percentage = round($percentage, 0);
                $result->MoveNext();
            }
            $result->Close();
        }
        
        if ($percentage != '' || $percentage == 0) {
            return $percentage;
        } else {
            return '-';
        }
        
        // old 'best results' way of getting percentage
        // nkowald 2010-10-05 - Get no of answered questions
        /*
        $x = 0; // incorrect
        $p = 0; // correct
        $un = 0; // unanswered
        foreach ($bksb_results as $res) {
            if ($res == 'X') {
                $x++;
            } else if ($res == 'P') {
                $p++;
            } else {
                $un++;
            }
        }
        // Percentage of right answers can be worked out by ((($p / ($x + $p)) * 100)
        $percentage = round( (($p / ($x + $p)) * 100), 0);
        */
        
    }
    
    // Checking results return in all required tables
    /*
    - bksb_Users			(userName)
    - bksb_GroupMembership	(UserName)
    - bksb_IAResults		(UserName)
    - bksb_ICTIAResults		(UserName)
    - bksb_Sessions			(userName)
    */
    
    // Performs simple queries on above tables querying for username
    // If all usernames found we can update all usernames with correct idnumber
    
    public function checkMatchingUsername($username='') {
    
        $username_exists = array(FALSE, FALSE, FALSE, FALSE, FALSE);
        
        if ($username != '') {

            // bksb_Users check
            $query1 = sprintf("SELECT userName FROM dbo.bksb_Users WHERE userName = '%s'", $username);
            if ($result1 = $this->connection->execute($query1)) {
                $this->num_queries++;
                $user_found = '';
                while (!$result1->EOF) {
                    $user_found = $result1->fields['userName']->value;
                    $result1->MoveNext();
                }
                $result1->Close();
                if ($user_found != '') {
                    $username_exists[0] = TRUE;
                }
            }
            
            // bksb_GroupMembership check
            $query2 = sprintf("SELECT UserName FROM dbo.bksb_GroupMembership WHERE UserName = '%s'", $username);
            if ($result2 = $this->connection->execute($query2)) {
                $this->num_queries++;
                $user_found = '';
                while (!$result2->EOF) {
                    $user_found = $result2->fields['UserName']->value;
                    $result2->MoveNext();
                }
                $result2->Close();
                if ($user_found != '') {
                    $username_exists[1] = TRUE;
                }
            }
            
            // bksb_IAResults check
            $query3 = sprintf("SELECT UserName FROM dbo.bksb_IAResults WHERE UserName = '%s'", $username);
            if ($result3 = $this->connection->execute($query3)) {
                $this->num_queries++;
                $user_found = '';
                while (!$result3->EOF) {
                    $user_found = $result3->fields['UserName']->value;
                    $result3->MoveNext();
                }
                $result3->Close();
                if ($user_found != '') {
                    $username_exists[2] = TRUE;
                }
            }
            
            // bksb_ICTIAResults check
            $query4 = sprintf("SELECT UserName FROM dbo.bksb_ICTIAResults WHERE UserName = '%s'", $username);
            if ($result4 = $this->connection->execute($query4)) {
                $this->num_queries++;
                $user_found = '';
                while (!$result4->EOF) {
                    $user_found = $result4->fields['UserName']->value;
                    $result4->MoveNext();
                }
                $result4->Close();
                if ($user_found != '') {
                    $username_exists[3] = TRUE;
                }
            }
            
            // bksb_Sessions check
            $query5 = sprintf("SELECT userName FROM dbo.bksb_Sessions WHERE userName = '%s'", $username);
            if ($result5 = $this->connection->execute($query5)) {
                $this->num_queries++;
                $user_found = '';
                while (!$result5->EOF) {
                    $user_found = $result5->fields['userName']->value;
                    $result5->MoveNext();
                }
                $result5->Close();
                if ($user_found != '') {
                    $username_exists[4] = TRUE;
                }
            }
            
            // We have results: return array
            return $username_exists;
            
        } else {
            return FALSE;
        }

    }
    
    // Expects an array with the following properties:
    /*
    array(5) {
      ["username"]=> string(6) "151177"
      ["firstname"]=> string(5) "0yema"
      ["lastname"]=> string(14) "fatuma milambo"
      ["dob"]=> => "01/01/1900"
      ["postcode"] => "N15 4RU"
      ["id"]=> int(6354)
      ["reason"]=> string(33) "ID Number doesn't exist in Moodle" // not needed here
    }
    */
    public function updateInvalidUsers(array $invalid_users) {
    
        $new_usernames = array();
        $no_users_updated = 0;
        
        global $CFG, $DB;
        
        // Before we return invalid BKSB users lets search moodle user table by first and last names to see if we get matched: then update bksb
        // Add new usernames to an array to remove duplicates at the end
        foreach ($invalid_users as $key => $user) {
        
            $old_username = $user['username'];
            $firstname = $user['firstname'];
            $lastname = $user['lastname'];
            
            if ($user_matches = $DB->get_records('user', array('firstname' => $firstname, 'lastname' => $lastname))) {

                if (count($user_matches) === 1) {
                    foreach ($user_matches as $match) {
                        $new_usernames[] = $match->idnumber;
                        $this->updateBksbData($old_username, $match->idnumber, $firstname, $lastname);
                        // Unset invalid user as if one of these tables doesn't exist, might just mean it doesn't exist
                        unset($invalid_users[$key]);
                        $no_users_updated++;
                    }
                }

            } else {
            
                // nkowald - 2011-08-22 - No match found on first and lastname in Moodle, let's try matching on postcode and date of birth (both means this person = found)
                $user_match_idnumber = '';
                $postcode = '';
                $dob = '';
                
                // Clean postcode for matching: uppercase, strip spaces, trim edges
                if ($user['postcode'] != '') {
                    $postcode = trim(str_replace(' ', '', strtoupper($user['postcode'])));
                }
                // Date format should be 14/12/1958 - validation on bksb means this is fine
                if ($user['dob'] != '') {
                    $dob = $user['dob'];
                }
                
                // If both postcode and dob exists, this is enough info to search for a match
                $query = '';
                if ($postcode != '' && $dob != '') {
                    $query = sprintf("SELECT idnumber, firstname, lastname FROM %suser WHERE dob = '%s' AND REPLACE(postcode, ' ', '') = '%s'", $CFG->prefix, $dob, $postcode);
                } else if ($dob != '' && $postcode == '') {
                    // Search on dob and firstname
                    $query = sprintf("SELECT idnumber, firstname, lastname FROM %suser WHERE dob = '%s' AND LOWER(firstname) = '%s'", $CFG->prefix, $dob, strtolower($user['firstname']));
                } else if ($postcode == '' && $dob != '') {
                    // Search on postcode and firstname
                    $query = sprintf("SELECT idnumber, firstname, lastname FROM %suser WHERE LOWER(firstname) = '%s' AND REPLACE(postcode, ' ', '') = '%s'", $CFG->prefix, $user['firstname'], $postcode);
                }

                if ($query == '') continue;
                
                $new_username = '';
                if ($matches = $DB->get_records_sql($query)) {
                    foreach($matches as $match) {
                        $new_username = $match->idnumber;
                        $firstname = $match->firstname;
                        $lastname = $match->lastname;
                    }
                }
                
                if ($new_username != '') {
                    
                    // We found a match looking in EBS, great! Let's update BKSB
                    $new_usernames[] = $new_username;
                    
                    $this->updateBksbData(ms_escape_string($old_username), ms_escape_string($new_username), ms_escape_string($firstname), ms_escape_string($lastname));
                    
                    // Unset invalid user as if one of these tables doesn't exist, might just mean it doesn't exist
                    unset($invalid_users[$key]);
                    $no_users_updated++;
                }
                
            }

        } // foreach
        
        // Finally, return the invalids
        if ($no_users_updated > 0) {
            $user_txt = ($no_users_updated > 1) ? 'users' : 'user';
            echo "Updated $no_users_updated $user_txt!" . PHP_EOL;
            
            // Remove duplicates
            $this->removeDuplicateUsers(TRUE, $new_usernames);
        } else {
            echo "No users were updated" . PHP_EOL;
        }
    }
    
    
    // nkowald - 2011-01-10 - Get all users from BKSB
    // $firstname - Get invalid users by firstname
    // $lastname - Get invalid users by lastname
    
    public function getInvalidBksbUsers($firstname='', $lastname='', $order_field='userName') {
    
        global $DB;
        // Can be memory intensive, increase limit
        ini_set('memory_limit', '500M');
        
        // Escape firstname and lastname for SQL query
        $firstname = ($firstname != '') ? trim($firstname) : $firstname;
        $lastname = ($lastname != '') ? trim($lastname) : $lastname;
        
        // Check for valid $order_field
        $valid_orders = array('userName', 'FirstName', 'LastName', 'DOB', 'Postcode');
        
        // if firstname is given and lastname given
        if ($firstname != '' && $lastname != '') {
            $query = sprintf("SELECT user_id, userName, FirstName, LastName, DOB, PostcodeA as Postcode FROM dbo.bksb_Users WHERE FirstName = '%s' AND LastName = '%s' ORDER BY FirstName ASC", $firstname, $lastname);	
        } else if ($firstname != '' && $lastname == '') {
            $query = sprintf("SELECT user_id, userName, FirstName, LastName, DOB, PostcodeA as Postcode FROM dbo.bksb_Users WHERE FirstName = '%s' ORDER BY FirstName ASC", $firstname);	
        } else if ($firstname == '' && $lastname != '') {
            $query = sprintf("SELECT user_id, userName, FirstName, LastName, DOB, PostcodeA as Postcode FROM dbo.bksb_Users WHERE LastName = '%s' ORDER BY LastName ASC", $lastname);
        } else {
            if (in_array($order_field, $valid_orders)) {
                $order = "$order_field ASC";
            } else {
                $order = 'Postcode DESC';
            }
            $query = "SELECT user_id, userName, FirstName, LastName, DOB, PostcodeA as Postcode FROM dbo.bksb_Users ORDER BY $order";
        }
        
        //$query = "SELECT user_id, userName, FirstName, LastName FROM (SELECT Row_Number() OVER (ORDER BY userName) AS RowIndex, * FROM bksb_Users) AS Sub WHERE Sub.RowIndex >= 1 AND Sub.RowIndex <= 1000 ORDER BY FirstName";
        
        if ($result = $this->connection->execute($query)) {
            $this->num_queries++;
            $invalid_users = array();
            
            while (!$result->EOF) {

                // Do checks here instead of later
                $username = addslashes($result->fields['userName']->value);
                $invalid = false;
                
                if (!is_numeric($username)) {
                    $reason = 'Non-numeric username';
                    $invalid = true;
                } 
                /*
                else if (!$DB->record_exists('user', array('idnumber' => $username))) {
                    $reason = 'ID Number doesn\'t exist in Moodle';
                    $invalid = true;
                }
                */
            
                if ($invalid === true) {
                    $invalid_users[] = array(
                    'username' => $username, 
                    'firstname' => addslashes($result->fields['FirstName']->value),
                    'lastname' => addslashes($result->fields['LastName']->value),
                    'dob' => ($result->fields['DOB']->value != '01/01/1900') ? $result->fields['DOB']->value : "",
                    'postcode' => ($result->fields['Postcode']->value != '') ? strtoupper($result->fields['Postcode']->value) : "",
                    'id' => addslashes($result->fields['user_id']->value),
                    'reason' => $reason
                    );
                }
                $result->MoveNext();
            }
        }
        $result->Close();
        
        return $invalid_users;
    }

    
    // nkowald - 2010-01-10 - Get a list of all bksb groups
    public function getBksbGroups() {
        $query = "SELECT group_id FROM dbo.bksb_Groups";
        if ($result = $this->connection->execute($query)) {
                $this->num_queries++;
                while (!$result->EOF) {
                    $groups[] = $result->fields['group_id']->value;				
                    $result->MoveNext();
                }
                $result->Close();
                return $groups;
        } else {
            return false;
        }
    }
    
    public function getDiagnosticOverviewsForGroup($group_name='') {
    
        if ($group_name != '') {
            $query = sprintf("SELECT DISTINCT userName FROM dbo.bksb_Sessions WHERE (assessment_id IN (SELECT ass_ref FROM dbo.bksb_Assessments WHERE ([assessment group] = 1))) AND (userName IN (SELECT UserName FROM dbo.bksb_GroupMembership WHERE (group_id = '%s') AND status='Complete') ) ORDER BY userName", $group_name);
            
            if ($result = $this->connection->execute($query)) {
                    $this->num_queries++;
                    $user_diag = array();

                    // Set total counts
                    $user_diag['total_literacy_e2'] = $user_diag['total_literacy_e3'] = $user_diag['total_literacy_l1'] = $user_diag['total_literacy_l2'] = $user_diag['total_literacy_l3'] = 0;
                    $user_diag['total_numeracy_e2'] = $user_diag['total_numeracy_e3'] = $user_diag['total_numeracy_l1'] = $user_diag['total_numeracy_l2'] = $user_diag['total_numeracy_l3'] = 0;
                    
                    while (!$result->EOF) {
                    
                        $username = $result->fields['userName']->value;
                        
                        $user_diag[$username]['user_name'] = $result->fields['userName']->value;	
                        //$user_diag[$username]['session_id'] = $result->fields['session_id']->value;															
                        //$user_diag[$username]['status'] = $result->fields['status']->value;				
                        
                        // Add Level
                        $ass_query = sprintf("SELECT DISTINCT Assessment FROM dbo.vw_student_curric_bestScoreAndComment WHERE (userName = '%s')", $username);
                        if ($result_ass = $this->connection->execute($ass_query)) {
                            $this->num_queries++;
                            while (!$result_ass->EOF) {
                                $assessments[] = $result_ass->fields['Assessment']->value;
                                $result_ass->MoveNext();
                            }
                            
                            // Literacy E2
                            $user_diag[$username]['literacy_e2'] = (in_array('Literacy E2', $assessments)) ? 'Yes' : '-';
                            if (in_array('Literacy E2', $assessments)) { $user_diag['total_literacy_e2']++; }
                            // Literacy E3
                            $user_diag[$username]['literacy_e3'] = (in_array('Literacy E3', $assessments)) ? 'Yes' : '-';
                            if (in_array('Literacy E3', $assessments)) { $user_diag['total_literacy_e3']++; }
                            // Literacy L1
                            $user_diag[$username]['literacy_l1'] = (in_array('Literacy L1', $assessments)) ? 'Yes' : '-';
                            if (in_array('Literacy L1', $assessments)) { $user_diag['total_literacy_l1']++; }
                            // Literacy L2
                            $user_diag[$username]['literacy_l2'] = (in_array('Literacy L2', $assessments)) ? 'Yes' : '-';
                            if (in_array('Literacy L2', $assessments)) { $user_diag['total_literacy_l2']++; }
                            // Literacy L3
                            $user_diag[$username]['literacy_l3'] = (in_array('Literacy L3', $assessments)) ? 'Yes' : '-';
                            if (in_array('Literacy L3', $assessments)) { $user_diag['total_literacy_l3']++; }
                            
                            // Numeracy E2
                            $user_diag[$username]['numeracy_e2'] = (in_array('Numeracy E2', $assessments)) ? 'Yes' : '-';
                            if (in_array('Numeracy E2', $assessments)) { $user_diag['total_numeracy_e2']++; }
                            // Numeracy E3
                            $user_diag[$username]['numeracy_e3'] = (in_array('Numeracy E3', $assessments)) ? 'Yes' : '-';
                            if (in_array('Numeracy E3', $assessments)) { $user_diag['total_numeracy_e3']++; }
                            // Numeracy L1
                            $user_diag[$username]['numeracy_l1'] = (in_array('Numeracy L1', $assessments)) ? 'Yes' : '-';
                            if (in_array('Numeracy L1', $assessments)) { $user_diag['total_numeracy_l1']++; }
                            // Numeracy L2
                            $user_diag[$username]['numeracy_l2'] = (in_array('Numeracy L2', $assessments)) ? 'Yes' : '-';
                            if (in_array('Numeracy L2', $assessments)) { $user_diag['total_numeracy_l2']++; }
                            // Numeracy L3
                            $user_diag[$username]['numeracy_l3'] = (in_array('Numeracy L3', $assessments)) ? 'Yes' : '-';
                            if (in_array('Numeracy L3', $assessments)) { $user_diag['total_numeracy_l3']++; }
                            
                            $assessments = array();
                        }
                        
                        $result->MoveNext();
                    }
                    
                    $result->Close();

                    // return all user diags for this given group
                    return $user_diag;
            }
            // Now we want to add some data to the arrays
            
        } else {
            return false;
        }
        
    }
    
    // nkowald - Need a new method to get users per group
    private function getUsersForGroup($group_name = '') {
    
        $query = sprintf("SELECT UserName FROM dbo.bksb_GroupMembership WHERE (group_id = '%s')", $group_name);
        
        if ($result = $this->connection->execute($query)) {
            $this->num_queries++;
            $users = array();

            while (!$result->EOF) {
                $user = $result->fields['UserName']->value;
                // Need to escape single quotes
                $user = str_replace("'", "''", $user);
                $users[] = $user;
                $result->MoveNext();
            }
            $result->Close();
            
            return $users;
        } else {
            return false;
        }
        
    }
    
    public function getIAForGroup($group_name='', $ia_type = '',  $unix_start = '', $unix_end = '') {
        
        if ($group_name == '' || $ia_type == '') return false;

        // get users for given group
        if ($users = $this->getUsersForGroup($group_name)) {
            // convert to csv
            $group_users = implode(',', $users);
            $group_users = "'" . str_replace(",", "','", $group_users) . "'";
        } else {
            return false;
        }
        
        $group_sessions = '';
        // Now we have a list of the users from the group, if valid start and end date given, cut down to users with a complete session between date range
        if (($unix_start != '' && is_numeric($unix_start)) && ($unix_end != '' && is_numeric($unix_end))) {
        
            /* 
            Convert start into SQL smalldatetime format
             In Microsoft SQL Server Management Studio Express the date displays as d/m/Y but
             it actually requires you to use m/d/Y format in the query.
            */ 
            
            $sdt_start = date('m/d/Y H:i:s', $unix_start);
            $sdt_end = date('m/d/Y H:i:s', $unix_end);
            
            $query = sprintf("SELECT session_id FROM bksb_Sessions WHERE (status = 'Complete') AND (dateCreated >= '%s') AND (dateCreated <= '%s') AND userName IN (%s)",
                $sdt_start,
                $sdt_end,
                $group_users
            );
            
            if ($result = $this->connection->execute($query)) {
                $this->num_queries++;
                $sessions = array();

                while (!$result->EOF) {
                    $sessions[] = $result->fields['session_id']->value;
                    $result->MoveNext();
                }
                $result->Close();
                
                // Finally, update group_users to be the filtered list of users 
                $group_sessions = implode(',', $sessions);
                $group_sessions = "'" . str_replace(",", "','", $group_sessions) . "'";
            }
            
        }
        
        // Use this query if English or Maths selected
        if ($ia_type == 'English' || $ia_type == 'Mathematics') {

            if ($group_sessions != '') {
                //$query = "SELECT UserName, Result FROM dbo.bksb_IAResults WHERE (Session_id IN ($group_sessions)) ORDER BY UserName";
                $query = sprintf("SELECT iar.UserName, u.FirstName, u.LastName, iar.Result FROM bksb_IAResults AS iar LEFT OUTER JOIN bksb_Users AS u ON LTRIM(RTRIM(iar.UserName)) = LTRIM(RTRIM(u.userName)) WHERE (iar.Session_id IN (%s)) ORDER BY iar.UserName", $group_sessions);
            } else {
                //$query = "SELECT UserName, Result FROM dbo.bksb_IAResults WHERE (UserName IN ($group_users)) ORDER BY UserName";
                $query = sprintf("SELECT iar.UserName, u.FirstName, u.LastName, iar.Result FROM bksb_IAResults AS iar LEFT OUTER JOIN bksb_Users AS u ON LTRIM(RTRIM(iar.UserName)) = LTRIM(RTRIM(u.userName)) WHERE (iar.UserName IN (%s)) ORDER BY iar.UserName", $group_users);
            }

            if ($result = $this->connection->execute($query)) {
                $this->num_queries++;
                $user_ass = array();

                while (!$result->EOF) {
                    $username = $result->fields['UserName']->value;
                    $user_ass[$username]['user_name'] = $username;
                    $user_ass[$username]['name'] = $result->fields['FirstName']->value . ' ' . $result->fields['LastName']->value;
                    $user_ass[$username]['results'][] = $result->fields['Result']->value;
                    $result->MoveNext();
                }
                
                // Set total counts
                $user_ass['total_literacy_e2'] = $user_ass['total_literacy_e3'] = $user_ass['total_literacy_l1'] = $user_ass['total_literacy_l2'] = $user_ass['total_literacy_l3'] = 0;
                $user_ass['total_numeracy_e2'] = $user_ass['total_numeracy_e3'] = $user_ass['total_numeracy_l1'] = $user_ass['total_numeracy_l2'] = $user_ass['total_numeracy_l3'] = 0;
                
                foreach ($user_ass as $user) {
                
                    $username = $user['user_name'];

                    // English E2
                    $user_ass[$username]['literacy_e2'] = (in_array('English Entry 2', $user_ass[$username]['results'])) ? 'Yes' : '-';
                    if (in_array('English Entry 2', $user_ass[$username]['results'])) { $user_ass['total_literacy_e2']++; }
                    // English E3
                    $user_ass[$username]['literacy_e3'] = (in_array('English Entry 3', $user_ass[$username]['results'])) ? 'Yes' : '-';
                    if (in_array('English Entry 3', $user_ass[$username]['results'])) { $user_ass['total_literacy_e3']++; }
                    // English L1
                    $user_ass[$username]['literacy_l1'] = (in_array('English Level 1', $user_ass[$username]['results'])) ? 'Yes' : '-';
                    if (in_array('English Level 1', $user_ass[$username]['results'])) { $user_ass['total_literacy_l1']++; }
                    // English L2
                    $user_ass[$username]['literacy_l2'] = (in_array('English Level 2', $user_ass[$username]['results'])) ? 'Yes' : '-';
                    if (in_array('English Level 2', $user_ass[$username]['results'])) { $user_ass['total_literacy_l2']++; }
                    // English L3
                    $user_ass[$username]['literacy_l3'] = (in_array('English Level 3', $user_ass[$username]['results'])) ? 'Yes' : '-';
                    if (in_array('English Level 3', $user_ass[$username]['results'])) { $user_ass['total_literacy_l3']++; }

                    // Mathematics E2
                    $user_ass[$username]['numeracy_e2'] = (in_array('Mathematics Entry 2', $user_ass[$username]['results'])) ? 'Yes' : '-';
                    if (in_array('Mathematics Entry 2', $user_ass[$username]['results'])) { $user_ass['total_numeracy_e2']++; }
                    // Mathematics E3
                    $user_ass[$username]['numeracy_e3'] = (in_array('Mathematics Entry 3', $user_ass[$username]['results'])) ? 'Yes' : '-';
                    if (in_array('Mathematics Entry 3', $user_ass[$username]['results'])) { $user_ass['total_numeracy_e3']++; }
                    // Mathematics L1
                    $user_ass[$username]['numeracy_l1'] = (in_array('Mathematics Level 1', $user_ass[$username]['results'])) ? 'Yes' : '-';
                    if (in_array('Mathematics Level 1', $user_ass[$username]['results'])) { $user_ass['total_numeracy_l1']++; }
                    // Mathematics L2
                    $user_ass[$username]['numeracy_l2'] = (in_array('Mathematics Level 2', $user_ass[$username]['results'])) ? 'Yes' : '-';
                    if (in_array('Mathematics Level 2', $user_ass[$username]['results'])) { $user_ass['total_numeracy_l2']++; }
                    // Mathematics L3
                    $user_ass[$username]['numeracy_l3'] = (in_array('Mathematics Level 3', $user_ass[$username]['results'])) ? 'Yes' : '-';
                    if (in_array('Mathematics Level 3', $user_ass[$username]['results'])) { $user_ass['total_numeracy_l3']++; }

                }
                
                $result->Close();
                // return all user initial assessments for this given group
                return $user_ass;
            }
        } else if ($ia_type == 'ICT') {
            
            if ($group_sessions != '') {
                $query = sprintf("SELECT ict.UserName, u.FirstName, u.LastName, ict.WordProcessing, ict.Spreadsheets, ict.Databases, ict.DesktopPublishing, ict.Presentation, ict.Email, ict.General, ict.Internet FROM bksb_ICTIAResults AS ict LEFT OUTER JOIN bksb_Users AS u ON LTRIM(RTRIM(ict.UserName)) = LTRIM(RTRIM(u.userName)) WHERE (ict.session_id IN (%s)) ORDER BY ict.UserName", $group_sessions);
                
            } else {
                $query = sprintf("SELECT ict.UserName, u.FirstName, u.LastName, ict.WordProcessing, ict.Spreadsheets, ict.Databases, ict.DesktopPublishing, ict.Presentation, ict.Email, ict.General, ict.Internet FROM bksb_ICTIAResults AS ict LEFT OUTER JOIN bksb_Users AS u ON LTRIM(RTRIM(ict.UserName)) = LTRIM(RTRIM(u.userName)) WHERE (ict.UserName IN (%s)) ORDER BY ict.UserName", $group_users);
            }

                
            if ($result = $this->connection->execute($query)) {
                $this->num_queries++;
            
                $user_ass = array();

                while (!$result->EOF) {
                
                    $valid_types = array('word_processing', 'spreadsheets', 'databases', 'desktop_publishing', 'presentation', 'email', 'general', 'internet');
                    
                    $username = $result->fields['UserName']->value;
                    $user_ass[$username]['user_name'] = $username;
                    $user_ass[$username]['name'] = $result->fields['FirstName']->value . ' ' . $result->fields['LastName']->value;
                    $user_ass[$username]['results']['word_processing'] = $result->fields['WordProcessing']->value;
                    $user_ass[$username]['results']['spreadsheets'] = $result->fields['Spreadsheets']->value;
                    $user_ass[$username]['results']['databases'] = $result->fields['Databases']->value;
                    $user_ass[$username]['results']['desktop_publishing'] = $result->fields['DesktopPublishing']->value;
                    $user_ass[$username]['results']['presentation'] = $result->fields['Presentation']->value;
                    $user_ass[$username]['results']['email'] = $result->fields['Email']->value;
                    $user_ass[$username]['results']['general'] = $result->fields['General']->value;
                    $user_ass[$username]['results']['internet'] = $result->fields['Internet']->value;

                    $result->MoveNext();
                }
                
                // Set total counts
                //$user_ass['total_word_processing'] = $user_ass['total_spreadsheets'] = $user_ass['total_databases'] = $user_ass['total_desktop_publishing'] = $user_ass['total_presentation'] = $user_ass['total_email'] = $user_ass['total_general'] = $user_ass['total_internet'] = 0;
            
                // Strip HTML from results where they exist
                foreach($user_ass as $key => $user) {
                    
                    foreach ($user['results'] as $type => $value) {
                        if (in_array($type, $valid_types)) {
                            if (strstr($value, '<br />')) {
                                $wp = explode('<br />', $value);
                                $user_ass[$key]['results'][$type] = $wp[0];
                            }
                        }
                    }
                
                }

                $result->Close();
                return $user_ass;
                
            }
        
        }
        
    }
    

    public function getIctTotals(array $users) {

            // Word Processing
            $totals['word_processing']['total_below_entry_3'] = $totals['word_processing']['total_entry_3'] = $totals['word_processing']['total_below_level_1'] = $totals['word_processing']['total_level_1'] = $totals['word_processing']['total_level_2'] = 0;
            // Spreadsheets
            $totals['spreadsheets']['total_below_entry_3'] = $totals['spreadsheets']['total_entry_3'] = $totals['spreadsheets']['total_below_level_1'] = $totals['spreadsheets']['total_level_1'] = $totals['spreadsheets']['total_level_2'] = 0;
            // Databases
            $totals['databases']['total_below_entry_3'] = $totals['databases']['total_entry_3'] = $totals['databases']['total_below_level_1'] = $totals['databases']['total_level_1'] = $totals['databases']['total_level_2'] = 0;
            // Desktop Publishing
            $totals['desktop_publishing']['total_below_entry_3'] = $totals['desktop_publishing']['total_entry_3'] = $totals['desktop_publishing']['total_below_level_1'] = $totals['desktop_publishing']['total_level_1'] = $totals['desktop_publishing']['total_level_2'] = 0;
            // Presentation
            $totals['presentation']['total_below_entry_3'] = $totals['presentation']['total_entry_3'] = $totals['presentation']['total_below_level_1'] = $totals['presentation']['total_level_1'] = $totals['presentation']['total_level_2'] = 0;
            // Email
            $totals['email']['total_below_entry_3'] = $totals['email']['total_entry_3'] = $totals['email']['total_below_level_1'] = $totals['email']['total_level_1'] = $totals['email']['total_level_2'] = 0;
            // General
            $totals['general']['total_below_entry_3'] = $totals['general']['total_entry_3'] = $totals['general']['total_below_level_1'] = $totals['general']['total_level_1'] = $totals['general']['total_level_2'] = 0;
            // Internet
            $totals['internet']['total_below_entry_3'] = $totals['internet']['total_entry_3'] = $totals['internet']['total_below_level_1'] = $totals['internet']['total_level_1'] = $totals['internet']['total_level_2'] = 0;
            
            foreach ($users as $user) {
                
                $username = $user['user_name'];
                
                // Yet another loop to update counts
                foreach ($users[$username]['results'] as $key => $result) {
                    
                    switch ($result) {
                        case 'Below Entry 3':
                            $totals[$key]['total_below_entry_3']++;
                            break;
                        case 'Entry 3':
                            $totals[$key]['total_entry_3']++;
                            break;
                        case 'Below Level 1':
                            $totals[$key]['total_below_level_1']++;
                            break;
                        case 'Level 1':
                            $totals[$key]['total_level_1']++;
                            break;
                        case 'Level 2':
                            $totals[$key]['total_level_2']++;
                    }
                }
                
            }

            // Now we've got totals count: create HTML of totals
            $html = '<h3>Totals</h3><div id="ict_totals"><table id="ict_container"><tr class="header">';
            foreach ($totals as $key => $total) {
                switch ($key) {
                    case 'word_processing':
                    $html .= '<td><strong>Word Processing</strong><br /><table class="bksb_results centered_tds"><tr><td>Below Entry 3: </td><td>' . $totals[$key]['total_below_entry_3'] .'</td></tr><tr><td>Entry 3:</td><td>' . $totals[$key]['total_entry_3'] .'</td></tr><tr><td>Below Level 1:</td><td> '.$totals[$key]['total_below_level_1'].'</td></tr><tr><td>Level 1:</td><td> '.$totals[$key]['total_level_1'].'</td></tr><tr><td>Level 2:</td><td> '.$totals[$key]['total_level_2'].'</td></tr></table>';
                    break;
                    
                    case 'spreadsheets':
                    $html .= '<td><strong>Spreadsheets</strong><br /><table class="bksb_results centred_tds"><tr><td>Below Entry 3: </td><td>' . $totals[$key]['total_below_entry_3'] .'</td></tr><tr><td>Entry 3:</td><td>' . $totals[$key]['total_entry_3'] .'</td></tr><tr><td>Below Level 1:</td><td> '.$totals[$key]['total_below_level_1'].'</td></tr><tr><td>Level 1:</td><td> '.$totals[$key]['total_level_1'].'</td></tr><tr><td>Level 2:</td><td> '.$totals[$key]['total_level_2'].'</td></tr></table>';
                    break;
                    
                    case 'databases':
                    $html .= '<td><strong>Databases</strong><br /><table class="bksb_results centred_tds"><tr><td>Below Entry 3: </td><td>' . $totals[$key]['total_below_entry_3'] .'</td></tr><tr><td>Entry 3:</td><td>' . $totals[$key]['total_entry_3'] .'</td></tr><tr><td>Below Level 1:</td><td> '.$totals[$key]['total_below_level_1'].'</td></tr><tr><td>Level 1:</td><td> '.$totals[$key]['total_level_1'].'</td></tr><tr><td>Level 2:</td><td> '.$totals[$key]['total_level_2'].'</td></tr></table>';	
                    break;
                    
                    case 'desktop_publishing':
                    $html .= '<td><strong>Desktop Publishing</strong><br /><table class="bksb_results centred_tds"><tr><td>Below Entry 3: </td><td>' . $totals[$key]['total_below_entry_3'] .'</td></tr><tr><td>Entry 3:</td><td>' . $totals[$key]['total_entry_3'] .'</td></tr><tr><td>Below Level 1:</td><td> '.$totals[$key]['total_below_level_1'].'</td></tr><tr><td>Level 1:</td><td> '.$totals[$key]['total_level_1'].'</td></tr><tr><td>Level 2:</td><td> '.$totals[$key]['total_level_2'].'</td></tr></table>';	
                    break;
                    
                    case 'presentation':
                    $html .= '<td><strong>Presentation</strong><br /><table class="bksb_results centred_tds"><tr><td>Below Entry 3: </td><td>' . $totals[$key]['total_below_entry_3'] .'</td></tr><tr><td>Entry 3:</td><td>' . $totals[$key]['total_entry_3'] .'</td></tr><tr><td>Below Level 1:</td><td> '.$totals[$key]['total_below_level_1'].'</td></tr><tr><td>Level 1:</td><td> '.$totals[$key]['total_level_1'].'</td></tr><tr><td>Level 2:</td><td> '.$totals[$key]['total_level_2'].'</td></tr></table>';							
                    break;
                    
                    case 'email':
                    $html .= '<td><strong>Email</strong><br /><table class="bksb_results centred_tds"><tr><td>Below Entry 3: </td><td>' . $totals[$key]['total_below_entry_3'] .'</td></tr><tr><td>Entry 3:</td><td>' . $totals[$key]['total_entry_3'] .'</td></tr><tr><td>Below Level 1:</td><td> '.$totals[$key]['total_below_level_1'].'</td></tr><tr><td>Level 1:</td><td> '.$totals[$key]['total_level_1'].'</td></tr><tr><td>Level 2:</td><td> '.$totals[$key]['total_level_2'].'</td></tr></table>';
                    break;
                    
                    case 'general':
                    $html .= '<td><strong>General</strong><br /><table class="bksb_results centred_tds"><tr><td>Below Entry 3: </td><td>' . $totals[$key]['total_below_entry_3'] .'</td></tr><tr><td>Entry 3:</td><td>' . $totals[$key]['total_entry_3'] .'</td></tr><tr><td>Below Level 1:</td><td> '.$totals[$key]['total_below_level_1'].'</td></tr><tr><td>Level 1:</td><td> '.$totals[$key]['total_level_1'].'</td></tr><tr><td>Level 2:</td><td> '.$totals[$key]['total_level_2'].'</td></tr></table>';	
                    break;
                    
                    case 'internet':
                    $html .= '<td><strong>Internet</strong><br /><table class="bksb_results centred_tds"><tr><td>Below Entry 3: </td><td>' . $totals[$key]['total_below_entry_3'] .'</td></tr><tr><td>Entry 3:</td><td>' . $totals[$key]['total_entry_3'] .'</td></tr><tr><td>Below Level 1:</td><td> '.$totals[$key]['total_below_level_1'].'</td></tr><tr><td>Level 1:</td><td> '.$totals[$key]['total_level_1'].'</td></tr><tr><td>Level 2:</td><td> '.$totals[$key]['total_level_2'].'</td></tr></table>';
                    
                }
            }
            $html .= '</tr></table></div>';
            
            return $html;
            
    }
    
    public function removeDuplicateUsers($notify = FALSE, $usernames=array()) {
        
        // nkowald - 2011-08-22 - If usernames given, skip straight to that
        $duplicate_users = array();
        
        if (count($usernames) == 0) {
            // Find duplicate users
            $query1 = "SELECT userName, COUNT(userName) AS occurrences FROM bksb_Users GROUP BY userName HAVING (COUNT(userName) > 1)";
            $duplicate_users = array();
            
            if ($result = $this->connection->execute($query1)) {
                $this->num_queries++;
                while (!$result->EOF) {
                    $duplicate_users[] = array(
                        'username' => $result->fields['userName']->value, 
                        'no_duplicates' => $result->fields['occurrences']->value
                    );
                    $result->MoveNext();
                }
                $result->Close();
            }
        } else {
            foreach ($usernames as $u_name) {
                $duplicate_users[] = array('username' => $u_name);
            }
        }
        
        if (count($duplicate_users) > 0) {
        
            $no_dupes_deleted = 0;
            foreach ($duplicate_users as $user) {

                // Check for valid user records existing with this valid $new_username username - we don't want to create duplicate user records
                $query2 = sprintf("SELECT user_id, userName, FirstName, LastName FROM dbo.bksb_Users WHERE userName = '%s' ORDER BY user_id DESC", $user['username']);
            
                if ($result = $this->connection->execute($query2)) {
                    $this->num_queries++;
                    $dupe_users = array();
                    while (!$result->EOF) {
                        $dupe_users[] = array(
                            'user_id' => $result->fields['user_id']->value, 
                            'userName' => $result->fields['userName']->value, 
                            'firstname' => $result->fields['firstname'], 
                            'lastname' => $result->fields['lastname']
                        );
                        $result->MoveNext();
                    }
                    $result->Close();
                }

                $delete_ids = array();
                $i = 0;
                foreach ($dupe_users as $duser) {
                    // Put every duplicate value AFTER the first occurrence in an array to delete
                    if ($i > 0){
                        $delete_ids[] = $duser['user_id'];
                    }
                    $i++;
                }
                if (count($delete_ids) > 0) {
                    // Remove duplicates
                    
                    foreach ($delete_ids as $id) {
                        $query = sprintf("DELETE FROM dbo.bksb_Users WHERE user_id = '%d'", $id);
                        if ($result = $this->connection->execute($query)) {
                            $this->num_queries++;
                            $no_dupes_deleted++;
                        }
                    }
                }
                
            }

            // Display how many duplicate users were removed
            if ($notify === TRUE) {
                $user_txt = ($no_dupes_deleted > 1) ? 'users' : 'user';
                echo "Removed $no_dupes_deleted duplicate $user_txt" . PHP_EOL;
            }
            
        }
        
    }
    
    
    public function updateBksbData($old_username='', $new_username='', $firstname='', $lastname='') {
        if ($old_username != '' && $new_username != '' && $firstname != '' && $lastname != '') {
        
            // Find which bksb tables contain this username and need to be updated
            $user_exists = $this->checkMatchingUsername($old_username);
            $updated = TRUE;
                
            // bksb_Users
            if ($user_exists[0] === TRUE) {			
                $query = sprintf("UPDATE dbo.bksb_Users SET userName = '%s', FirstName = '%s', LastName = '%s' WHERE (userName = '%s')", $new_username, $firstname, $lastname, $old_username);
                if (!$result = $this->connection->execute($query)) {
                    $this->errors[] = "Query failed: $query";
                    $updated = FALSE;
                }
                $this->num_queries++;
            }
            // bksb_GroupMembership
            if ($user_exists[1] === TRUE) {
                $gm_query = sprintf("UPDATE dbo.bksb_GroupMembership SET UserName = '%s' WHERE UserName = '%s'", $new_username, $old_username);
                if (!$gm_result = $this->connection->execute($gm_query)) {
                    $this->errors[] = "Query failed: $gm_query";
                    $updated = FALSE;
                }
                $this->num_queries++;
            }
            // bksb_IAResults
            if ($user_exists[2] === TRUE) {
                $ia_query = sprintf("UPDATE dbo.bksb_IAResults SET UserName = '%s' WHERE UserName = '%s'", $new_username, $old_username );
                if (!$ia_result = $this->connection->execute($ia_query)) {
                    $this->errors[] = "Query failed: $ia_query";
                    $updated = FALSE;
                }
                $this->num_queries++;
            }
            // bksb_ICTIAResults
            if ($user_exists[3] === TRUE) {
                $ictia_query = sprintf("UPDATE dbo.bksb_ICTIAResults SET UserName = '%s' WHERE UserName = '%s'", $new_username, $old_username);
                if (!$ictia_result = $this->connection->execute($ictia_query)) {
                    $this->errors[] = "Query failed: $ictia_query";
                    $updated = FALSE;
                }
                $this->num_queries++;
            }
            // bksb_Sessions
            if ($user_exists[4] === TRUE) {
                $sess_query = sprintf("UPDATE dbo.bksb_Sessions SET userName = '%s' WHERE userName = '%s'", $new_username, $old_username);
                if (!$sess_result = $this->connection->execute($sess_query)) {
                    $this->errors[] = "Query failed: $sess_query";
                    $updated = FALSE;
                }
                $this->num_queries++;
            }
            
            // Remove any duplicate users
            // nkowald - 2011-08-22 - put new username into an array for the remove dupes functions
            $duplicate_users[] = $new_username;
            $this->removeDuplicateUsers(FALSE, $duplicate_users);
            
            // Finished updating
            if ($updated === TRUE) {
                return true;
            } else {
                return false;
            }
            
        } else {
            return false;
        }
    }
    
    public function findBksbUserName($idnumber='', $forename='', $surname='', $dob='', $postcode='') {

        $username = '';
        $forename = str_replace("'", "''", $forename);
        $surname = str_replace("'", "''", $surname);
        $postcode = str_replace(' ', '', $postcode);

        // Try to match on idnumber first
        $query = sprintf("SELECT userName FROM dbo.bksb_Users WHERE userName = '%s'", $idnumber);	
        if ($result = $this->connection->execute($query)) {
            $this->num_queries++;
            while (!$result->EOF) {
                $username = $result->fields['userName']->value;
                $result->MoveNext();
            }
            if ($username != '') {
                $result->Close();
                return $username;
            }
        }
        $query = sprintf("SELECT userName FROM dbo.bksb_Users WHERE FirstName = '%s' AND LastName = '%s' ORDER BY user_id DESC", $forename, $surname);	
        if ($result = $this->connection->execute($query)) {
            $this->num_queries++;
            $usernames = array();
            while (!$result->EOF) {
                $usernames[] = $result->fields['userName']->value;
                $result->MoveNext();
            }
            if (count($usernames) > 0) {
                $result->Close();
                return $usernames;
            }
        }
        // change format of dob
        $query = sprintf("SELECT userName FROM dbo.bksb_Users WHERE (REPLACE(PostcodeA, ' ', '') = '%s') AND (CONVERT(VARCHAR(10), DOB, 103) = '%s')", $postcode, $dob);
        if ($result = $this->connection->execute($query)) {
            $this->num_queries++;
            while (!$result->EOF) {
                $username = $result->fields['userName']->value;
                $result->MoveNext();
            }
            if ($username != '') {
                $result->Close();
                return $username;
            }
        }
        // If we get here, we haven't found a match so return false
        return false;
    }

    // This will keep ONLY distinct paramaters
    public function getDistinctParams() {
        $param = '';	
        $params = array();
        foreach ($_GET as $key => $value) {
            if ($key == 'page' || $value == '') continue;
            $params[$key] = $value;
        }
        if (count($params) == 0) return $param;

        $c = 0;
        foreach ($params as $key => $value) {
            // Only use a question mark for the first get param
            $param .= ($c == 0) ? "?" : "&";
            $param .= "$key=$value";
            $c++;
        }
        return $param;
    }

    public function filterStudentsByName(Array $students) {
        $first_initial = (isset($_GET['tifirst']) && $_GET['tifirst'] != '' && ctype_alpha($_GET['tifirst'])) ? $_GET['tifirst'] : '';
        $last_initial = (isset($_GET['tilast']) && $_GET['tilast'] != '' && ctype_alpha($_GET['tilast'])) ? $_GET['tilast'] : '';
        // No name filters set: return untouched
        if ($first_initial == '' && $last_initial == '') return $students;

        $filtered = array();
        foreach ($students as $student) {
            if ($first_initial != '' && strtolower($student->firstname[0]) != strtolower($first_initial)) {
                continue;
            }
            if ($last_initial != '' && strtolower($student->lastname[0]) != strtolower($last_initial)) {
                continue; 
            }
            $filtered[] = $student;
        }
        return $filtered;
    }

    public function getDiagnosticIdsForStudents(Array $students) {
        $student_idnumbers = array();
        foreach ($students as $student) {
            $student_idnumbers[] = "'".$student->idnumber."'";
        }
        $csv_student_idnumbers = implode(',', $student_idnumbers);
        $query = sprintf("SELECT DISTINCT bs.assessment_id AS id, bs.userName, ba.[assessment name] as name FROM bksb_Sessions AS bs LEFT OUTER JOIN bksb_Assessments AS ba ON bs.assessment_id = ba.ass_ref WHERE (bs.userName IN (%s)) AND (ba.[assessment group] = 1) ORDER BY bs.userName", $csv_student_idnumbers);
        if ($result = $this->connection->execute($query)) {
            $this->num_queries++;
            $diag_ids = array();
            $search = array('English', 'Mathematics', ' Diagnostic');
            $replace = array('Literacy', 'Numeracy', '');
            while (!$result->EOF) {
                $diag_ids[] = array(
                    $result->fields['userName']->value,
                    str_replace($search, $replace, $result->fields['name']->value),
                    $result->fields['id']->value
                );
                $result->MoveNext();
            }
            $result->Close();
            return $diag_ids;
        }
    }
    public function filterStudentsByDiagAss(Array $students, Array $diag_ids, $ass_no) {
        /*
        $ass_type = $this->getAssTypeFromNo($ass_no);
        $filtered = array();
        foreach ($students as $student) {
            foreach ($diag_ids as $diag) {
                if ($diag[1] == $ass_type && $diag[0] == $student->idnumber) {
                    $filtered[] = $student;
                    break;
                }
            }
        }
        return $filtered;
        */
        return $students;
    }

    public function getStudentsForCourse($course_id) {
        global $DB, $CFG;

        $context = get_context_instance(CONTEXT_COURSE, $course_id);
        $query = sprintf("SELECT u.id, u.firstname, u.lastname, u.idnumber FROM ".$CFG->prefix."user AS u, ".$CFG->prefix."role_assignments AS a WHERE contextid=%d AND roleid=%d AND a.userid=u.id AND u.idnumber != ''", 
            $context->id, 
            5
        );

        $students = $DB->get_records_sql($query); 
        $this->num_queries++;

        return $students;
    }
    public function isUserStudentOnThisCourse($user_id='', $course_id='') {
        if ($user_id == '' || $course_id == '') return false;
        $students = $this->getStudentsForCourse($course_id);
        foreach ($students as $student) {
            if ($student->id == $user_id) return true;
        }
        return false;
    }

    public function filterStudentsByPage(Array $students, $offset, $perpage) {
        $students = array_slice($students,$offset,$perpage);
        /*
        $valid_keys[] = $offset;
        while (count($valid_keys) < $perpage) {
            $valid_keys[] = $offset + 1;
        }
        $c = 0;
        foreach ($students as $key => $student) {
            if (!in_array($c, $valid_keys)) {
                unset($students[$key]);
            }
            $c++;
        }
        */
        // check for name filters
        $students = $this->filterStudentsByName($students);
        return $students;
    }

    /*
    // Idiot me only updated usernames in one table, instead of 'all instances' that use the username: FIX!
    public function restoreUsernames() {
    
        $query = "SELECT user_id, userName FROM dbo.bksb_Users ORDER BY user_id";
        if ($result = $this->old_connection->execute($query)) {
            $old_usernames = array();
            while (!$result->EOF) {
                $user_id = $result->fields['user_id']->value;
                $username = $result->fields['userName']->value;
                $old_usernames[$user_id] = $username;
                $result->MoveNext();
            }
        }
        
        // show array
        foreach ($old_usernames as $key => $value) {
            // Query will update record $key with username of $value
            $update_query = "UPDATE dbo.bksb_Users SET userName = '$value' WHERE user_id = $key";
            if ($result_new = $this->connection->execute($update_query)) {
                // worked!
            } else {
                $this->errors[] = "Update failed for user $key";
            }
        }
    }
    */

    public function syncUserDobAndPostcode() {

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

        $users_updated = 0;
        foreach ($ebs_users as $user) {
            // Check if user exists in Moodle users table
            if ($exists = $DB->get_record('user', array('idnumber' => $user['idnumber']))) {
                // Update user record with postcode and dob
                $exists->dob = $user['dob'];
                $exists->postcode = $user['postcode'];
                if ($DB->update_record('user', $exists)) {
                    $users_updated++;
                }
            }
        }
        //echo 'Number of users updated: ' . $users_updated;
    }

    public function __destruct() {

        try {
            $this->connection->Close();
        } catch (Exception $e) {
            //$this->errors[] = $e->getMessage();
        }

        if ($this->debug === true) {
            echo '<div class="debugging">';
            echo '<h2>Debugging</h2>';
            echo '<p><strong>Number of SQL queries:</strong> ' . $this->num_queries . '</p>';
            if (function_exists('xdebug_is_enabled') && xdebug_is_enabled()) { 
                echo '<p><strong>Time taken:</strong> ' . xdebug_time_index() . '</p>';
            }
            echo '</div>';

            if (count($this->errors) > 0) {
                echo '<div style="color:red;">';
                echo "<h2>Errors</h2>";
                echo '<ul>';
                foreach($this->errors as $error) {
                    echo "<li>$error</li>";
                }
                echo '</ul>';
                echo '</div>';
            }
        }

        $this->errors[] = array();
    }

}


function ms_escape_string($data) {
	if ( !isset($data) or empty($data) ) return '';
	if ( is_numeric($data) ) return $data;

	$non_displayables = array(
		'/%0[0-8bcef]/',            // url encoded 00-08, 11, 12, 14, 15
		'/%1[0-9a-f]/',             // url encoded 16-31
		'/[\x00-\x08]/',            // 00-08
		'/\x0b/',                   // 11
		'/\x0c/',                   // 12
		'/[\x0e-\x1f]/'             // 14-31
	);
	foreach ( $non_displayables as $regex )
		$data = preg_replace( $regex, '', $data );
	$data = str_replace("'", "''", $data );
	return $data;
}
