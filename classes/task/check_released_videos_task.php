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
        $sql = 'SELECT identifier, courseid, title, releasenumber FROM {local_oer_snapshot} ' .
            'WHERE identifier LIKE ? GROUP BY identifier ORDER BY releasenumber DESC';
        $released = $DB->get_records_sql($sql, ['oer:opencast@%']);
        cli_writeln(count($released) . ' release snapshots for opencast videos will be checked.');
        $notfound = []; // 404 Error occured.
        $found = []; // Found video in webservice response.
        $errors = []; // Any other error than 404.
        $failed = []; // Found the video, but the update failed.

        $api = api_helper::get_api();

        // Step 2: Check if something needs to be done.
        foreach ($released as $snapshot) {
            $decompose = identifier::decompose($snapshot->identifier);
            $response = $api->opencastapi->eventsApi->getAcl($decompose->value);
            switch ($response['code']) {
                case 200:
                    $metadata = $api->opencastapi->eventsApi->getMetadata($decompose->value, api_helper::METADATATYPE);
                    $fixdescription = false;
                    if ($metadata && $metadata['code'] == 200) {
                        foreach ($metadata['body'] as $field) {
                            if ($field->id == 'description' && !str_contains($field->value, api_helper::OERPUBLISHED)) {
                                $fixdescription = true;
                            }
                        }
                    }
                    $found[$snapshot->identifier] = [
                        'snapshot' => $snapshot,
                        'response' => $response,
                        'fixdescription' => $fixdescription,
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
        cli_writeln('------------');
        cli_writeln(count($found) . ' Videos will be checked for their permissions.');
        cli_writeln((count($errors) + count($notfound)) . ' Videos are missing or have errors.' .
            ((count($errors) + count($notfound)) > 0 ? ' Emails will be sent if necessary.' : ''));
        cli_writeln('------------');

        // Step 3: Check if videos are still publicly available and teachers cannot delete them.
        $tofix = [];
        foreach ($found as $snapshot) {
            $anonymous = false;
            $canwrite = false;
            foreach ($snapshot['response']['body'] as $permission) {
                if ($permission->role == 'ROLE_ANONYMOUS') {
                    $anonymous = true;
                }
                // When a video is released, all courses where the video is linked should lose writing capability.
                // So we check for every $courseid_instructor that is set.
                if (
                    str_contains($permission->role, '_Instructor') && $permission->action == 'write' &&
                    $permission->allow
                ) {
                    $canwrite = true;
                }
            }
            if (!$anonymous || $canwrite || $snapshot['fixdescription']) {
                $tofix[$snapshot['snapshot']->identifier] = $snapshot;
            }
        }

        // Step 4: Set videos to public and remove write permissions for teachers.
        foreach ($tofix as $snapshot) {
            cli_writeln('Fix permissions for: ' . $snapshot['snapshot']->identifier . ' (' . $snapshot['snapshot']->title . ')');
            $success = api_helper::set_element_to_release($snapshot['snapshot']->identifier);
            if ($success) {
                logger::add(
                    $snapshot['snapshot']->courseid,
                    $success ? logger::LOGSUCCESS : logger::LOGERROR,
                    $success ? 'Fixed permissions for: ' . $snapshot['snapshot']->identifier
                        : 'Error fixing permissions for: ' . $snapshot['snapshot']->identifier
                );
            } else {
                // Logger already triggered in set_element_to_release.
                $failed[$snapshot['snapshot']->identifier] = $snapshot;
            }
        }

        // Step 5: If there are any videos missing send notifications.
        message::send_missingvideos($notfound, $errors, $failed);
    }
}
