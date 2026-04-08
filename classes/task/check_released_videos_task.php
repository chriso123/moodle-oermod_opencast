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
use local_oer\logger;
use oermod_opencast\api_helper;
use oermod_opencast\message;

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
        $sql = "SELECT s.id, s.identifier, s.courseid, s.title, s.releasenumber
          FROM {local_oer_snapshot} s
          JOIN (
              SELECT identifier, MAX(releasenumber) as maxrel
                FROM {local_oer_snapshot}
               WHERE identifier LIKE ?
            GROUP BY identifier
          ) m ON s.identifier = m.identifier AND s.releasenumber = m.maxrel
          ORDER BY s.releasenumber DESC";
        $released = $DB->get_records_sql($sql, ['oer:opencast@%']);
        cli_writeln(count($released) . ' release snapshots for opencast videos will be checked.');
        $notfound = []; // 404 Error occured.
        $found = []; // Found video in webservice response.
        $errors = []; // Any other error than 404.

        $api = api_helper::get_api();

        // Step 2: Check if something needs to be done.
        foreach ($released as $snapshot) {
            $decompose = identifier::decompose($snapshot->identifier);
            $response = $api->opencastapi->eventsApi->getAcl($decompose->value);
            switch ($response['code']) {
                case 200:
                    $found[$snapshot->identifier] = [
                        'snapshot' => $snapshot,
                        'response' => $response,
                        'licence' => api_helper::get_moodle_licence_of_video($snapshot->identifier),
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

        // Step 3: Check if videos are still publicly available and teachers cannot delete them.
        $tofix = [];
        $wronglicence = [];
        $roles = get_config('oermod_opencast', 'rolestoremovewrite');
        $roles = str_replace('{{courseid}}', '', $roles); // Remove placeholder for comparison.
        $roles = explode("\r\n", $roles);
        foreach ($found as $snapshot) {
            $anonymous = false;
            $canwrite = false;
            if (empty($snapshot['licence']) || (!str_contains($snapshot['licence'], 'cc') && $snapshot['licence'] !== 'public')) {
                $wronglicence[$snapshot['snapshot']->identifier] = $snapshot;
            }
            foreach ($snapshot['response']['body'] as $permission) {
                if ($permission->role == 'ROLE_ANONYMOUS') {
                    $anonymous = true;
                }
                // When a video is released, all courses where the video is linked should lose writing capability.
                // So we check for every role from the oermod_opencast | rolestoremovewrite setting.
                foreach ($roles as $role) {
                    if (str_contains($permission->role, $role) &&
                        $permission->action == 'write' &&
                        $permission->allow
                    ) {
                        $canwrite = true;
                    }
                }
            }
            if (!$anonymous || $canwrite) {
                $tofix[$snapshot['snapshot']->identifier] = $snapshot;
            }
        }

        cli_writeln('------------');
        cli_writeln(count($tofix) . ' Videos have wrong ACL settings.');
        cli_writeln(count($wronglicence) . ' Videos have wrong licence set.');
        cli_writeln(count($notfound) . ' Videos are missing.');
        cli_writeln(count($errors) . ' Videos have other errors.');
        cli_writeln('Emails will be sent.');
        cli_writeln('------------');

        // Step 5: If there are any videos missing send notifications.
        message::send_missingvideos($tofix, $wronglicence, $notfound, $errors);
    }
}
