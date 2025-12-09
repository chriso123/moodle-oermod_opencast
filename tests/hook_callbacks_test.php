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

/**
 * Test hook_callbacks_test
 *
 * @coversDefaultClass  \oermod_opencast\hook_callbacks
 */
class hook_callbacks_test extends \advanced_testcase {
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
        \oermod_opencast\api_helper::reset_api();
        $testcourse = new \oermod_opencast\testcourse();
        $testcourse->reset_json_response();
    }

    /**
     * Test inject_javascript_to_block_opencast function.
     *
     * This test creates a mock of the page_requirements_manager. This class tracks
     * all things a page require. Then we tell it that the oermod_opencast javascript
     * is expected.
     * So this test checks if the amd module is present in the page object.
     * As the page object properties are read-only, a reflection class is used and the
     * page_requirements_manager is replaced in page object with the mock.
     *
     * TODO: write also a behat test for this functionality.
     *
     * @covers ::inject_javascript_to_block_opencast
     *
     * @return void
     * @throws ReflectionException
     * @throws dml_exception
     */
    public function test_inject_javascript_to_block_opencast(): void {
        global $PAGE, $COURSE;
        $testcourse = new \oermod_opencast\testcourse();
        $course = $testcourse->generate_testcourse_with_opencast_series($this->getDataGenerator());
        $COURSE = $course;
        $identifer = $testcourse->generate_opencast_identifier('abcd-abcd-abcd-abcd-abcd');
        $testcourse->insert_to_snapshot_table($course->id, $identifer);

        $PAGE->set_url('/blocks/opencast/index.php', ['courseid' => $course->id, 'ocinstance' => 1]);
        $PAGE->set_pagelayout('incourse');

        $mockRequires = $this->createMock(\page_requirements_manager::class);
        $mockRequires->expects($this->once())
            ->method('js_call_amd')
            ->with('oermod_opencast/preventdelete-lazy', 'init');

        $reflection = new \ReflectionClass($PAGE);
        $property = $reflection->getProperty('_requires');
        $originalRequires = $property->getValue($PAGE);
        $property->setValue($PAGE, $mockRequires);
        \oermod_opencast\hook_callbacks::inject_javascript_to_block_opencast();
        $property->setValue($PAGE, $originalRequires);
    }
}