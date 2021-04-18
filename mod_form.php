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
 * Add/update module form.
 *
 * @package    mod_courselinks
 * @copyright  2021 Anthony Durif, Université Clermont Auvergne
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/courselinks/lib.php');
require_once($CFG->dirroot.'/vendor/autoload.php');

/**
 * Class mod_courselinks_mod_form
 */
class mod_courselinks_mod_form extends moodleform_mod {

    /**
     * Function to construct the form.
     * @throws Exception
     * @throws coding_exception
     */
    function definition() {
        $mform = $this->_form;
        $error = null;

        $courses = get_linkable_courses();

        if (!empty($this->current->id)) {
            if (!empty($this->current->links)) {
                $links = json_decode($this->current->links);
                foreach ($links as $key => $link) {
                    // Check if the course is in the linkable courses list of the user.
                    if (!in_array($link, array_keys($courses))) {
                        unset($links[$key]);
                    }
                }
                $this->current->links = $links;
            }
        }

        if ($courses) {
            $mform->addElement('header', 'general', get_string('general'));

            $mform->addElement('text', 'name', get_string('name'), 'size=80');
            $mform->addRule('name', null, 'required', null, 'client');
            $mform->setType('name', PARAM_TEXT);

            $this->standard_intro_elements();
            $element = $mform->getElement('introeditor');
            $attributes = $element->getAttributes();
            $attributes['rows'] = 5;
            $element->setAttributes($attributes);

            $mform->addElement('searchableselector', 'links', get_string('links', 'mod_courselinks'), $courses, array('multiple'));
            $mform->addHelpButton('links', 'links', 'mod_courselinks');

            $displaychoices = array(
                'card'          => get_string('display:card', 'mod_courselinks'),
                'list'          => get_string('display:list', 'mod_courselinks'),
                'nav'           => get_string('display:nav', 'mod_courselinks'),
            );
            $mform->addElement('select', 'displaytype', get_string('display', 'mod_courselinks'), $displaychoices);
            $mform->addHelpButton('displaytype', 'display', 'mod_courselinks');

            $this->standard_coursemodule_elements();
            $this->add_action_buttons();
        } else {
            $this->standard_hidden_coursemodule_elements();
            $mform->addElement('html', get_string('error_courses', 'mod_courselinks'));
            $mform->addElement('cancel', '', get_string('back', 'mod_courselinks'));
        }
    }

    /**
     * Form validation function.
     * @param array $data
     * @param array $files
     * @return array
     */
    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ((isset($data['links']) && count($data['links']) < 1) || (count($data['links']) == 1 && empty($data['links'][0])) ) {
            $errors['links'] = get_string('error_links', 'mod_courselinks');
        }

        return $errors;
    }

    /**
     * Allows modules to modify the data returned by form get_data().
     * This method is also called in the bulk activity completion form.
     *
     * Only available on moodleform_mod.
     *
     * @param stdClass $data the form data to be modified.
     */
    public function data_postprocessing($data) {
        parent::data_postprocessing($data);
        if (!empty($data->completionunlocked)) {
            $autocompletion = !empty($data->completion) && $data->completion == COMPLETION_TRACKING_AUTOMATIC;
            if (empty($data->completionentriesenabled) || !$autocompletion) {
                $data->completionentries = 0;
            }
        }

        if (!empty($data->links)) {
            if (($key = array_search("", $data->links)) !== false) {
                unset($data->links[$key]);
            }
            $data->links = array_values($data->links);
        }
    }
}
