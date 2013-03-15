<?php
    class block_bksb extends block_base {

        function init() {
            $this->title = get_string('bksb', 'block_bksb');
            //$this->cron = 43200; // run the cron at minimum once every 12 hour
        }

        /**
        * Allow the user to set sitewide configuration options for the block.
        *
        * @return bool true
        */
        function has_config() {
            return true;
        }

        /**
        * Allow the user to set specific configuration options for the instance of
        * the block attached to a course.
        *
        * @return bool true
        */
        function instance_allow_config() {
            return false;
        }

        /**
        * Prevent the user from having more than one instance of the block on each
        * course.
        *
        * @return bool false
        */
        function instance_allow_multiple() {
            return false;
        }

        function get_content() {

            if ($this->content !== NULL) {
                return $this->content;
            }

            global $USER, $COURSE, $CFG;
            $this->content = new stdClass;

            $user_id = $USER->id;
            $course_id = $COURSE->id;

            $url_ia = $CFG->wwwroot . '/blocks/bksb/initial_assessment.php';
            $url_da = $CFG->wwwroot . '/blocks/bksb/diagnostic_assessment.php';

            // Is the logged in user a student?
            $user_is_student = (strpos($USER->email, '@student.conel.ac.uk') === false) ? false : true;

            $block_html = '<ul id="bksb_block_ul">';

            /* Student Results */
            if ($user_is_student === true) {
                $get_params = sprintf('?id=%d', $user_id);
                if ($course_id != '') {
                    $get_params .= sprintf('&amp;course_id=%d', $course_id);
                }
                $block_html .= '<li class="ia_icon"><a href="'.$url_ia . $get_params.'">'.get_string('my_initial_assessments', 'block_bksb').'</a></li>
                    <li class="da_icon"><a href="'.$url_da . $get_params.'">'.get_string('my_diagnostic_assessments', 'block_bksb').'</a></li>';
            }  
            
            /* Course Results */
            if ($user_is_student === false) {
                if ($course_id == '') {
                    $block_html .= '<li>Course ID required</li>';
                } else {
                    $block_html .= '<li class="ia_icon"><a href="'.$url_ia.'?course_id='.$course_id.'">'.get_string('initial_assessments', 'block_bksb').'</a></li>
                    <li class="da_icon"><a href="'.$url_da.'?course_id='.$course_id.'&amp;assessment=1">'.get_string('diagnostic_assessments', 'block_bksb').'</a></li>';
                }
            }
            $block_html .= '</ul>';

            $this->content->text = $block_html;
            $this->content->footer = '';

            return $this->content;
        }

    }
?>
