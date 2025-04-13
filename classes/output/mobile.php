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
 * Provides {@see \mod_courselinks\output\mobile} class.
 *
 * @package     mod_courselinks
 * @copyright  2025 Anthony Durif, Université Clermont Auvergne
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_courselinks\output;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/courselinks/lib.php');

/**
 * Controls the display of the plugin in the Mobile App.
 *
 * @package     mod_courselinks
 * @copyright  2025 Anthony Durif, Université Clermont Auvergne
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile {

    /**
     * Return the data for the CoreCourseModuleDelegate delegate.
     *
     * @param object $args
     * @return object
     */
    public static function mobile_course_view($args) {
        global $OUTPUT, $USER, $DB;

        $args = (object) $args;
        $cm = get_coursemodule_from_id('courselinks', $args->cmid);
        $context = \context_module::instance($cm->id);

        require_login($args->courseid, false, $cm, true, true);
        require_capability('mod/courselinks:view', $context);

        $courselinks = $DB->get_record('courselinks', ['id' => $cm->instance], '*', MUST_EXIST);
        $course = get_course($cm->course);

        // Pre-format some of the texts for the mobile app.
        $courselinks->name = external_format_string($courselinks->name, $context);
        [$courselinks->intro, $courselinks->introformat] = external_format_text(
            $courselinks->intro, $courselinks->introformat, $context,'mod_courselinks', 'intro');

        $data = [
            'cmid' => $cm->id,
            'courselinks' => $courselinks,
            'courses' => courselinks_get_courses_as_array($courselinks),
            'showdescription' => $cm->showdescription,
        ];

        return [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('mod_courselinks/mobile_view', $data),
                ],
            ],
            'javascript' => '',
            'otherdata' => '',
            'files' => [],
        ];
    }
}
