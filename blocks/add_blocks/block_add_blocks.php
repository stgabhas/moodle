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
 * @package    block_add_blocks
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_add_blocks extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_add_blocks');
    }

    public function instance_allow_multiple() {
        return false;
    }

    public function has_config() {
        return false;
    }

    public function instance_allow_config() {
        return true;
    }

    public function applicable_formats() {
        return array(
                'admin' => true,
                'site-index' => true,
                'course-view' => true,
                'mod' => true,
                'my' => true
        );
    }
    public function get_content() {
        global $PAGE, $OUTPUT;

        if (!$PAGE->user_is_editing() || !$PAGE->user_can_edit_blocks()) {
            return null;
        }

        if ($this->content !== null) {
            return $this->content;
        }
        $this->content = new stdClass();
        $this->content->text = '';

        $missingblocks = $PAGE->blocks->get_addable_blocks();
        if (empty($missingblocks)) {
            $this->content->text = get_string('noblockstoaddhere');
            return $this->content;
        }

        $menu = array();
        foreach ($missingblocks as $block) {
            $blockobject = block_instance($block->name);
            if ($blockobject !== false && $blockobject->user_can_addto($PAGE)) {
                $menu[$block->name] = $blockobject->get_title();
            }
        }
        core_collator::asort($menu);

        $actionurl = new moodle_url($PAGE->url, array('sesskey'=>sesskey()));
        $select = new single_select($actionurl, 'bui_addblock', $menu, null, array(''=>get_string('adddots')), 'add_block');
        $select->set_label(get_string('addblock'), array('class'=>'accesshide'));

        $this->content->text = $OUTPUT->render($select);

        return $this->content;
    }
}
