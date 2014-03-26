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
 * Helper functions for course_overview block
 *
 * @package    block_course_overview_lite
 * @copyright  2012 Adam Olley <adam.olley@netspot.com.au>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function block_course_overview_lite_get_overviews($courses) {
    $htmlarray = array();
    if ($modules = get_plugin_list_with_function('mod', 'print_overview')) {
        foreach ($modules as $fname) {
            $fname($courses, $htmlarray);
        }
    }
    return $htmlarray;
}
/**
 * Sets user preference for maximum courses to be displayed in course_overview block
 *
 * @param int $number maximum courses which should be visible
 */
function block_course_overview_lite_update_mynumber($number) {
    set_user_preference('course_overview_lite_number_of_courses', $number);
}

/**
 * Sets user course sorting preference in course_overview block
 *
 * @param array $sortorder sort order of course
 */
function block_course_overview_lite_update_myorder($sortorder) {
    set_user_preference('course_overview_lite_course_order', serialize($sortorder));
}

function block_course_overview_lite_update_courses_hidden($hiddencourses) {
    set_user_preference('course_overview_lite_courses_hidden', serialize($hiddencourses));
}

/**
 * Returns maximum number of courses which will be displayed in course_overview block
 *
 * @return int maximum number of courses
 */
function block_course_overview_lite_get_max_user_courses() {
    // Get block configuration.
    $config = get_config('block_course_overview_lite');
    $limit = $config->defaultmaxcourses;

    // If max course is not set then try get user preference.
    if (empty($config->forcedefaultmaxcourses)) {
        $limit = get_user_preferences('course_overview_lite_number_of_courses', $limit);
    }
    return $limit;
}

/**
 * Return sorted list of user courses
 *
 * @return array list of sorted courses and count of courses.
 */
function block_course_overview_lite_get_sorted_courses($usenav = true) {
    global $PAGE;

    $courses = array();
    $ajax = false;
    $highlightprefix = get_config('block_course_overview_lite', 'highlightprefix');
    $highlightdelim = get_config('block_course_overview_lite', 'highlightdelim');
    $hiddencourses = block_course_overview_lite_get_courses_hidden();

    if ($usenav) {
        $PAGE->navigation->initialise();
        $navigation = clone($PAGE->navigation);
        foreach (array($navigation) as $item) {
            if (!$item->display && !$item->contains_active_node() ||
                $item->type != navigation_node::TYPE_SYSTEM || empty($item->action)) {
                continue;
            }
            $my = $item->get('mycourses');
            if (!empty($my) && $my->children) {
                if ($my->forceopen) {
                    $collection = $my->find_all_of_type(navigation_node::TYPE_COURSE);
                    foreach ($collection as $coursenode) {
                        $course = new stdClass();
                        $course->id = $coursenode->key;
                        $course->fullname = $coursenode->title;
                        $course->shortname = $coursenode->shorttext;
                        $course->url = $coursenode->action;
                        $course->hidden = $coursenode->hidden;
                        $course->modinfo = '';
                        $course->sectioncache = '';
                        $course->current = (!empty($highlightprefix) &&
                            block_course_overview_lite_current($highlightprefix, $highlightdelim, $course->shortname));
                        $course->userhidden = array_key_exists($course->id, $hiddencourses)
                            && ($hiddencourses[$course->id] == true);
                        $courses[$course->id] = $course;
                    }
                }
                break;
            }
        }
    }

    if (empty($courses)) {
        if (ajaxenabled() && $usenav) {
            $ajax = true;
            $PAGE->requires->string_for_js('move', 'moodle');
            $PAGE->requires->string_for_js('hide_icon_alt', 'block_course_overview_lite');
        } else {
            $rawcourses = enrol_get_my_courses('id, shortname, fullname, modinfo, sectioncache');
            foreach ($rawcourses as $rawcourse) {
                $course = new stdClass();
                $course->id = $rawcourse->id;
                $course->fullname = $rawcourse->fullname;
                $course->shortname = $rawcourse->shortname;
                $url = new moodle_url('/course/view.php', array('id' => $rawcourse->id));
                $course->url = $url->out();
                $course->hidden = $rawcourse->visible == 0;
                $course->modinfo = $rawcourse->modinfo;
                $course->sectioncache = $rawcourse->sectioncache;
                $course->current = (!empty($highlightprefix) &&
                    block_course_overview_lite_current($highlightprefix, $highlightdelim, $rawcourse->shortname));
                $course->userhidden = array_key_exists($course->id, $hiddencourses) && ($hiddencourses[$course->id] == true);
                $courses[$course->id] = $course;
            }
        }
    }
    if (!$ajax) {
        $hiddencourses = block_course_overview_lite_remove_old_courses_from_hidden_list($hiddencourses, array_keys($courses));
        $courses = block_course_overview_lite_sort_courses($courses);
    }
    return array($courses, count($courses),  array_sum($hiddencourses), $ajax);
}

function block_course_overview_lite_sort_courses($courses) {
    $limit = block_course_overview_lite_get_max_user_courses();
    // Hard limit the courses to 100 to skip the user pref save error.
    $limit = $limit > 100 ? 100 : $limit;
    $order = array();
    if (!is_null($usersortorder = get_user_preferences('course_overview_lite_course_order'))) {
        $order = unserialize($usersortorder);
    }

    $sortedcourses = array();
    $remainingcourses = array();
    $counter = 0;
    // Get courses in sort order into list.
    foreach ($order as $key => $cid) {
        if (($counter >= $limit) && ($limit != 0)) {
            break;
        }
        if (isset($courses[$cid])) {
            $sortedcourses[] = $courses[$cid];
            unset($courses[$cid]);
            $counter++;
        }
    }
    // Append unsorted courses if limit allows.
    foreach ($courses as $course) {
        if (($limit != 0) && ($counter >= $limit)) {
            break;
        }
        if ($course->current) {
            $sortedcourses[] = $course;
        } else {
            $remainingcourses[] = $course;
        }
        $counter++;
    }

    return array_merge($sortedcourses, $remainingcourses);
}


function block_course_overview_lite_current($prefix, $delimiter, $text) {
    if (!empty($delimiter)) {
        $prefixs = explode($delimiter , $prefix);
        foreach ($prefixs as $match) {
            if (strpos($text, $match) !== false) {
                return true;
            }
        }
        return false;
    }
    return strpos($text, $prefix) !== false;
}

function block_course_overview_lite_get_courses_hidden() {
    $hiddencourses = array();
    if (!is_null($userpref = get_user_preferences('course_overview_lite_courses_hidden'))) {
        $hiddencourses = unserialize($userpref);
    }
    return $hiddencourses;
}

function block_course_overview_lite_remove_old_courses_from_hidden_list($hiddencourses, $usercourseids) {
    $hiddencourseremoved = false;
    foreach (array_keys($hiddencourses) as $hiddencourseid) {
        if (!in_array($hiddencourseid, $usercourseids)) {
            unset($hiddencourses[$hiddencourseid]);
            $hiddencourseremoved = true;
        }
    }
    if ($hiddencourseremoved) {
        set_user_preference('course_overview_lite_courses_hidden', serialize($hiddencourses));
    }

    return $hiddencourses;
}
