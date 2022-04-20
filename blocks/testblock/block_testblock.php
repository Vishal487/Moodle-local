<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Form for editing HTML block instances.
 *
 * @package   block_testblock
 * @copyright 1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_testblock extends block_base {

    function init() {
        $this->title = get_string('pluginname', 'block_testblock');
    }

    function get_content() {
        global $DB;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $sessions = $DB->get_records('sessions');
        $curr_session = end($sessions);   // last element corresponds to current user.
        $curr_userid = $curr_session->userid;

        $assignment_ids = array();
        $assignment_submissions = $DB->get_records('assign_submission');
        foreach ($assignment_submissions as $submission){
            if ($submission->userid == $curr_userid){
                if (strcmp($submission->status, "new") == 0){
                    array_push($assignment_ids, $submission->assignment);
                }
            }
        }
        $assignment_ids = array_unique($assignment_ids);

        $temp_string = '';
        foreach ($assignment_ids as $assid){
            $temp_string .= 'Assignment ' . $assid . "<br>";
        }

        $this->content = new stdClass;
        $this->content->text = $temp_string;
        $this->content->footer = "<i> this is footer </i>";

        return $this->content;
    }

}
