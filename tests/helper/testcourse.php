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
 * Open Educational Resources Plugin
 *
 * @package    oermod_opencast
 * @author     Christian Ortner <christian.ortner@tugraz.at>
 * @copyright  2017-2022 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace oermod_opencast;

use local_oer\identifier;
use PHPUnit\Runner\Exception;
use stdClass;
use tool_opencast\local\api_testable;
use tool_opencast\local\settings_api;

require_once(__DIR__ . '/../../../../tests/helper/testcourse.php');

/**
 * Class testcourse
 *
 * Extend the testcourse class of local_oer.
 */
class testcourse extends \local_oer\testcourse {
    /**
     * Set the tool_opencast test api.
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function set_testapi(): void {
        $settings = settings_api::get_default_ocinstance();
        $testapi = new api_testable($settings->id);
        api_helper::get_api($testapi); // $testapi is stored in static variable.
    }

    /**
     * Create a default local_oer testcourse, but add a seriesid to tool_opencast.
     *
     * @param \testing_data_generator $generator
     * @return stdClass
     * @throws \dml_exception
     */
    public function generate_testcourse_with_opencast_series(\testing_data_generator $generator): stdClass {
        $course = self::generate_testcourse($generator);
        $this->add_series_to_course($course->id, '1234-1234-1234-1234-1234', 1);
        return $course;
    }

    /**
     * Add a series to a course.
     *
     * A series is stored in a tool_opencast table. The series id should match an identifier in a json api file.
     *
     * @param int $courseid Moodle course id.
     * @param string $series Identifier of series. Use a format like 1234-1234-1234-1234-1234.
     * @param int $default is this the default series. Only one series should get 1.
     * @return void
     * @throws \dml_exception
     */
    public function add_series_to_course(int $courseid, string $series, int $default = 0): void {
        global $DB;
        $settings = settings_api::get_default_ocinstance();
        $record = new stdClass();
        $record->courseid = $courseid;
        $record->series = $series;
        $record->isdefault = $default;
        $record->ocinstanceid = $settings->id;
        $DB->insert_record('tool_opencast_series', $record);
    }

    /**
     * Create identifier in opencast format.
     *
     * @param string $contenthash
     * @return string
     * @throws \coding_exception
     */
    public function generate_opencast_identifier(string $contenthash): string {
        global $CFG;
        return identifier::compose('opencast', $CFG->wwwroot, 'video', 'contenthash', $contenthash);
    }

    /**
     * Load prepared json files from the fixtures folder.
     *
     * @param string $filename Json file name in ../fixtures/api_calls folder.
     * @param string $method get | post | put | delete
     * @return void
     */
    public function set_json_response_for_testapi(string $filename, string $method): void {
        $path = __DIR__ . "/../fixtures/api_calls/$method/$filename";
        if (!file_exists($path)) {
            throw new Exception('opencast json file does not exist');
        }
        try {
            $apicall = file_get_contents($path);
            $apicall = json_decode($apicall);
            $method = strtoupper($method);
            $status = !empty($apicall->status) ? intval($apicall->status) : 200;
            $body = !empty($apicall->body) ? json_encode($apicall->body) : null;
            $params = !empty($apicall->params) ? json_encode($apicall->params) : '';
            $headers = !empty($apicall->headers) ? json_encode($apicall->headers) : [];
            api_testable::add_json_response($apicall->resource, $method, $status, $body, $params, $headers);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Reset the json response for further tests.
     *
     * @return void
     */
    public function reset_json_response(): void {
        set_config('api_testable_responses', '[]', 'tool_opencast'); // Clear if already something is set.
    }
}
