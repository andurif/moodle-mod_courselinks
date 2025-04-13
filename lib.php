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
 * @copyright  2025 Anthony Durif, Université Clermont Auvergne
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
    switch ($feature) {
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

    return null;
}

/**
 * Save a courselinks instance in the database.
 * @param object $data
 * @param object $mform
 * @return int new instance id.
 */
function courselinks_add_instance($data, $mform) {
    global $DB;

    if (!empty($data->name)) {
        $data->intro = $data->intro;
        $data->introformat = 1;
        $data->timemodified = time();
        $data->links = json_encode($data->links);
        $data->opentype = intval($data->opentype);

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
        $data->opentype = intval($data->opentype);
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

    if (!$courselink = $DB->get_record('courselinks', ['id' => $id])) {
        return false;
    }

    $cm = get_coursemodule_from_instance('courselinks', $id);
    \core_completion\api::update_completion_date_event($cm->id, 'courselinks', $id, null);

    // Note: all context files are deleted automatically.
    $DB->delete_records('courselinks', ['id' => $courselink->id]);

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

    if (!$resource = $DB->get_record('courselinks', ['id' => $coursemodule->instance],
        'id, course, name, intro, introformat, displaytype, links, timemodified, show_all_courses, opentype, cards_by_line')) {
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
 * @return array courses which can be displayed with the plugin.
 * @throws coding_exception
 */
function courselinks_get_linkable_courses() {
    $courses = [];
    $mycourses = enrol_get_my_courses(null, 'fullname ASC,visible DESC,sortorder ASC');
    foreach ($mycourses as $key => $mycourse) {
        $tounset = false;
        try {
            if (!has_capability('moodle/role:assign', context_course::instance($mycourse->id))) {
                $tounset = true;
            }
        } catch (Exception $e) {
            $tounset = true;
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
 * Fonction to get the list of courses we want to display as an array.
 * This array is specificly use to render this resource in the mobile moodle app.
 * @param object $courselinks the resource.
 * @return array an array with link to course and name as items.
 */
function courselinks_get_courses_as_array($courselinks) {
    $links = json_decode($courselinks->links);
    $courses = [];

    foreach ($links as $link) {
        try {
            $course = get_course($link);
        } catch (Exception $exc) {
            // Next course.
            continue;
        }

        if (courselinks_has_access($course)) {
            $url = new moodle_url('/course/view.php', ['id' => $course->id]);
            $courses[] = [
                'href'  => $url->out(),
                'title' => $course->fullname,
            ];
        }
    }

    return $courses;
}

/**
 * Function to set the module content in a label.
 * @param cm_info $cm course module id.
 * @throws dml_exception
 */
function courselinks_cm_info_view(cm_info $cm) {
    global $DB;

    $courselinks = $DB->get_record('courselinks', ['id' => $cm->instance]);
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
 * @param object $courselinks the module.
 * @return string the content to display.
 * @throws dml_exception
 * @throws moodle_exception
 * @throws require_login_exception
 */
function courselinks_get_content($courselinks) {
    global $CFG;
    require_once($CFG->dirroot.'/lib/resourcelib.php');

    $links = json_decode($courselinks->links);
    $courses = [];
    $content = "";

    foreach ($links as $link) {
        try {
            $course = get_course($link);
            if (courselinks_has_access($course) || ($course->visible && $courselinks->show_all_courses)) {
                // We display the course link if the user has access to it.
                // Or if the option to dispay all courses has been checked in the form.
                $courses[] = $course;
            }
        } catch (Exception $exc) {
            // Next course.
            continue;
        }
    }

    if (!empty($courses)) {
        // Call the right function in function of the selected display type.
        switch ($courselinks->displaytype) {
            case 'card':
            default:
                $content = courselinks_get_content_card($links, $courselinks->opentype, $courselinks->cards_by_line);
                break;
            case 'list':
                $content = courselinks_get_content_list($links, $courselinks->opentype);
                break;
            case 'nav':
                $content = courselinks_get_content_nav($links, $courselinks->opentype);
                break;
        }
    }

    return $content;
}

/**
 * Returns html content if the user selects to display links with cards.
 * @param array $links links to display.
 * @param string $opentype the selected option for opening.
 * @param int $cards_by_line the number of cards to display by line.
 * @return string the content.
 * @throws dml_exception
 * @throws moodle_exception
 * @throws require_login_exception
 */
function courselinks_get_content_card($links, $opentype, $cards_by_line) {
    $class_by_number = [
        0 => (count($links) <= 4) ? 'col-lg-3 col-md-4 col-sm-6 col-xs-12' : 'col-lg-2 col-md-4 col-sm-6 col-xs-12',
        2 => 'col-lg-6 col-md-6 col-sm-6 col-xs-12',
        3 => 'col-lg-4 col-md-4 col-sm-6 col-xs-12',
        4 => 'col-lg-3 col-md-4 col-sm-6 col-xs-12',
        6 => 'col-lg-2 col-md-3 col-sm-6 col-xs-12',
    ];
    $content = html_writer::start_tag('div', ['class' => 'col-12 justify-content-center card-deck']). PHP_EOL;;
    foreach ($links as $link) {
        try {
            $course = get_course($link);
        } catch (Exception $exc) {
            // Next course.
            continue;
        }
        if (courselinks_has_access($course)) {
            $url = new moodle_url('/course/view.php', ['id' => $course->id]);
            $contentlinks = html_writer::start_tag('div', ['class' => $class_by_number[$cards_by_line] .
                    ' text-center', 'style' => 'margin-bottom: 20px;']) . PHP_EOL;
            $contentlinks .= html_writer::start_tag('div', ['class' => 'card shadow-lg h-100']) . PHP_EOL;
            $contentlinks .= html_writer::link($url , html_writer::img(courselinks_get_course_image($course), $course->fullname,
                    ['class' => 'card-img-top img-fluid', 'style' => 'max-height: 200px;', 'target' => '_blank']). PHP_EOL, courselinks_get_html_link_options($opentype, $url));
            $contentlinks .= html_writer::start_tag('div', ['class' => 'card-body']) . PHP_EOL;
            $contentlinks .= html_writer::start_tag('h5', ['class' => 'card-title']) . PHP_EOL;
            $contentlinks .= html_writer::link($url , $course->fullname, courselinks_get_html_link_options($opentype, $url));
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
 * @param array $links the course list.
 * @param string $opentype the selected option for opening.
 * @return string the content.
 * @throws dml_exception
 * @throws moodle_exception
 * @throws require_login_exception
 */
function courselinks_get_content_nav($links, $opentype) {
    $content = html_writer::start_tag('ul', [
        'class' => 'nav nav-pills justify-content-center',
        'style' => 'list-style: none;'
    ]). PHP_EOL;
    foreach ($links as $link) {
        try {
            $course = get_course($link);
        } catch (Exception $exc) {
            // Next course.
            continue;
        }
        if (courselinks_has_access($course)) {
            $url = new moodle_url('/course/view.php', ['id' => $course->id]);
            $contentlinks = html_writer::start_tag('li', ['class' => 'nav-item']). PHP_EOL;
            $contentlinks .= html_writer::link($url , $course->fullname, courselinks_get_html_link_options($opentype, $url, [
                'class' => 'nav-link active',
                'style' => 'border: 1px solid white'
            ])) . PHP_EOL;
            $contentlinks .= html_writer::end_tag('li') . PHP_EOL;
            $content = (!empty($contentlinks)) ? $content . $contentlinks : $content;
        }
    }
    $content .= html_writer::end_tag('ul') . PHP_EOL;

    return $content;
}

/**
 * Returns html content if the user selects to display links with a navigation menu.
 * @param array $links the course list.
 * @param string $opentype the selected option for opening.
 * @return string the content.
 * @throws dml_exception
 * @throws moodle_exception
 * @throws require_login_exception
 */
function courselinks_get_content_list($links, $opentype) {
    $content = html_writer::start_tag('div', ['class' => 'nav list-group justify-content-center']) . PHP_EOL;
    foreach ($links as $link) {
        try {
            $course = get_course($link);
        } catch (Exception $exc) {
            // Next course.
            continue;
        }
        if (courselinks_has_access($course)) {
            $url = new moodle_url('/course/view.php', ['id' => $course->id]);
            $contentlinks = html_writer::link($url , $course->fullname, courselinks_get_html_link_options($opentype, $url, [
                'class' => 'list-group-item list-group-item-action'
            ])) . PHP_EOL;
            $content = (!empty($contentlinks)) ? $content . $contentlinks : $content;
        }
    }
    $content .= html_writer::end_tag('div') . PHP_EOL;

    return $content;
}

/**
 * Get the html element that represents the link to the destination course given in paramaters.
 * @param string $opentype the selected option for opening.
 * @param string $url the url course link.
 * @param array $givenoptions the options.
 * @return array all the options.
 */
function courselinks_get_html_link_options($opentype, $url, $givenoptions = []) {
    $options = [];
    if ($opentype == RESOURCELIB_DISPLAY_NEW) {
        // Open the link in a new tab.
        $options = ['target' => '_blank'];
    } else if ($opentype == RESOURCELIB_DISPLAY_POPUP) {
        // Open the link in a new popup window.
        $fullurl = "$url&amp;redirect=1";
        $wh = "width=620,height=450,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes";
        $options = ['onclick' => "window.open('$fullurl', '', '$wh'); return false;"];
    }

    return array_merge($givenoptions, $options);
}

/**
 * Returns the course image.
 * @param object $course the course.
 * @return false|mixed|object|string|null the image.
 * @throws dml_exception
 */
function courselinks_get_course_image($course) {
    $image = (class_exists(course_summary_exporter::class) && method_exists(course_summary_exporter::class, 'get_course_image'))
        ? course_summary_exporter::get_course_image($course) : null;
    $image = (!$image) ? courselinks_get_generated_image_for_id($course->id) : $image;

    return $image;
}

/**
 * Get the course pattern datauri to show on a course card.
 * The datauri is an encoded svg that can be passed as a url.
 * @param int $id Id to use when generating the pattern.
 * @return string datauri.
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
    if (!$course->visible && !has_capability('moodle/course:viewhiddencourses', $coursecontext)) {
        // Originally there was also test of parent category visibility, BUT is was very slow in complex queries
        // involving "my courses" now it is also possible to simply hide all courses user is not enrolled in :-).
        return false;
    }

    // Is the user enrolled?
    if (\core\session\manager::is_loggedinas()) {
        // Make sure the REAL person can access this course first.
        $realuser = \core\session\manager::get_realuser();
        if (!is_enrolled($coursecontext, $realuser->id, '', true) &&
            !is_viewing($coursecontext, $realuser->id) && !is_siteadmin($realuser->id)) {
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
                $params = ['courseid' => $course->id, 'status' => ENROL_INSTANCE_ENABLED];
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
                        if ($until !== false && $until > time()) {
                            $USER->enrol['tempguest'][$course->id] = $until;
                            $access = true;
                            break;
                        }
                    }
                }
            } else {
                $access = false;
            }
        }
    }

    return $access;
}
