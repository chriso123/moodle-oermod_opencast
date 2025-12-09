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

namespace oermod_opencast;

use local_oer\identifier;
use local_oer\logger;
use tool_opencast\local\api;
use tool_opencast\local\api_testable;
use tool_opencast\local\settings_api;

/**
 * Class api_helper
 *
 * Contains all functions that use the opencast api.
 */
class api_helper {
    /**
     * Cached opencast api object for request.
     *
     * @var api|api_testable|null
     */
    private static api|api_testable|null $api = null;

    /**
     * Get the tool_opencast api.
     *
     * @param api_testable|null $api Inject tool_opencast api_testable for unit testing.
     * @return api|api_testable
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function get_api(?api_testable $api = null): api|api_testable {
        if ($api !== null) {
            self::$api = $api;
        } else if (self::$api == null) {
            $settings = settings_api::get_default_ocinstance();
            self::$api = new api($settings->id);
        }
        return self::$api;
    }

    /**
     * Resets the cached API.
     *
     * Use only in unit tests to reset static cache between tests.
     *
     * @return void
     */
    public static function reset_api(): void {
        self::$api = null;
    }

    /**
     * Use the opencast API to load all videos for a Moodle course.
     *
     * @param int $courseid Moodle course id.
     * @return array
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function load_videos(int $courseid): array {
        // A course can have more than one series.
        global $DB;
        $list = $DB->get_records('tool_opencast_series', ['courseid' => $courseid]);
        $videos = [];
        foreach ($list as $series) {
            $params = [
                'sign' => false,
                'withacl' => false,
                'withmetadata' => false,
                'withpublications' => true,
                'sort' => [
                    'start_date' => 'DESC',
                ],
            ];

            $api = self::get_api();
            $response = $api->opencastapi->eventsApi->getBySeries($series->series, $params);
            $code = $response['code'];

            if ($code != 200) {
                logger::add(
                    $courseid,
                    logger::LOGERROR,
                    "OERmod opencast: could not reach opencast server. Status Code:$code",
                    'oermod_opencast'
                );
                continue;
            }
            if (empty($response['body'])) {
                continue;
            }
            $videos = array_merge($videos, $response['body']);
        }
        return $videos;
    }

    /**
     * Write back the fields that are allowed to overwrite in the source.
     *
     * @param string $identifier Identifier of OER element.
     * @param string $moodlelicence Licence as defined in Moodle
     * @return bool
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function write_to_source(string $identifier, string $moodlelicence): bool {
        $decompose = identifier::decompose($identifier);
        if ($decompose->platform != 'opencast' || $decompose->type != 'video' || $decompose->valuetype != 'identifier') {
            return false;
        }
        if ($moodlelicence == 'unknown') {
            return false; // Do not update unknown licence.
        }

        $licence = self::match_licence('moodle', $moodlelicence);
        $update = [
            'id' => 'license',
            'value' => $licence,
        ];
        $metadata = json_encode([$update]);
        $type = 'dublincore/episode';

        $api = self::get_api();
        $response = $api->opencastapi->eventsApi->updateMetadata($decompose->value, $type, $metadata);
        $success = self::republish_metadata($api, $decompose->value, $response['code']);
        if (!$success) {
            global $DB;
            $courseid = $DB->get_field('local_oer_elements', 'courseid', ['identifier' => $identifier]);
            logger::add(
                $courseid,
                logger::LOGERROR,
                'Workflow could not be started, so licence not visible: ' . $identifier,
                'oermod_opencast'
            );
        }
        return $success;
    }

    /**
     * Map the Moodle licences to its Opencast counterpart.
     *
     * The shortnames of the licences will be matched, not the visible names.
     * Only the base licences are matched here.
     *
     * TODO: should this be more dynamic for custom licences?
     * TODO: what happens if some licences are deactivated in opencast?
     *
     * @return array
     */
    public static function licence_mapping(): array {
        return [
            'unknown' => '', // Empty string in Opencast, also all other licences not matchable.
            'allrightsreserved' => 'ALLRIGHTS',
            'public' => 'CC0',
            'cc-4.0' => 'CC-BY',
            'cc-nc-4.0' => 'CC-BY-NC',
            'cc-nd-4.0' => 'CC-BY-ND',
            'cc-nc-nd-4.0' => 'CC-BY-NC-ND',
            'cc-nc-sa-4.0' => 'CC-BY-NC-SA',
            'cc-sa-4.0' => 'CC-BY-SA',
        ];
    }

    /**
     * Match a given licence to its counterpart.
     *
     * @param string $source The source can be either 'moodle' or 'opencast'.
     * @param string $licence Licence shortname string.
     * @return string
     * @throws \Exception
     */
    public static function match_licence(string $source, string $licence): string {
        if (!in_array($source, ['moodle', 'opencast'])) {
            throw new \Exception('Wrong source given, only "moodle" or "opencast" are allowed.');
        }

        foreach (self::licence_mapping() as $moodle => $opencast) {
            if ($source == 'moodle' && $moodle == $licence) {
                return $opencast;
            }
            if ($source == 'opencast' && $opencast == $licence) {
                return $moodle;
            }
        }
        return $source == 'moodle' ? '' : 'unknown';
    }

    /**
     * Remove the write flag from an ACL role.
     *
     * @param \stdClass $role
     * @param string $key
     * @param array $aclsettings
     * @return bool
     */
    public static function remove_write_permission(\stdClass $role, string $key, array &$aclsettings): bool {
        if (!isset($aclsettings[$key])) {
            return false;
        }
        if ($role->action == 'write') {
            $aclsettings[$key]->allow = false;
            return true;
        }
        return false;
    }

    /**
     * After something has been written back to opencast, the video has to run a workflow so that the changes are visible.
     *
     * @param api $api tool_opencast api
     * @param string $videoid Opencast video id
     * @param int $code Http response code
     * @return bool
     */
    public static function republish_metadata(api $api, string $videoid, int $code) {
        if ($code == 204) {
            // Workflow to republish metadata needs to be triggered.
            $workflow = $api->opencastapi->workflowsApi->run(
                $videoid,
                'republish-metadata',
                [],
                false,
                false
            );
            if ($workflow && $workflow['code'] == 201) {
                return true;
            }
        }
        return false;
    }

    /**
     * When an opencast video is released, the video has to be set to be publicly accessible.
     * Also, the video should not be deletable for lecturers.
     *
     * @param string $identifier
     * @return bool
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function set_element_to_release(string $identifier): bool {
        $decompose = identifier::decompose($identifier);
        $api = self::get_api();
        $response = $api->opencastapi->eventsApi->getAcl($decompose->value);
        global $DB;
        $courseid = $DB->get_field('local_oer_snapshot', 'courseid', ['identifier' => $identifier]);
        if (empty($response) || $response['code'] != 200 || $response['reason'] != 'OK') {
            // Webservice call did not succeed.
            // TODO: maybe this should be retried later? Add an adhoc task for this?
            logger::add($courseid, logger::LOGERROR, 'Could not set element to release: ' . $identifier, 'oermod_opencast');
            return false;
        }
        $anonymousrole = "ROLE_ANONYMOUS";
        $update = false;
        $found = false;

        // All entries have to be returned, else they will be deleted.
        // Test if anonymous is already in the list, if true, test allow and action.
        // If false, add it to the list.
        $removewrite = get_config('oermod_opencast', 'rolestoremovewrite');
        $removewrite = str_replace('{{courseid}}', $courseid, $removewrite);
        $list = explode("\r\n", $removewrite);
        $aclsettings = $response['body'];
        foreach ($aclsettings as $key => $role) {
            switch ($role->role) {
                case $anonymousrole:
                    $result = self::remove_write_permission($role, $key, $aclsettings);
                    $update = $update ?: $result;
                    if (!$update) {
                        $found = true;
                    }
                    break;
                default:
            }
            if (in_array($role->role, $list)) {
                $result = self::remove_write_permission($role, $key, $aclsettings);
                $update = $update ?: $result;
            }
        }

        // If the setting has not been found, add it and trigger update.
        if (!$found) {
            $update = true;
            $acl = new \stdClass();
            $acl->allow = true;
            $acl->role = $anonymousrole;
            $acl->action = "read";
            $aclsettings[] = $acl;
        }

        if ($update) {
            $response = $api->opencastapi->eventsApi->updateAcl($decompose->value, $aclsettings);
            return self::republish_metadata($api, $decompose->value, $response['code']);
        }
        return true; // No update necessary, all good.
    }
}
