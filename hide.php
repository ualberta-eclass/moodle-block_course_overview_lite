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
 * Hide/show course functionality for eclass_course_overview block.
 *
 * @package    block_eclass_course_overview
 * @copyright  2013 Dom Royko <royko@ualberta.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

require_sesskey();
require_login();

$togglecourse = required_param('toggle_hidden', PARAM_INT);

$hiddencourses = block_course_overview_lite_get_courses_hidden();

if (array_key_exists($togglecourse, $hiddencourses) && ($hiddencourses[$togglecourse] == true)) {
    unset($hiddencourses[$togglecourse]);
} else {
    $hiddencourses[$togglecourse] = true;
}

block_course_overview_lite_update_courses_hidden($hiddencourses);

redirect(new moodle_url('/my/index.php'));
