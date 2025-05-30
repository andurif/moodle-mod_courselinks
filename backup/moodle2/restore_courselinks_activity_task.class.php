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
 * @copyright  2025 Anthony Durif, Université Clermont Auvergne
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/courselinks/backup/moodle2/restore_courselinks_stepslib.php');

/**
 * Defines all settings and step to perform the activity restore.
 *
 * @package     mod_courselinks
 * @copyright  2025 Anthony Durif, Université Clermont Auvergne
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_courselinks_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Choice only has one structure step.
        $this->add_step(new restore_courselinks_activity_structure_step('courselinks_structure', 'courselinks.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    public static function define_decode_contents() {
        $contents = [];

        $contents[] = new restore_decode_content('courselinks', ['intro'], 'courselinks');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    public static function define_decode_rules() {
        $rules = [];

        // $rules[] = new restore_decode_rule('COURSELINKSVIEWBYID', '/mod/courselinks/view.php?id=$1', 'course_module');
        // $rules[] = new restore_decode_rule('COURSELINKSINDEX', '/mod/courselinks/index.php?id=$1', 'course');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@see restore_logs_processor} when restoring
     * courselinks logs. It must return one array
     * of {@see restore_log_rule} objects
     */
    public static function define_restore_log_rules() {
        $rules = [];

        $rules[] = new restore_log_rule('courselinks', 'add', 'view.php?id={course_module}', '{courselinks}');
        $rules[] = new restore_log_rule('courselinks', 'update', 'view.php?id={course_module}', '{courselinks}');
        $rules[] = new restore_log_rule('courselinks', 'view', 'view.php?id={course_module}', '{courselinks}');
        // $rules[] = new restore_log_rule('courselinks', 'fetch', 'view.php?id={course_module}', '{courselinks}');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@see restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@see restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    public static function define_restore_log_rules_for_course() {
        $rules = [];

        // Fix old wrong uses (missing extension).
        $rules[] = new restore_log_rule('courselinks', 'view all', 'index.php?id={course}', null);

        return $rules;
    }
}
