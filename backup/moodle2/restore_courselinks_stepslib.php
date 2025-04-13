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
 *
 * @package     mod_courselinks
 * @category    backup
 * @copyright  2021 Anthony Durif, Université Clermont Auvergne
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Structure step to restore one courselinks activity.
 *
 * @package     mod_courselinks
 * @category    backup
 * @copyright  2021 Anthony Durif, Université Clermont Auvergne
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_courselinks_activity_structure_step extends restore_activity_structure_step {

    /**
     * Attaches the handlers of the backup XML tree parts.
     *
     * @return array of restore_path_element
     */
    protected function define_structure() {
        $paths = [];
        $paths[] = new restore_path_element('courselinks', '/activity/courselinks');

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process the /activity/courselinks path element.
     *
     * @param object|array $data node contents
     */
    protected function process_courselinks($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();

        $data->timefetched = 0;

        $newitemid = $DB->insert_record('courselinks', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Callback to be executed after the restore.
     */
    protected function after_execute() {
        // Add courselinks related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_courselinks', 'intro', null);
    }
}
