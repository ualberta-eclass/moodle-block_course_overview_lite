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
 * course_overview block rendrer
 *
 * @package    block_course_overview_lite
 * @copyright  2012 Adam Olley <adam.olley@netspot.com.au>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

/**
 * Course_overview block rendrer
 *
 * @copyright  2012 Adam Olley <adam.olley@netspot.com.au>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_course_overview_lite_renderer extends plugin_renderer_base {

    /**
     * Construct contents of course_overview_lite block
     *
     * @param array $courses list of courses in sorted order
     * @return string html to be displayed in course_overview block
     */
    public function course_overview($courses, $ajax) {
        $editclass = $this->page->user_is_editing() ? 'ajax-edit' : '';
        $html = '';
        $html .= html_writer::start_tag('div', array('id' => 'course_list_header'));
        $html .= html_writer::tag('span', get_string('currentcourses', 'block_course_overview_lite'),
            array('id' => 'course_overview_lite_legend', 'class' => 'currentcourse'));
        $html .= html_writer::end_tag('div');

        $html .= html_writer::start_tag('div', array('id' => 'course_list', 'class' => $editclass));
        $courseordernumber = 0;
        $maxcourses = count($courses);
        // Intialize string/icon etc if user is editing.
        $url = null;
        $hideurl = null;
        $moveicon = null;
        $hideicon = null;
        $showicon = null;
        $moveup[] = null;
        $movedown[] = null;

        if ($ajax && empty($courses)) {
            $html .= html_writer::tag('div', html_writer::empty_tag('img',
                array('src' => $this->pix_url('i/loading')->out(false))
            ), array('id' => 'ajaxcourse', 'class' => $editclass));
        } else {
            if ($this->page->user_is_editing()) {
                if (ajaxenabled()) {
                    $moveicon = html_writer::tag('div',
                        html_writer::empty_tag('img',
                            array('src' => $this->pix_url('i/move_2d')->out(false),
                                'alt' => get_string('move'), 'class' => 'cursor',
                                'title' => get_string('move'))
                        ), array('class' => 'move')
                    );
                } else {
                    $url = new moodle_url('/blocks/course_overview_lite/move.php', array('sesskey' => sesskey()));
                    $hideurl = new moodle_url('/blocks/course_overview_lite/hide.php', array('sesskey' => sesskey()));
                    $moveup['str'] = get_string('moveup');
                    $moveup['icon'] = $this->pix_url('t/up');
                    $movedown['str'] = get_string('movedown');
                    $movedown['icon'] = $this->pix_url('t/down');
                }
                $hideicon = html_writer::empty_tag('img',
                    array('src' => $this->pix_url('i/hide')->out(false),
                        'alt' => get_string('hide_icon_alt',
                            'block_course_overview_lite'),
                        'class' => 'hide_icon',
                        'align' => 'right',
                        'title' => get_string('hide_icon_alt',
                            'block_course_overview_lite')
                    )
                );
                $showicon = html_writer::empty_tag('img',
                    array('src' => $this->pix_url('i/show')->out(false),
                        'alt' => get_string('hide_icon_alt',
                            'block_course_overview_lite'),
                        'class' => 'hide_icon',
                        'align' => 'right',
                        'title' => get_string('hide_icon_alt',
                            'block_course_overview_lite')
                    )
                );
            }
        }

        foreach ($courses as $key => $course) {
            // If the course is hidden, set its class to 'dimmed'.
            if ($course->userhidden) {
                if (!$this->page->user_is_editing()) {
                    continue;
                }
                $classvisibility = " userhidden";
            } else {
                $classvisibility = "";
            }
            $class = $course->current ? 'coursebox currentcourse' : 'coursebox';
            $class .= $classvisibility;
            $html .= $this->output->box_start($class, "course-{$course->id}");
            $html .= html_writer::start_tag('div', array('class' => 'course_title'));

            // Ajax enabled then add moveicon html.
            if (!is_null($moveicon)) {
                $html .= $moveicon;
            } else if (!is_null($url)) {
                // Add course id to move link.
                $url->param('source', $course->id);
                $html .= html_writer::start_tag('div', array('class' => 'moveicons'));
                // Add an arrow to move course up.
                if ($courseordernumber > 0) {
                    $url->param('move', -1);
                    $html .= html_writer::link($url,
                    html_writer::empty_tag('img', array('src' => $moveup['icon'],
                        'class' => 'up', 'alt' => $moveup['str'])),
                        array('title' => $moveup['str'], 'class' => 'moveup'));
                } else {
                    // Add a spacer to keep keep down arrow icons at right position.
                    $html .= html_writer::empty_tag('img', array('src' => $this->pix_url('spacer'),
                        'class' => 'movedownspacer'));
                }
                // Add an arrow to move course down.
                if ($courseordernumber <= $maxcourses - 2) {
                    $url->param('move', 1);
                    $html .= html_writer::link($url, html_writer::empty_tag('img',
                        array('src' => $movedown['icon'], 'class' => 'down', 'alt' => $movedown['str'])),
                        array('title' => $movedown['str'], 'class' => 'movedown'));
                } else {
                    // Add a spacer to keep keep up arrow icons at right position.
                    $html .= html_writer::empty_tag('img', array('src' => $this->pix_url('spacer'),
                        'class' => 'moveupspacer'));
                }
                $html .= html_writer::end_tag('div');
            }

            // Add hide icon to each course..
            if ($this->page->user_is_editing()) {
                if ($course->userhidden) {
                    $icon = $showicon;
                } else {
                    $icon = $hideicon;
                }
                if (!is_null($icon)) {
                    $html .= html_writer::start_tag('div', array("class" => "hide_course", "id" => $course->id));
                    if (is_null($hideurl)) {
                        $html .= $icon;
                    } else {
                        $hideurl->param('toggle_hidden', $course->id);
                        $html .= html_writer::link($hideurl, $icon);
                    }
                    $html .= html_writer::end_tag('div');
                }
            }

            $attributes = array('title' => s($course->fullname));
            if ($course->id > 0) {
                $coursefullname = format_string($course->fullname, true, $course->id);
                $course->hidden ? $attributes['class'] = 'dimmed_text' : false;
                $link = html_writer::link($course->url, $coursefullname, $attributes);
                $html .= $this->output->heading($link, 3, 'title');
            }

            $html .= $this->output->box('', 'flush');
            $html .= html_writer::end_tag('div');

            $html .= $this->output->box('', 'flush');
            $html .= $this->output->box_end();
            $courseordernumber++;
        }

        $html .= html_writer::end_tag('div');

        return $html;
    }

    /**
     * Constructs header in editing mode
     *
     * @param int $max maximum number of courses
     * @return string html of header bar.
     */
    public function editing_bar_head($max = 0) {
        $output = $this->output->box_start('notice');

        $options = array('0' => get_string('alwaysshowall', 'block_course_overview_lite'));
        for ($i = 1; $i <= $max; $i++) {
            $options[$i] = $i;
        }
        $url = new moodle_url('/my/index.php');
        $select = new single_select($url, 'mynumber', $options, block_course_overview_lite_get_max_user_courses(), array());
        $select->set_label(get_string('numtodisplay', 'block_course_overview_lite'));
        $output .= $this->output->render($select);

        $output .= $this->output->box_end();
        return $output;
    }

    /**
     * Show hidden courses count
     *
     * @param int $total count of hidden courses
     * @return string html
     */
    public function hidden_courses($total) {
        if ($total <= 0) {
            return;
        }
        $output = $this->output->box_start('notice');
        $plural = $total > 1 ? 'plural' : '';
        $output .= get_string('hiddencoursecount'.$plural, 'block_course_overview_lite', $total);
        $output .= $this->output->box_end();
        return $output;
    }

    /**
     * Cretes html for welcome area
     *
     * @param int $msgcount number of messages
     * @return string html string for welcome area.
     */
    public function welcome_area($msg) {
        $output = $this->output->box_start('welcome_area');
        $output .= html_writer::tag('div', $msg);
        $output .= $this->output->box('', 'flush');
        $output .= $this->output->box_end();
        return $output;
    }
}
