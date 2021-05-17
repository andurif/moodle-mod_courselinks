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
 * Mandatory public API of url module
 *
 * @package    mod_courselinks
 * @copyright  2021 Anthony Durif, Université Clermont Auvergne
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_course\external\course_summary_exporter;

defined('MOODLE_INTERNAL') || die;

/**
 * Defines the type of the activity and specific supports.
 * @param $feature
 * @return bool|int|null
 */
function courselinks_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:           return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;
        case FEATURE_NO_VIEW_LINK:            return true;
        default: return null;
    }
}

/**
 * Save a courselinks instance in the database.
 * @param object $data
 * @param object $mform
 * @return int new instance id
 */
function courselinks_add_instance($data, $mform) {
    global $DB;

    if (!empty($data->name)) {
        $data->intro = $data->intro;
        $data->introformat = 1;
        $data->timemodified = time();
        $data->links = json_encode($data->links);

        $data->id = $DB->insert_record('courselinks', $data);
    }

    return $data->id;
}

/**
 * Update a courselinks instance.
 * @param object $data
 * @param object $mform
 * @return bool true if update is ok and false in other cases.
 */
function courselinks_update_instance($data, $mform) {
    global $DB;

    if (!empty($data->name)) {
        $data->intro = $data->intro;
        $data->introformat = 1;
        $data->timemodified = time();
        $data->links = json_encode($data->links);
        $data->id = $data->instance;

        $DB->update_record('courselinks', $data);

        return true;
    }

    return false;
}

/**
 * Delete a courselinks instance.
 * @param int $id
 * @return bool true if the deletion is ok and false in other cases.
 */
function courselinks_delete_instance($id) {
    global $DB;

    if (!$courselink = $DB->get_record('courselinks', array('id' => $id))) {
        return false;
    }

    $cm = get_coursemodule_from_instance('courselinks', $id);
    \core_completion\api::update_completion_date_event($cm->id, 'courselinks', $id, null);

    // Note: all context files are deleted automatically.
    $DB->delete_records('courselinks', array('id' => $courselink->id));

    return true;
}

/**
 * Add a get_coursemodule_info function in case adding 'extra' information for the course (see resource).
 *
 * @param stdClass $coursemodule The coursemodule object (record).
 * @return cached_cm_info An object on information that the courses
 *                        will know about (most noticeably, an icon).
 */
function courselinks_get_coursemodule_info($coursemodule) {
    global $CFG, $DB;

    if (!$resource = $DB->get_record('courselinks', array('id' => $coursemodule->instance),
        'id, course, name, intro, introformat, displaytype, links, timemodified')) {
        return null;
    }

    $info = new cached_cm_info();
    $info->name = $resource->name;
    $info->content = "";

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $info->content = format_module_intro('courselinks', $resource, $coursemodule->id, false);
    }

    $info->content .= courselinks_get_content($resource);

    return $info;
}

/**
 * Function to return courses on which we have permissions to do a link.
 * @throws coding_exception
 */
function courselinks_get_linkable_courses() {
    global $USER;

    $courses = [];
    $mycourses = enrol_get_my_courses(null, 'fullname ASC,visible DESC,sortorder ASC');
    foreach ($mycourses as $key => $mycourse) {
        $tounset = false;
        if (!$mycourse->visible) {
            if (!has_capability('moodle/role:assign', context_course::instance($mycourse->id))) {
                $tounset = true;
            }
        }

        if ($tounset) {
            unset($mycourses[$key]);
        }
    }

    foreach ($mycourses as $mycourse) {
        $courses[$mycourse->id] = $mycourse->fullname;
    }

    return $courses;
}

/**
 * Function to set the module content in a label
 * @param cm_info $cm course module id
 * @throws dml_exception
 */
function courselinks_cm_info_view(cm_info $cm) {
    global $DB;

    $courselinks = $DB->get_record('courselinks', array('id' => $cm->instance));
    if ($courselinks) {
        $links = json_decode($courselinks->links);
        $content = ($links) ? courselinks_get_content($courselinks) : "";
    }

    // Also show mod description.
    if ($cm->showdescription) {
        $content = (!empty($courselinks->links)) ? $courselinks->intro . '<br/>' . $content : '';
    }

    $cm->set_content($content);
}

/**
 * Return the content in the function of the selected type in the form.
 * @param $courselinks the module
 * @return string the content to display
 * @throws dml_exception
 * @throws moodle_exception
 * @throws require_login_exception
 */
function courselinks_get_content($courselinks) {
    $links = json_decode($courselinks->links);
    switch($courselinks->displaytype) {
        case 'card':
        default:
            $content = courselinks_get_content_card($links);
            break;
        case 'list':
            $content = courselinks_get_content_list($links);
            break;
        case 'nav':
            $content = courselinks_get_content_nav($links);
            break;
    }

    return $content;
}

/**
 * Returns html content if the user selects to display links with cards.
 * @param $links
 * @return string
 * @throws dml_exception
 * @throws moodle_exception
 * @throws require_login_exception
 */
function courselinks_get_content_card($links) {
    $content = html_writer::start_tag('div', array('class' => 'row row-cols-1 row-cols-md-4 justify-content-center')) . PHP_EOL;;
    foreach ($links as $link) {
        try {
            $course = get_course($link);
        }
        catch (Exception $exc) {
            // Next course.
            continue;
        }
        if (courselinks_has_access($course)) {
            $url = new moodle_url('/course/view.php', array('id' => $course->id));
            $contentlinks = html_writer::start_tag('div', array('class' => 'col mb-3 text-center', 'style' => 'margin-bottom: 20px;')) . PHP_EOL;
            $contentlinks .= html_writer::start_tag('div', array('class' => 'card shadow-lg h-100')) . PHP_EOL;
            $contentlinks .= html_writer::link($url , html_writer::img(courselinks_get_course_image($course), $course->fullname, array('class' => 'card-img-top img-fluid', 'style' => 'max-height: 200px;', 'target' => '_blank')). PHP_EOL);
            $contentlinks .= html_writer::start_tag('div', array('class' => 'card-body')) . PHP_EOL;
            $contentlinks .= html_writer::start_tag('h5', array('class' => 'card-title')) . PHP_EOL;
            $contentlinks .= html_writer::link($url , $course->fullname, array('target' => '_blank'));
            $contentlinks .= html_writer::end_tag('h5') . PHP_EOL;
            $contentlinks .= html_writer::end_tag('div') . PHP_EOL;
            $contentlinks .= html_writer::end_tag('div') . PHP_EOL;
            $contentlinks .= html_writer::end_tag('div') . PHP_EOL;
            $content = (!empty($contentlinks)) ? $content . $contentlinks : $content;
        }
    }
    $content .= html_writer::end_tag('div') . PHP_EOL;

    return $content;
}

/**
 * Returns html content if the user selects to display links with a list.
 * @param $links
 * @return string
 * @throws dml_exception
 * @throws moodle_exception
 * @throws require_login_exception
 */
function courselinks_get_content_nav($links) {
    $content = html_writer::start_tag('ul', array('class' => 'nav nav-pills justify-content-center', 'style' => 'list-style: none;')). PHP_EOL;
    foreach ($links as $link) {
        try {
            $course = get_course($link);
        }
        catch (Exception $exc) {
            //Next course.
            continue;
        }
        if (courselinks_has_access($course)) {
            $url = new moodle_url('/course/view.php', array('id' => $course->id));
            $contentlinks = html_writer::start_tag('li', array('class' => 'nav-item')). PHP_EOL;
            $contentlinks .= html_writer::link($url , $course->fullname, array('class' => 'nav-link active', 'style' => 'border: 1px solid white', 'target' => '_blank')). PHP_EOL;
            $contentlinks .= html_writer::end_tag('li') . PHP_EOL;
            $content = (!empty($contentlinks)) ? $content . $contentlinks : $content;
        }
    }
    $content .= html_writer::end_tag('ul') . PHP_EOL;

    return $content;
}

/**
 * Returns html content if the user selects to display links with a navigation menu.
 * @param $links
 * @return string
 * @throws dml_exception
 * @throws moodle_exception
 * @throws require_login_exception
 */
function courselinks_get_content_list($links) {
    $content = html_writer::start_tag('div', array('class' => 'nav list-group justify-content-center')) . PHP_EOL;
    foreach ($links as $link) {
        try {
            $course = get_course($link);
        }
        catch (Exception $exc) {
            // Next course.
            continue;
        }
        if (courselinks_has_access($course)) {
            $url = new moodle_url('/course/view.php', array('id' => $course->id));
            $contentlinks = html_writer::link($url, $course->fullname, array('class' => 'list-group-item list-group-item-action', 'target' => '_blank')). PHP_EOL;
            $content = (!empty($contentlinks)) ? $content . $contentlinks : $content;
        }
    }
    $content .= html_writer::end_tag('div') . PHP_EOL;

    return $content;
}

/**
 * Returns the course image.
 * @param $course the course.
 * @return false|mixed|object|string|null the image.
 * @throws dml_exception
 */
function courselinks_get_course_image($course) {
    global $PAGE;
    if ($course->id == 1) {
        return get_config('theme_bandeau', 'default_course_img');
    }
    $image = (class_exists(course_summary_exporter::class) && method_exists(course_summary_exporter::class, 'get_course_image')) ? course_summary_exporter::get_course_image($course) : null;
    // $image = (!$image) ? $PAGE->get_renderer('core')->get_generated_image_for_id($course->id) : $image; //@todo: some errors after duplicate action
    $image = (!$image) ? courselinks_get_generated_image_for_id($course->id) : $image;

    return $image;
}

/**
 * Get the course pattern datauri to show on a course card.
 *
 * The datauri is an encoded svg that can be passed as a url.
 * @param int $id Id to use when generating the pattern
 * @return string datauri
 */
function courselinks_get_generated_image_for_id($id) {
    if (get_config('core_admin', 'coursecolor1')) {
        $colornumbers = range(1, 10);
        $basecolors = [];
        foreach ($colornumbers as $number) {
            $basecolors[] = get_config('core_admin', 'coursecolor' . $number);
        }
    } else {
        $basecolors = ['#81ecec', '#74b9ff', '#a29bfe', '#dfe6e9', '#00b894',
            '#0984e3', '#b2bec3', '#fdcb6e', '#fd79a8', '#6c5ce7'];
    }

    $color = $basecolors[$id % 10];
    $pattern = new \core_geopattern();
    $pattern->setColor($color);
    $pattern->patternbyid($id);

    return $pattern->datauri();
}

/**
 * Function to check if the current user has access or right to the course given in param.
 * @param $course the course.
 * @return true if the user has access and false in other cases.
 */
function courselinks_has_access($course) {
    global $USER, $DB;

    if (is_primary_admin($USER)) {
        return true;
    }

    $access = false;
    $coursecontext = context_course::instance($course->id);

    // Make sure the course itself is not hidden.
    if (is_role_switched($course->id)) {
        // When switching roles ignore the hidden flag - user had to be in course to do the switch.
    } else {
        if (!$course->visible and !has_capability('moodle/course:viewhiddencourses', $coursecontext)) {
            // Originally there was also test of parent category visibility, BUT is was very slow in complex queries
            // involving "my courses" now it is also possible to simply hide all courses user is not enrolled in :-).
            return false;
        }
    }

    // Is the user enrolled?
    if (\core\session\manager::is_loggedinas()) {
        // Make sure the REAL person can access this course first.
        $realuser = \core\session\manager::get_realuser();
        if (!is_enrolled($coursecontext, $realuser->id, '', true) and
            !is_viewing($coursecontext, $realuser->id) and !is_siteadmin($realuser->id)) {
                return false;
        }
    }

    if (is_role_switched($course->id)) {
        // Ok, user had to be inside this course before the switch.
        $access = true;
    } else if (is_viewing($coursecontext, $USER)) {
        // Ok, no need to mess with enrol.
        $access = true;
    } else {
        if (isset($USER->enrol['enrolled'][$course->id])) {
            if ($USER->enrol['enrolled'][$course->id] > time()) {
                $access = true;
                if (isset($USER->enrol['tempguest'][$course->id])) {
                    unset($USER->enrol['tempguest'][$course->id]);
                    remove_temp_course_roles($coursecontext);
                }
            } else {
                // Expired.
                unset($USER->enrol['enrolled'][$course->id]);
            }
        }
        if (isset($USER->enrol['tempguest'][$course->id])) {
            if ($USER->enrol['tempguest'][$course->id] == 0) {
                $access = true;
            } else if ($USER->enrol['tempguest'][$course->id] > time()) {
                $access = true;
            } else {
                // Expired.
                unset($USER->enrol['tempguest'][$course->id]);
                remove_temp_course_roles($coursecontext);
            }
        }

        if (!$access) {
            // Cache not ok.
            $until = enrol_get_enrolment_end($coursecontext->instanceid, $USER->id);
            if ($until !== false) {
                // Active participants may always access, a timestamp in the future, 0 (always) or false.
                if ($until == 0) {
                    $until = ENROL_MAX_TIMESTAMP;
                }
                $USER->enrol['enrolled'][$course->id] = $until;
                $access = true;

            } else if (core_course_category::can_view_course_info($course)) {
                $params = array('courseid' => $course->id, 'status' => ENROL_INSTANCE_ENABLED);
                $instances = $DB->get_records('enrol', $params, 'sortorder, id ASC');
                $enrols = enrol_get_plugins(true);
                // First ask all enabled enrol instances in course if they want to auto enrol user.
                foreach ($instances as $instance) {
                    if (!isset($enrols[$instance->enrol])) {
                        continue;
                    }
                    // Get a duration for the enrolment, a timestamp in the future, 0 (always) or false.
                    $until = $enrols[$instance->enrol]->try_autoenrol($instance);
                    if ($until !== false) {
                        if ($until == 0) {
                            $until = ENROL_MAX_TIMESTAMP;
                        }
                        $USER->enrol['enrolled'][$course->id] = $until;
                        $access = true;
                        break;
                    }
                }
                // If not enrolled yet try to gain temporary guest access.
                if (!$access) {
                    foreach ($instances as $instance) {
                        if (!isset($enrols[$instance->enrol])) {
                            continue;
                        }
                        // Get a duration for the guest access, a timestamp in the future or false.
                        $until = $enrols[$instance->enrol]->try_guestaccess($instance);
                        if ($until !== false and $until > time()) {
                            $USER->enrol['tempguest'][$course->id] = $until;
                            $access = true;
                            break;
                        }
                    }
                }
            } else {
                $access = false;
                // User is not enrolled and is not allowed to browse courses here.
                // if ($preventredirect) {
                // throw new require_login_exception('Course is not available');
                // }
                // PAGE->set_context(null);
                // We need to override the navigation URL as the course won't have been added to the navigation and thus
                // the navigation will mess up when trying to find it.
                // navigation_node::override_active_url(new moodle_url('/'));
                // notice(get_string('coursehidden'), $CFG->wwwroot .'/');
            }
        }
    }

    return $access;
}