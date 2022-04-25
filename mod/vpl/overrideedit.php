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
 * This page handles editing and creation of vpl overrides
 *
 * @package   mod_vpl
 * @copyright 2022 Neeraj Patil
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/mod/vpl/lib.php');
require_once($CFG->dirroot.'/mod/vpl/locallib.php');
require_once($CFG->dirroot.'/mod/vpl/override_form.php');


$cmid = optional_param('cmid', 0, PARAM_INT);
$overrideid = optional_param('id', 0, PARAM_INT);
$action = optional_param('action', null, PARAM_ALPHA);
$reset = optional_param('reset', false, PARAM_BOOL);
$uid = optional_param('uid', null, PARAM_INT);
$userchange = optional_param('userchange', false, PARAM_BOOL);
$users = optional_param('selecteduser',null, PARAM_SEQUENCE);
$pagetitle = get_string('editoverride', 'vpl');


$override = null;
if ($overrideid) {

    if (! $override = $DB->get_record('vpl_overrides', array('id' => $overrideid))) {
        print_error('invalidoverrideid', 'vpl');
    }

    list($course, $cm) = get_course_and_cm_from_instance($override->vplid, 'vpl');

} else if ($cmid) {
    list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'vpl');

} else {
    print_error('invalidcoursemodule');
}

$url = new moodle_url('/mod/vpl/overrideedit.php');
if ($action) {
    $url->param('action', $action);
}
if ($overrideid) {
    $url->param('id', $overrideid);
} else {
    $url->param('cmid', $cmid);
}

$PAGE->set_url($url);

require_login($course, false, $cm);

$context = context_module::instance($cm->id);
$vpl = new mod_vpl( $cm->id );
$vplinstance=$vpl->get_instance();

$shouldadduserid = $uid && !empty($course->relativedatesmode);
$shouldresetform = optional_param('resetbutton', 0, PARAM_ALPHA) || ($userchange && $action !== 'duplicate');

// Add or edit an override.
require_capability('mod/vpl:manageoverrides', $context);

if ($overrideid) {
    // Editing an override.
    $data = clone $override;

    if ($override->groupid) {
        if (!groups_group_visible($override->groupid, $course, $cm)) {
            print_error('invalidoverrideid', 'vpl');
        }
    } else {
        if (!groups_user_groups_visible($course, $override->userid, $cm)) {
            print_error('invalidoverrideid', 'vpl');
        }
    }
} else {
    // Creating a new override.
    $data = new stdClass();
}

// Merge vpl defaults with data.
$keys = array('duedate', 'startdate');
foreach ($keys as $key) {
    if (!isset($data->{$key}) || $reset) {
        $data->{$key} = $vplinstance->{$key};
    }
}

// True if group-based override.
$groupmode = !empty($data->groupid) || ($action === 'addgroup' && empty($overrideid));

// If we are duplicating an override, then clear the user/group and override id
// since they will change.
if ($action === 'duplicate') {
    $override->id = $data->id = null;
    $override->userid = $data->userid = null;
    $override->groupid = $data->groupid = null;
    $pagetitle = get_string('duplicateoverride', 'vpl');
}

if ($shouldadduserid) {
    $data->userid = $uid;
}

$overridelisturl = new moodle_url('/mod/vpl/overrides.php', array('cmid' => $cm->id));
if (!$groupmode) {
    $overridelisturl->param('mode', 'user');
}

if($action == 'grantextension')
{
    $userlist = explode(',', $users);
    $data->userid = $userlist;
}
// Setup the form.
$mform = new vpl_override_form($url, $cm, $vpl, $context, $groupmode, $override);
$mform->set_data($data);

if ($mform->is_cancelled()) {
    redirect($overridelisturl);

} else if ($shouldresetform) {
    $url->param('reset', true);
    if ($shouldadduserid) {
        $url->param('uid', $uid);
    }
    redirect($url);

} else if (!$userchange && $fromform = $mform->get_data()) {

    foreach ($fromform->userid as $userid) {
    $fromform->vplid = $vplinstance->id;

    // Replace unchanged values with null.
    foreach ($keys as $key) {
        if (($fromform->{$key} == $vplinstance->{$key})) {
            $fromform->{$key} = null;
        }
    }

    // See if we are replacing an existing override.
    $userorgroupchanged = false;
    if (empty($override->id)) {
        $userorgroupchanged = true;
    } else if (!empty($userid)) {
        $userorgroupchanged = $userid !== $override->userid;
    } else {
        $userorgroupchanged = $fromform->groupid !== $override->groupid;
    }

    if ($userorgroupchanged) {
        $conditions = array(
                'vplid' => $vplinstance->id,
                'userid' => empty($userid) ? null : $userid,
                'groupid' => empty($fromform->groupid) ? null : $fromform->groupid);
        if ($oldoverride = $DB->get_record('vpl_overrides', $conditions)) {
            // There is an old override, so we merge any new settings on top of
            // the older override.
            foreach ($keys as $key) {
                if (is_null($fromform->{$key})) {
                    $fromform->{$key} = $oldoverride->{$key};
                }
            }

            $vpl->delete_override($oldoverride->id);
        }
    }

    // Set the common parameters for one of the events we may be triggering.
    $params = array(
        'context' => $context,
        'other' => array(
            'vplid' => $vplinstance->id
        )
    );
    if (!empty($override->id)) {
        
        $fromform->id = $override->id;
        $dataobj = $fromform;
        $dataobj->userid=$userid;
        $DB->update_record('vpl_overrides', $dataobj);
        // $cachekey = $groupmode ? "{$fromform->vplid}_g_{$fromform->groupid}" : "{$fromform->vplid}_u_{$fromform->userid}";
        // cache::make('mod_vpl', 'overrides')->delete($cachekey);

        // // Determine which override updated event to fire.
        // $params['objectid'] = $override->id;
        // if (!$groupmode) {
        //     $params['relateduserid'] = $fromform->userid;
        //     $event = \mod_assign\event\user_override_updated::create($params);
        // } else {
        //     $params['other']['groupid'] = $fromform->groupid;
        //     $event = \mod_assign\event\group_override_updated::create($params);
        // }

        // Trigger the override updated event.
    //    $event- >trigger();
    } else {
        unset($fromform->id);
        var_dump($fromform);
        $dataobj = $fromform;
        $dataobj->userid=$userid;
        $fromform->id = $DB->insert_record('vpl_overrides', $dataobj);
        if ($groupmode) {
            $fromform->sortorder = 1;

            $overridecountgroup = $DB->count_records('vpl_overrides',
                array('userid' => null, 'vplid' => $vplinstance->id));
            $overridecountall = $DB->count_records('vpl_overrides', array('vplid' => $vplinstance->id));
            if ((!$overridecountgroup) && ($overridecountall)) { // No group overrides and there are user overrides.
                $fromform->sortorder = 1;
            } else {
                $fromform->sortorder = $overridecountgroup;

            }

            $DB->update_record('vpl_overrides', $fromform);
            vpl_reorder_group_overrides($vplinstance->id);
        }
        // $cachekey = $groupmode ? "{$fromform->assignid}_g_{$fromform->groupid}" : "{$fromform->assignid}_u_{$fromform->userid}";
        // cache::make('mod_vpl', 'overrides')->delete($cachekey);

        // // Determine which override created event to fire.
        // $params['objectid'] = $fromform->id;
        // if (!$groupmode) {
        //     $params['relateduserid'] = $fromform->userid;
        //     $event = \mod_assign\event\user_override_created::create($params);
        // } else {
        //     $params['other']['groupid'] = $fromform->groupid;
        //     $event = \mod_assign\event\group_override_created::create($params);
        // }

        // // Trigger the override created event.
        // $event->trigger();
    }

    // assign_update_events($assign, $fromform);
    }
    vpl_prepare_update_events($vpl);
    if (!empty($fromform->submitbutton)) {
        redirect($overridelisturl);
    }

    // The user pressed the 'again' button, so redirect back to this page.
    $url->remove_params('cmid');
    $url->param('action', 'duplicate');
    $url->param('id', $fromform->id);
    redirect($url);
    
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
?>