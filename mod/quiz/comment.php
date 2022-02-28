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
 * This page allows the teacher to enter a manual grade for a particular question.
 * This page is expected to only be used in a popup window.
 *
 * @package   mod_quiz
 * @copyright gustav delius 2006
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('locallib.php');

$attemptid = required_param('attempt', PARAM_INT);
$slot = required_param('slot', PARAM_INT); // The question number in the attempt.
$cmid = optional_param('cmid', null, PARAM_INT);

$PAGE->set_url('/mod/quiz/comment.php', array('attempt' => $attemptid, 'slot' => $slot));

$attemptobj = quiz_create_attempt_handling_errors($attemptid, $cmid);
$attemptobj->preload_all_attempt_step_users();
$student = $DB->get_record('user', array('id' => $attemptobj->get_userid()));

// Can only grade finished attempts.
if (!$attemptobj->is_finished()) {
    print_error('attemptclosed', 'quiz');
}

// Check login and permissions.
require_login($attemptobj->get_course(), false, $attemptobj->get_cm());
$attemptobj->require_capability('mod/quiz:grade');

// Print the page header.
$PAGE->set_pagelayout('popup');
$PAGE->set_title(get_string('manualgradequestion', 'quiz', array(
        'question' => format_string($attemptobj->get_question_name($slot)),
        'quiz' => format_string($attemptobj->get_quiz_name()), 'user' => fullname($student))));
$PAGE->set_heading($attemptobj->get_course()->fullname);
$output = $PAGE->get_renderer('mod_quiz');
echo $output->header();

// Prepare summary information about this question attempt.
$summarydata = array();

// Student name.
$userpicture = new user_picture($student);
$userpicture->courseid = $attemptobj->get_courseid();
$summarydata['user'] = array(
    'title'   => $userpicture,
    'content' => new action_link(new moodle_url('/user/view.php', array(
            'id' => $student->id, 'course' => $attemptobj->get_courseid())),
            fullname($student, true)),
);

// Quiz name.
$summarydata['quizname'] = array(
    'title'   => get_string('modulename', 'quiz'),
    'content' => format_string($attemptobj->get_quiz_name()),
);

// Question name.
$summarydata['questionname'] = array(
    'title'   => get_string('question', 'quiz'),
    'content' => $attemptobj->get_question_name($slot),
);

// Process any data that was submitted.
if (data_submitted() && confirm_sesskey()) {
    if (optional_param('submit', false, PARAM_BOOL) && question_engine::is_manual_grade_in_range($attemptobj->get_uniqueid(), $slot)) {
        $transaction = $DB->start_delegated_transaction();
        $attemptobj->process_submitted_actions(time());
        $transaction->allow_commit();

        // Log this action.
        $params = array(
            'objectid' => $attemptobj->get_question_attempt($slot)->get_question_id(),
            'courseid' => $attemptobj->get_courseid(),
            'context' => context_module::instance($attemptobj->get_cmid()),
            'other' => array(
                'quizid' => $attemptobj->get_quizid(),
                'attemptid' => $attemptobj->get_attemptid(),
                'slot' => $slot
            )
        );
        $event = \mod_quiz\event\question_manually_graded::create($params);
        $event->trigger();

        echo $output->notification(get_string('changessaved'), 'notifysuccess');
        close_window(2, true);
        die;
    }
}

// Print quiz information.
echo $output->review_summary_table($summarydata, 0);

// Print the comment form.
echo '<form method="post" class="mform" id="manualgradingform" action="' .
        $CFG->wwwroot . '/mod/quiz/comment.php">';
echo $attemptobj->render_question_for_commenting($slot);

// var_dump($attemptobj);
$qa = $attemptobj->get_question_attempt($slot);
// var_dump($qa);
$options = $attemptobj->get_display_options(true);
// var_dump($options);
$files = $qa->get_last_qt_files('attachments', $options->context->id);
// var_dump($files);
$fileurl = "";
/**
 * TODO :: if annotated file exists in the DB, 
 * then $fileurl should point to that.
 */

foreach ($files as $file) {
    $out = $qa->get_response_file_url($file);
    $url = (explode("?", $out))[0];
    $fileurl = $url;
    // var_dump($url);
}

$attemptid = $attemptobj->get_attemptid();
$contextid = $options->context->id;
$filename = end(explode("/", $fileurl));
$filename = str_replace('%20', '', $filename);   // replace whitespaces
$filename = str_replace('%28', '(', $filename); // replace (
$filename = str_replace('%29', ')', $filename); // replace )
// var_dump($attemptobj->get_attemptid());
// var_dump($qa->get_usage_id());
$contextID = $options->context->id;
$component = 'question';
$filearea = 'response_attachments';
$filepath = '/';
$itemid = $attemptobj->get_attemptid();

$fs = get_file_storage();
var_dump($contextID);
var_dump($component);
var_dump($filearea);
var_dump($itemid);
var_dump($filepath);
var_dump($filename);
$doesExists = $fs->file_exists($contextID, $component, $filearea, $itemid, $filepath, $filename);
// var_dump($doesExists);
if($doesExists === true)
{
    $file = $fs->get_file($contextID, $component, $filearea, $itemid, $filepath, $filename);
    $url1 = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename(), false);
    // echo $url;
    //http://localhost/moodle/pluginfile.php/53/question/response_attachments/13/1/42/Mess_Timings.pdf
    $file = $fs->get_file($contextID, $component, $filearea, $itemid, $filepath, $filename);
    $temp = file_encode_url(new moodle_url('/pluginfile.php'), '/' . implode('/', array(
        $file->get_contextid(),
        $file->get_component(),
        $file->get_filearea(),
        $qa->get_usage_id(),
        $qa->get_slot(),
        $file->get_itemid())) .
        $file->get_filepath() . $file->get_filename(), true);
    
    var_dump($temp);
    //http://localhost/moodle/pluginfile.php/53/question/response_attachments/13/1/11/Mess_Timings.pdf?forcedownload=1
    //http://localhost/moodle/pluginfile.php/53/question/response_feedback_annotation/13/1/11/Mess_Timings.pdf
    $url1 = (explode("?", $temp))[0];
    $fileurl = $url1;
}
else
{
    var_dump($doesExists);
    echo "File doesn't exist\n";
}

// $filename = "http://localhost/moodle/pluginfile.php/60/question/response_attachments/35/1/139/intro.pdf";
// $fileurl = "http://localhost/moodle/pluginfile.php/60/question/response_feedback_annotation/35/1/25/pannot.pdf";

include "./myindex.html";

?>
<script type="text/javascript">
    var fileurl = "<?= $fileurl ?>"
    var contextID = "<?= $contextid ?>";
    var attemptID = "<?= $attemptid ?>";
    var filename = "<?= $filename ?>"; 
</script>

<script type="text/javascript" src="./myscript.js"></script>

<div>
    <input type="hidden" name="attempt" value="<?php echo $attemptobj->get_attemptid(); ?>" />
    <input type="hidden" name="slot" value="<?php echo $slot; ?>" />
    <input type="hidden" name="slots" value="<?php echo $slot; ?>" />
    <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>" />
</div>
<fieldset class="hidden">
    <div>
        <div class="fitem fitem_actionbuttons fitem_fsubmit">
            <fieldset class="felement fsubmit">
                <input id="id_submitbutton" type="submit" name="submit" class="btn btn-primary" value="<?php
                        print_string('save', 'quiz'); ?>"/>
            </fieldset>
        </div>
    </div>
</fieldset>
<?php
echo '</form>';
$PAGE->requires->js_init_call('M.mod_quiz.init_comment_popup', null, false, quiz_get_js_module());

// End of the page.
echo $output->footer();
