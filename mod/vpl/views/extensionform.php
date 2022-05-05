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
 * Vpl extension dates form
 *
 * @package   mod_vpl
 * @copyright 2022 Neeraj Patil
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');


require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot . '/mod/vpl/locallib.php');

class mod_vpl_extension_form extends moodleform {
    /** @var array $instance - The data passed to this form */
    private $instance;

    /**
     * Define the form - called by parent constructor
     */
    public function definition() {
        global $DB;

        $mform = $this->_form;
        $params = $this->_customdata;

        // Instance variable is used by the form validation function.
        $instance = $params['instance'];
        $this->instance = $instance;

        // Get the vpl class.
        $vpl = $params['vpl'];
        $userlist = $params['userlist'];
        $usercount = 0;
        $usershtml = '';

        foreach ($userlist as $userid) {
            if ($usercount >= 5) {
                $usershtml .= get_string('moreusers', 'vpl', count($userlist) - 5);
                break;
            }
            $user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);

            $usershtml .= html_writer::div($vpl->user_picture( $user) . ' '.$vpl->fullname($user,true));
            $usercount += 1;
        }

        $finalusershtml = html_writer::div($usershtml,'users');

        $userscount = count($userlist);

        $listusersmessage = get_string('grantextensionforusers', 'vpl', $userscount);
        $mform->addElement('header', 'general', $listusersmessage);
        $mform->addElement('static', 'userslist', get_string('selectedusers', 'vpl'), $finalusershtml);

        if ($instance->startdate) {
            $mform->addElement('static', 'startdate', get_string('startdate', 'vpl'),
                               userdate($instance->startdate));
        }

        $finaldate = 0;
        if ($instance->duedate) {
            $mform->addElement('static', 'duedate', get_string('duedate', 'vpl'), userdate($instance->duedate));
            $finaldate = $instance->duedate;
        }
        
        $mform->addElement('date_time_selector', 'extensionduedate',
                           get_string('extensionduedate', 'vpl'), array('optional'=>true));
        $mform->setDefault('extensionduedate', $finaldate);

        $mform->addElement('hidden', 'cmid',$params['cm']);
        $mform->setType('cmid', PARAM_INT);
        $mform->addElement('hidden', 'selecteduser');
        $mform->setType('selecteduser', PARAM_SEQUENCE);
        // $mform->addElement('hidden', 'action', 'saveextension');
        // $mform->setType('action', PARAM_ALPHA);

        $this->add_action_buttons(true, get_string('savechanges', 'vpl'));
    }

    /**
     * Perform validation on the extension form
     * @param array $data
     * @param array $files
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if ($this->instance->duedate && $data['extensionduedate']) {
            if ($this->instance->duedate > $data['extensionduedate']) {
                $errors['extensionduedate'] = get_string('extensionnotafterduedate', 'vpl');
            }
        }
        if ($this->instance->startdate && $data['extensionduedate']) {
            if ($this->instance->startdate > $data['extensionduedate']) {
                $errors['extensionduedate'] = get_string('extensionnotafterfromdate', 'vpl');
            }
        }

        return $errors;
    }
}
