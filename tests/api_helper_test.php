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

use advanced_testcase;

/**
 * Test api_helper
 *
 * @coversDefaultClass  \oermod_opencast\api_helper
 */
class api_helper_test extends advanced_testcase {
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
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_load_videos() {
        $this->resetAfterTest(true);
        $this->setAdminUser();

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
}