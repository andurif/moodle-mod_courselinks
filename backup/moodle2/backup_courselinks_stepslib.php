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
 * Define all the backup steps that will be used by the backup_courselinks_activity_task
 *
 * @package     mod_courselinks
 * @category    backup
 * @copyright  2025 Anthony Durif, Université Clermont Auvergne
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the complete courselinks structure for backup
 *
 * @package     mod_courselinks
 * @category    backup
 * @copyright  2021 Anthony Durif, Université Clermont Auvergne
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

 */
class backup_courselinks_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the complete courselinks structure for backup
     */
    protected function define_structure() {
        $courselinks = new backup_nested_element('courselinks', ['id'], [
            'name', 'intro', 'introformat', 'displaytype', 'timemodified', 'links'
        ]);

        $courselinks->set_source_table('courselinks', ['id' => backup::VAR_ACTIVITYID]);

        return $this->prepare_activity_structure($courselinks);
    }
}
