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
 * @copyright  2025 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace oermod_opencast\task;

defined('MOODLE_INTERNAL') || die();

use core\task\scheduled_task;
use local_oer\identifier;
use oermod_opencast\message;
use tool_opencast\local\api;
use tool_opencast\local\settings_api;

require_once($CFG->libdir . '/clilib.php');

/**
 * Class upload_task
 */
class check_released_videos_task extends scheduled_task {
    /**
     * Get name
     *
     * @return \lang_string|string
     * @throws \coding_exception
     */
    public function get_name() {
        return get_string('check_released_videos_task', 'oermod_opencast');
    }

    /**
     * Execute task
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function execute() {
        global $DB;
        // Step 1: Get all released videos.
        $sql = 'SELECT * FROM {local_oer_snapshot} WHERE identifier LIKE ?';
        $released = $DB->get_records_sql($sql, ['oer:opencast@%']);
        cli_writeln('Found ' . count($released) . ' release snapshots for opencast videos.');
        $notfound = [];
        $found = [];
        $errors = [];

        // Step 2: Check if something needs to be done.
        foreach ($released as $snapshot) {
            $decompose = identifier::decompose($snapshot->identifier);
            $settings = settings_api::get_default_ocinstance();
            $api = new api($settings->id);
            $response = $api->opencastapi->eventsApi->getAcl($decompose->value);
            switch ($response['code']) {
                case 200:
                    $found[$snapshot->identifier] = [
                        'snapshot' => $snapshot,
                        'response' => $response,
                    ];
                    break;
                case 404:
                    $notfound[$snapshot->identifier] = [
                        'snapshot' => $snapshot,
                        'response' => $response,
                    ];
                    break;
                default:
                    $errors[$snapshot->identifier] = [
                        'snapshot' => $snapshot,
                        'response' => $response,
                    ];
            }
        }

        // Step 3: Fix
        foreach ($found as $snapshot) {

        }

        // Step 4: Set videos to public and remove write permissions for teachers.
        // TODO: output steps, statistic and identifiers.

        // Step 5: If there are any videos missing send notifications.
        message::send_missingvideos($notfound, $errors);
    }
}
