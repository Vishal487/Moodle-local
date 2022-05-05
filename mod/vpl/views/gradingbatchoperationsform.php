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
 * This file contains the forms to create and edit an instance of this module.
 *  VPL grading options form
 * @package    mod_vpl
 * @copyright  2022 Neeraj Patil
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');
require_once("$CFG->libdir/formslib.php");
class mod_vpl_grading_batch_operations_form extends moodleform
{

    public function definition()
    {
        $mform = $this->_form;
        $instance = $this->_customdata;

        // Visible elements.
        $options['grantextension'] = get_string('grantextension', 'vpl');

        $mform->addElement('hidden', 'id', $instance['cm']);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'selecteduser', '', array('class' => 'selecteduser'));
        $mform->setType('selecteduser', PARAM_SEQUENCE);

        $objs = array();
        $objs[] = &$mform->createElement('select', 'action', get_string('chooseoperation', 'vpl'), $options);
        $objs[] = &$mform->createElement('submit', 'submit', get_string('go'));
        $batchdescription = get_string('batchoperationsdescription', 'vpl');
        $mform->addElement('group', 'actionsgrp', $batchdescription, $objs, ' ', false);
    }
}
