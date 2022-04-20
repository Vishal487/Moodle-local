<?php 
//...

/**
 * @package     local_helloworld
 * @author      Vishal Rao
 */

require(__DIR__ . '/../../config.php');

$PAGE->set_url(new moodle_url('/local/helloworld/index.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title('Hello world');

echo $OUTPUT->header();

class simplehtml_form extends moodleform {
    //Add elements to form
    public function definition() {
        global $CFG;
       
        $mform = $this->_form; // Don't forget the underscore! 

        $mform->addElement('html', "<h1>Hello World!</h1>");
        $mform->addElement('html', "<p>What is your name? </p>");

        $mform->addElement('text', 'name', get_string('entername', 'local_helloworld'));  // Add elements to your form
        $mform->setType('name', PARAM_NOTAGS);                   //Set type of element
        $mform->setDefault('name', '');        //Default value

        $this->add_action_buttons($cancel = false, $submitlabel="submit");
    }
    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}

//Instantiate simplehtml_form 
$mform = new simplehtml_form();

// Form processing and displaying is done here
if ($mform->is_cancelled()) {
    //Handle form cancel operation, if cancel button is present on form
} else if ($fromform = $mform->get_data()) {
    //In this case you process validated data. $mform->get_data() returns data posted in form.
    $name = $fromform->name;
    echo "<h1> Hello {$name}! </h1>";

    $hello_world_url = new moodle_url('/local/helloworld/index.php');
    $site_front_page_url = new moodle_url('/');
    echo "<ul> 
            <li> <a href='{$site_front_page_url}'> Go to site front page</a> </li>
            <li> <a href='{$hello_world_url}'> Back to the Hello world main page </a> </li>
        </ul>
    ";
} else {
  // this branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
  // or on the first display of the form.


  //Set default data (if any)
  $mform->set_data($toform);
  //displays the form
  $mform->display(); 
}


echo $OUTPUT->footer();