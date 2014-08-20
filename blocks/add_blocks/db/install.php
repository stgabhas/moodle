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
 * Add_blocks block installation.
 *
 * @package    block_add_blocks
 * @copyright  2014 onwards Daniel Neis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Add_blocks block installation.
 *
 * @package    block_add_blocks
 * @copyright  2014 onwards Daniel Neis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function xmldb_block_add_blocks_install() {

    $courses = get_courses("all", "c.sortorder ASC", "c.*");
    $blocknames = array(BLOCK_POS_LEFT => array('add_blocks'));
    foreach ($courses as $course) {
        $page = new moodle_page();
        $page->set_course($course);
        if (!$page->blocks->is_block_present('add_blocks')) {
            // We must always have the "add_blocks" block.
            if ($course->id == SITEID) {
                $pagetypepattern = 'site-index';
            } else {
                $pagetypepattern = 'course-view-*';
            }
            $page->blocks->add_blocks($blocknames, $pagetypepattern);
        }
    }
}
