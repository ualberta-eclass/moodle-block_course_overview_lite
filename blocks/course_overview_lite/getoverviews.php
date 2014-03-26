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
 * @package eclass-blocks-course-overview-lite
 * @author joshstagg
 * @copyright Josh Stagg
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('AJAX_SCRIPT', true);

/* Include config */
require_once(dirname(__FILE__) . '/../../config.php');
/* Include course lib for its functions */
require_once($CFG->dirroot.'/blocks/course_overview_lite/locallib.php');

require_sesskey();
require_login();

global $USER, $PAGE;
try {
    //  Start buffer capture so that we can remove any errors.
    ob_start();
    $PAGE->set_context(context_system::instance());
    $json = array();
    if (confirm_sesskey()) {
        list($courses, $totalcourses, $numhidden, $ajax) = block_course_overview_lite_get_sorted_courses(false);
        $unsorted = array();
        foreach ($courses as $key => $c) {
            if (isset($USER->lastcourseaccess[$c->id])) {
                $courses[$key]->lastaccess = $USER->lastcourseaccess[$c->id];
            } else {
                $courses[$key]->lastaccess = 0;
            }
            $unsorted[$c->id] = $courses[$key];
        }
        $overviews = block_course_overview_lite_get_overviews($unsorted);
        foreach ($overviews as $cid => $overview) {
            $output = '';
            foreach (array_keys($overview) as $module) {
                $output .= html_writer::start_tag('div',
                    array('class' => 'activity_overview'));
                $output .= $overview[$module];
                $output .= html_writer::end_tag('div');
            }
            $json[$cid] = $output;
        }
    }
    //  Stop buffering errors at this point.
    $html = ob_get_contents();
    ob_end_clean();
} catch (Exception $e) {
    die(json_encode(array('error' => 'Exception raised', 'data' => $e->getMessage())));
}
header('Content-type: text/json; charset=utf-8');
// Check if the buffer contained anything if it did ERROR!
if (trim($html) !== '') {
    $error = json_encode(array('error' => 'Errors were encountered while producing the course list',
        'data' => $html, 'user' => $USER->id));
    error_log($error);
    die($error);
}
// Output json.
echo json_encode($json);
