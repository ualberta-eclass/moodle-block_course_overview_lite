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
 * Course overview block
 *
 * @package    block_course_overview
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->dirroot.'/blocks/course_overview_lite/locallib.php');

/**
 * Course overview block
 *
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_course_overview_lite extends block_base {
    /**
     * Block initialization
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_course_overview_lite');
    }

    /**
     * Return contents of course_overview block
     *
     * @return stdClass contents of block
     */
    public function get_content() {
        global $USER, $CFG;
        require_once($CFG->dirroot.'/user/profile/lib.php');

        if ($this->content !== null) {
            return $this->content;
        }

        $config = get_config('block_course_overview_lite');

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        $updatemynumber = optional_param('mynumber', -1, PARAM_INT);
        if ($updatemynumber >= 0) {
            block_course_overview_lite_update_mynumber($updatemynumber);
        }

        list($sortedcourses, $totalcourses, $ajax) = block_course_overview_lite_get_sorted_courses();

        $renderer = $this->page->get_renderer('block_course_overview_lite');
        if (!empty($config->showwelcomearea) && !empty($config->welcomeareatext)) {
            $this->content->text = $renderer->welcome_area($config->welcomeareatext);
        }

        // Number of sites to display.
        if ($this->page->user_is_editing() && empty($config->forcedefaultmaxcourses)) {
            $this->content->text .= $renderer->editing_bar_head($totalcourses);
        }

        if (empty($sortedcourses) && !$ajax) {
            $this->content->text .= get_string('nocourses', 'my');
        } else {
            $this->content->text .= $renderer->course_overview($sortedcourses, $ajax);
            $this->content->text .= $renderer->hidden_courses($totalcourses - count($sortedcourses));
            $this->page->requires->js_init_call('M.block_course_overview_lite.init');
            if ($this->page->user_is_editing() && ajaxenabled()) {
                $this->page->requires->js_init_call('M.block_course_overview_lite.add_handles');
            }
        }
        return $this->content;
    }

    /**
     * Allow the block to have a configuration page
     *
     * @return boolean
     */
    public function has_config() {
        return true;
    }

    /**
     * Locations where block can be displayed
     *
     * @return array
     */
    public function applicable_formats() {
        return array('my-index' => true);
    }
}