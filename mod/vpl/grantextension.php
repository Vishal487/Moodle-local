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
 * This page is used to implement the grant extension functionality
 *
 * @package   mod_vpl
 * @copyright 2022 Neeraj Patil
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/mod/vpl/lib.php');
require_once($CFG->dirroot.'/mod/vpl/locallib.php');
require_once($CFG->dirroot . '/mod/vpl/views/extensionform.php');

$cmid = required_param('cmid', PARAM_INT);
$action = optional_param('action', null, PARAM_ALPHA);
$users = required_param('selecteduser', PARAM_SEQUENCE);

$userlist = explode(',', $users);
$url = new moodle_url($CFG->dirroot.'/mod/vpl/grantextension.php');
$PAGE->set_url($url);

$pagetitle = get_string('grantextension','vpl');
list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'vpl');

require_login($course, false, $cm);



$context = context_module::instance($cmid);
$vpl = new mod_vpl( $cmid );
$vplinstance=$vpl->get_instance();

$formparams = array(
    'instance' => $vplinstance,
    'vpl' => $vpl,
    'cm' => $cmid
);
$data = new stdClass();
$data->selecteduser = $users;

$keys = array('duedate', 'startdate');
$formparams['userlist'] = $userlist;

require_capability('mod/vpl:manageoverrides', $context);
$mform = new mod_vpl_extension_form(null, $formparams);
$mform->set_data($data);

if ($mform->is_cancelled()) {
    //Handle form cancel operation, if cancel button is present on form
    redirect(new moodle_url('/mod/vpl/views/submissionslist.php',['id' => $cmid]));
} else if ($fromform = $mform->get_data()) {
    //In this case you process validated data. $mform->get_data() returns data posted in form.
    $selectedusers = $fromform->selecteduser;
    $selecteduserlist = explode(',', $selectedusers);
    foreach ($selecteduserlist as $userid) {
        // See if we are replacing an existing override.
        $userorgroupchanged = false;
        $conditions = array(
            'vplid' => $vplinstance->id,
            'userid' => empty($userid) ? null : $userid);
        if ($oldoverride = $DB->get_record('vpl_overrides', $conditions)) {
        // There is an old override, so we merge any new settings on top of
        // the older override.
            foreach ($keys as $key) {
                if (is_null($fromform->extensionduedate)) {
                    $fromform->extensionduedate = $oldoverride->duedate;
                }
            }
            $vpl->delete_override($oldoverride->id);
        }
        
    
        // Set the common parameters for one of the events we may be triggering.
        $params = array(
            'context' => $context,
            'other' => array(
                'vplid' => $vplinstance->id
            )
        );
      
        unset($fromform->id);
        $dataobj = new stdClass();
        $dataobj->vplid = $vplinstance->id;
        $dataobj->userid=$userid;
        $dataobj->duedate=$fromform->extensionduedate;
        var_dump($dataobj);
        $fromform->id = $DB->insert_record('vpl_overrides', $dataobj);
    }
    vpl_prepare_update_events($vpl);
    redirect(new moodle_url('/mod/vpl/views/submissionslist.php',['id' => $cmid]));
} 

// Print the form.
$PAGE->navbar->add($pagetitle);
$PAGE->set_pagelayout('admin');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($vplinstance->name, true, array('context' => $context)));

$mform->display();

echo $OUTPUT->footer();