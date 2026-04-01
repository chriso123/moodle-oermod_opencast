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
 * OER subplugin for loading opencast videos
 *
 * @package    oermod_opencast
 * @author     Christian Ortner <christian.ortner@tugraz.at>
 * @copyright  2024 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace oermod_opencast;

/**
 * Class hook_callbacks
 */
class hook_callbacks {
    /**
     * Inject JavaScript to the block_opencast overview page.
     *
     * JavaScript will scan for released videos and remove the edit/delete buttons from the table.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function inject_javascript_to_block_opencast() {
        global $PAGE;
        if ($PAGE->has_set_url() && preg_match('/\/blocks\/opencast\/index.php/', $PAGE->url->out())) {
            global $COURSE, $DB;
            $released = $DB->get_records('local_oer_snapshot', ['courseid' => $COURSE->id]);
            $series = $DB->get_records('tool_opencast_series', ['courseid' => $COURSE->id]);

            $data = [];
            foreach ($series as $entry) {
                $data['series'][] = $entry->series;
            }
            foreach ($released as $record) {
                $identifier = \local_oer\identifier::decompose($record->identifier);
                if ($identifier->platform == 'opencast' && $identifier->type == 'video') {
                    $data['videos'][$record->identifier] = [
                        'videoid' => $identifier->value,
                        'title' => $record->title,
                    ];
                }
            }
            $PAGE->requires->js_call_amd('oermod_opencast/preventdelete-lazy', 'init', ['released' => $data]);
        }
    }

    /**
     * Add ACL parameters to the opencast studio link
     *
     * @return void
     * @throws \dml_exception
     */
    public static function extend_opencast_studio_url_parameters() {
        global $PAGE;
        if (
            $PAGE->has_set_url() &&
            (preg_match('/\/blocks\/opencast\/index.php/', $PAGE->url->out()) ||
                preg_match('/\/course\/view.php/', $PAGE->url->out())) &&
            get_config('block_opencast', 'enable_opencast_studio_link_1') == 1
        ) {
            $courseid = $PAGE->course->id;
            $roles = json_decode(get_config('block_opencast', 'roles_1'));
            $acl = [];
            foreach ($roles as $role) {
                if (str_contains($role->actions, 'write')) {
                    $rolename = str_replace('[COURSEID]', $courseid, $role->rolename);
                    $acl[$rolename] = ['read', 'write'];
                }
            }
            $params = [
                'upload.acl' => json_encode($acl),
            ];
            $PAGE->requires->js_call_amd('oermod_opencast/extend_opencast_studio-lazy', 'init', [$params]);
        }
    }
}
