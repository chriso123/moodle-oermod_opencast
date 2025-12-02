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
 * @copyright  2023 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace oermod_opencast;

use local_oer\identifier;
use local_oer\modules\elements;
use local_oer\modules\element;
use local_oer\modules\person;
use tool_opencast\local\settings_api;

/**
 * Class module
 *
 * Implements the interface required to be used in local_oer plugin.
 */
class module implements \local_oer\modules\module {
    /**
     * Supported roles from opencast.
     */
    const ROLES = [
        // Creator, not interesting for OER.
        'Presenter',
        'Contributor',
        'Rightsholder',
    ];

    /**
     * Load all files from a given course.
     *
     * @param int $courseid Moodle courseid
     * @return elements
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function load_elements(int $courseid): \local_oer\modules\elements {
        // TODO: Implement behaviour for multiple instances.
        // This will also affect the write back function.
        $videos = api_helper::load_videos($courseid);
        $elements = new elements();
        if (empty($videos)) {
            return $elements;
        }
        $addpeople = get_config('oermod_opencast', 'addpeopleandroles');

        $settings = settings_api::get_default_ocinstance();
        $instance = settings_api::get_apiurl($settings->id);
        $creator = "oermod_opencast\module";
        foreach ($videos as $video) {
            if ($video->processing_state != 'SUCCEEDED' || empty($video->publications)) {
                // Only show working videos.
                // Possible states: INSTANTIATED, RUNNING, PAUSED, SUCCEEDED, FAILED, SKIPPED, RETRY.
                // TODO: when workflows are running, the videos are not returned.
                //       That leads to an error in the local_oer UI when a user updates the licence of the video.
                //       The video is not returned and the ajax call gets an error.
                continue;
            }
            $element = new element($creator, element::OERTYPE_EXTERNAL);
            $identifier = identifier::compose('opencast', $instance,
                'video', 'identifier', $video->identifier);
            $element->set_identifier($identifier);
            $element->set_origin('opencast', 'origin', 'oermod_opencast');
            $element->set_title($video->title);
            $license = api_helper::match_licence('opencast', $video->license);
            $element->set_license($license);
            if ($addpeople) {
                foreach ($video->presenter as $presenter) {
                    $pres = new person();
                    $pres->set_role(self::ROLES[1]);
                    $pres->set_fullname($presenter);
                    $element->add_person($pres);
                }
                foreach ($video->contributor as $contributor) {
                    $contrib = new person();
                    $contrib->set_role(self::ROLES[2]);
                    $contrib->set_fullname($contributor);
                    $element->add_person($contrib);
                }
                if (!empty($video->rightsholder)) {
                    $rolerightsholder = new person();
                    $rolerightsholder->set_role(self::ROLES[3]);
                    $rolerightsholder->set_fullname($video->rightsholder);
                    $element->add_person($rolerightsholder);
                }
            }

            $element->set_source($video->publications[0]->url);
            if (!empty($video->series)) {
                $element->add_information('series', 'oermod_opencast', $video->series, null, '');
            }
            $element->add_information('origin', 'local_oer',
                get_string('url', 'moodle'), null, '',
                $element->get_source());

            if (isset($video->publications[0]->media)) {
                $durations = [];
                foreach ($video->publications[0]->media as $media) {
                    $durations[$media->duration] = isset($durations[$media->duration]) ? $durations[$media->duration]++ : 0;
                }
                $milliseconds = empty($durations) ? [0 => 0] : array_keys($durations, max($durations));
                $milliseconds = reset($milliseconds);
                $duration = $milliseconds / 1000;
                $minutes = floor($duration / 60);
                $seconds = (int) $duration % 60;
                $result = $minutes > 0 ? $minutes . 'min' : '';
                $result .= $minutes > 0 && $seconds > 0 ? ' ' : '';
                $result .= $seconds > 0 ? $seconds . 's' : '';
                if (!empty($result)) { // Not every video has set correct length.
                    $element->add_information('duration', 'oermod_opencast', $result, 'duration', $milliseconds);
                }
            }
            $elements->add_element($element);
        }
        return $elements;
    }

    /**
     * Fields that can be written back from local_oer to the source.
     *
     * @return array[]
     */
    public function writable_fields(): array {
        return [
            ['license', 'moodle'],
        ];
    }

    /**
     * Write back the fields that are allowed to overwrite in the source.
     *
     * @param element $element
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function write_to_source(\local_oer\modules\element $element): void {
        api_helper::write_to_source($element->get_identifier(), $element->get_license());
    }

    /**
     * Match Opencast licences with Moodle active licences and return the result.
     *
     * @return array
     */
    public function supported_licences(): array {
        $licences = \license_manager::get_active_licenses_as_array();
        $result = [];
        foreach (api_helper::licence_mapping() as $moodle => $opencast) {
            if (isset($licences[$moodle])) {
                $result[] = $moodle;
            }
        }
        return $result;
    }

    /**
     * Return supported roles.
     *
     * @return array[]
     */
    public function supported_roles(): array {
        return [
            [self::ROLES[2], 'rightsholder', 'oermod_opencast', self::ROLE_REQUIRED],
            [self::ROLES[0], 'presenter', 'oermod_opencast'],
            [self::ROLES[1], 'contributor', 'oermod_opencast'],
        ];
    }

    /**
     * When an opencast video is released, the video has to be set to be publicly accessible.
     * Also, the video should not be deletable for lecturers.
     *
     * @param element $element
     * @return bool
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function set_element_to_release(\local_oer\modules\element $element): bool {
        return api_helper::set_element_to_release($element->get_identifier());
    }
}
