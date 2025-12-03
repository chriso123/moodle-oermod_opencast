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

require_once(__DIR__ . '/helper/testcourse.php');

use local_oer\logger;

/**
 * Test api_helper
 *
 * @coversDefaultClass  \oermod_opencast\api_helper
 */
class api_helper_test extends \advanced_testcase {
    /**
     * Test set up.
     *
     * @return void
     */
    public function setUp(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
    }

    /**
     * Tear down static caches.
     *
     * @return void
     */
    public function tearDown(): void {
        api_helper::reset_api();
        $testcourse = new testcourse();
        $testcourse->reset_json_response();
    }

    /**
     * Test load videos.
     *
     * @covers ::load_videos
     * @covers ::reset_api
     * @covers ::get_api
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_load_videos(): void {
        $testcourse = new testcourse();
        $course = $testcourse->generate_testcourse_with_opencast_series($this->getDataGenerator());
        $testcourse->add_series_to_course($course->id, '1234-1234-1234-1234-1235'); // Add second series to course.
        $testcourse->set_json_response_for_testapi('api_events_byseries_series_1.json', 'get');
        $testcourse->set_json_response_for_testapi('api_events_byseries_series_2.json', 'get');
        $testcourse->set_testapi();
        $result = api_helper::load_videos($course->id);
        $this->assertCount(16, $result);
        $this->assertEquals('abcd-abcd-abcd-abcd', $result[0]->identifier);
        $this->assertEquals('abcd-abcd-abcd-abce', $result[1]->identifier);
        $this->assertEquals('abcd-abcd-abcd-abcf', $result[2]->identifier);
        $this->assertEquals('abcd-abcd-abcd-abcg', $result[3]->identifier);
        $this->assertEquals('abcd-abcd-abcd-abch', $result[4]->identifier);
        $this->assertEquals('abcd-abcd-abcd-abci', $result[5]->identifier);
        $this->assertEquals('abcd-abcd-abcd-abcj', $result[6]->identifier);
        $this->assertEquals('abcd-abcd-abcd-abck', $result[7]->identifier);
        $this->assertEquals('abce-abcd-abcd-abcd', $result[8]->identifier);
        $this->assertEquals('abcf-abcd-abcd-abce', $result[9]->identifier);
        $this->assertEquals('abcg-abcd-abcd-abcf', $result[10]->identifier);
        $this->assertEquals('abch-abcd-abcd-abcg', $result[11]->identifier);
        $this->assertEquals('abci-abcd-abcd-abch', $result[12]->identifier);
        $this->assertEquals('abcj-abcd-abcd-abci', $result[13]->identifier);
        $this->assertEquals('abck-abcd-abcd-abcj', $result[14]->identifier);
        $this->assertEquals('abcl-abcd-abcd-abck', $result[15]->identifier);
    }

    /**
     * Test load_videos when series is not found.
     *
     * @covers ::load_videos
     * @covers ::reset_api
     * @covers ::get_api
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_load_videos_404(): void {
        $testcourse = new testcourse();
        $course = $testcourse->generate_testcourse_with_opencast_series($this->getDataGenerator());

        // Only series_1 is added to course, so the api will return a 404.
        $testcourse->set_json_response_for_testapi('api_events_byseries_series_2.json', 'get');
        $testcourse->set_testapi();
        $result = api_helper::load_videos($course->id);
        $this->assertEmpty($result);
        global $DB;
        $entries = $DB->get_records('local_oer_log', ['courseid' => $course->id]);
        $this->assertCount(1, $entries);
        $this->assertEquals($course->id, $entries[array_key_first($entries)]->courseid);
        $this->assertEquals(logger::LOGERROR, $entries[array_key_first($entries)]->type);
        $this->assertEquals('OERmod opencast: could not reach opencast server. Status Code:404',
            $entries[array_key_first($entries)]->message);
        $this->assertEquals('oermod_opencast', $entries[array_key_first($entries)]->component);
    }

    /**
     * Test load_videos when no videos are attached to a series.
     *
     * @covers ::load_videos
     * @covers ::reset_api
     * @covers ::get_api
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_load_videos_empty_series(): void {
        $testcourse = new testcourse();
        $course = $testcourse->generate_testcourse_with_opencast_series($this->getDataGenerator());

        $testcourse->set_json_response_for_testapi('api_events_byseries_series_1_empty.json', 'get');
        api_helper::reset_api();
        $testcourse->reset_json_response();
        $testcourse->set_json_response_for_testapi('api_events_byseries_series_1_empty.json', 'get');
        $testcourse->set_testapi();
        $result = api_helper::load_videos($course->id);
        $this->assertEmpty($result);
        global $DB;
        $entries = $DB->get_records('local_oer_log', ['courseid' => $course->id]);
        $this->assertEmpty($entries);
    }

    /**
     * Test write_to_source
     *
     * @covers ::write_to_source
     *
     * @return void
     */
    public function test_write_to_source(): void {
        // TODO.
    }

    /**
     * Test licence_mapping
     *
     * @covers ::licence_mapping
     *
     * @return void
     */
    public function test_license_mapping(): void {
        $mapping = api_helper::licence_mapping();
        $this->assertCount(9, $mapping);
        $this->assertArrayHasKey('unknown', $mapping);
        $this->assertArrayHasKey('allrightsreserved', $mapping);
        $this->assertArrayHasKey('public', $mapping);
        $this->assertArrayHasKey('cc-4.0', $mapping);
        $this->assertArrayHasKey('cc-nc-4.0', $mapping);
        $this->assertArrayHasKey('cc-nd-4.0', $mapping);
        $this->assertArrayHasKey('cc-nc-nd-4.0', $mapping);
        $this->assertArrayHasKey('cc-nc-sa-4.0', $mapping);
        $this->assertArrayHasKey('cc-sa-4.0', $mapping);
        $this->assertEquals('', $mapping['unknown']);
        $this->assertEquals('ALLRIGHTS', $mapping['allrightsreserved']);
        $this->assertEquals('CC0', $mapping['public']);
        $this->assertEquals('CC-BY', $mapping['cc-4.0']);
        $this->assertEquals('CC-BY-NC', $mapping['cc-nc-4.0']);
        $this->assertEquals('CC-BY-ND', $mapping['cc-nd-4.0']);
        $this->assertEquals('CC-BY-NC-ND', $mapping['cc-nc-nd-4.0']);
        $this->assertEquals('CC-BY-NC-SA', $mapping['cc-nc-sa-4.0']);
        $this->assertEquals('CC-BY-SA', $mapping['cc-sa-4.0']);
    }

    /**
     * Test match_licence function.
     *
     * @covers ::match_licence
     *
     * @return void
     */
    public function test_match_licence(): void {
        // TODO.
    }

    /**
     * Test remove_write_permission
     *
     * @covers ::remove_write_permission
     *
     * @return void
     */
    public function test_remove_write_permission(): void {
        // TODO.
    }

    /**
     * Test republish_metadata
     *
     * @covers ::republish_metadata
     *
     * @return void
     */
    public function test_republish_metadata(): void {
        // TODO.
    }

    /**
     * Test set_element_to_release function.
     *
     * @covers ::set_element_to_release
     *
     * @return void
     */
    public function test_set_element_to_release(): void {
        // TODO.
    }
}