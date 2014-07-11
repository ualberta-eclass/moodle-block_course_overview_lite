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
 * This file is used to deliver a branch from the navigation structure
 * in XML format back to a page from an AJAX call
 *
 * @since 2.0
 * @package    block_course_overview_lite
 * @copyright  2014 Josh Stagg <jstagg@ualberta.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

/* Include config */
require_once(dirname(__FILE__) . '/../../config.php');
/* Include course lib for its functions */
require_once($CFG->dirroot.'/blocks/course_overview_lite/locallib.php');

require_sesskey();
require_login();

$edit = optional_param('edit', false, PARAM_BOOL);

global $USER;
try {
    // Start buffer capture so that we can remove any errors.
    ob_start();
    $json = array();
    if (confirm_sesskey()) {
        list($json, $totalcourses, $numhidden, $ajax) = block_course_overview_lite_get_sorted_courses(false);
    }
    // Stop buffering errors at this point.
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
