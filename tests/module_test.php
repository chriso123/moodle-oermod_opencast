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

use local_oer\modules\element;
use local_oer\modules\elements;

require_once(__DIR__ . '/helper/testcourse.php');

/**
 * Test module_test
 *
 * The functions write_to_source and set_element_to_release are only wrapper functions.
 * The functionality is tested in api_helper_test. The functions are part of the interface
 * a subplugin of local_oer has to implement. But for better code readability API functions
 * have been separated from the module class in this subplugin.
 *
 * @coversDefaultClass  \oermod_opencast\module
 */
class module_test extends \advanced_testcase {
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
     * Test send_missing_videos function.
     *
     * @covers ::load_elements
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_load_elements(): void {
        set_config('addpeopleandroles', 1, 'oermod_opencast');

        $testcourse = new \oermod_opencast\testcourse();
        $course = $testcourse->generate_testcourse_with_opencast_series($this->getDataGenerator());

        $testcourse->set_json_response_for_testapi('api_events_byseries_series_1.json', 'get');
        $testcourse->set_testapi();

        $identifier1 = $testcourse->generate_opencast_identifier('abcd-abcd-abcd-abcd');
        $identifier2 = $testcourse->generate_opencast_identifier('abcd-abcd-abcd-abce');

        $module = new module();
        $elements = $module->load_elements($course->id);
        $this->assertCount(2, $elements);
        $this->assertEquals(elements::class, get_class($elements));

        $element1 = $elements->current();
        $elements->next();
        $element2 = $elements->current();

        $this->assertEquals(element::class, get_class($element1));
        $this->assertEquals($identifier1, $element1->get_identifier());
        $this->assertEquals('https://test-opencast.de/paella/ui/watch.html?id=1234-1234-1234-1234-1234', $element1->get_source());
        $info1 = $element1->get_information();
        $this->assertCount(3, $info1, 'Series, Origin and Duration are set.');
        foreach ($info1 as $info) {
            $found = false;
            if ($info->get_area() == 'Duration') {
                $found = true;
                $this->assertEquals('10s', $info->get_name());
                $this->assertEquals('10000', $info->get_raw_data());
            }
        }
        $this->assertTrue($found);

        $this->assertEquals(element::class, get_class($element2));
        $this->assertEquals($identifier2, $element2->get_identifier());
        $this->assertEquals('https://test-opencast.de/play/abcd-abcd-abcd-abce', $element2->get_source());
        $info2 = $element2->get_information();
        $this->assertCount(3, $info2, 'Series, Origin and Duration are set.');

        $testcourse->reset_json_response();
        api_helper::reset_api();
        $testcourse->set_json_response_for_testapi('api_events_byseries_series_1_empty.json', 'get');
        $testcourse->set_testapi();
        $elements = $module->load_elements($course->id);
        $this->assertEmpty($elements);
        $this->assertEquals(elements::class, get_class($elements));
    }

    /**
     * Test writable_fields function.
     *
     * @covers ::writable_fields
     *
     * @return void
     */
    public function test_writable_fields(): void {
        $module = new module();
        $fields = $module->writable_fields();
        $this->assertCount(1, $fields);
        $this->assertCount(2, $fields[0]);
        $this->assertEquals('license', $fields[0][0]);
        $this->assertEquals('moodle', $fields[0][1]);
    }

    /**
     * Test supported_licences function
     *
     * @covers ::supported_licences
     *
     * @return void
     * @throws \dml_exception
     */
    public function test_supported_licences(): void {
        global $DB;
        $licences = $DB->get_records('license', ['enabled' => 1], '', 'shortname');
        $module = new module();
        $supported = $module->supported_licences();
        foreach ($supported as $licence) {
            $this->assertArrayHasKey($licence, $licences);
        }
        $this->assertCount(count($licences), $supported);
    }

    /**
     * Test supported_roles function
     *
     * @covers ::supported_roles
     *
     * @return void
     */
    public function test_supported_roles(): void {
        $module = new module();
        $roles = $module->supported_roles();
        $this->assertCount(3, $roles);

        $this->assertCount(4, $roles[0]);
        $this->assertEquals(module::ROLES[2], $roles[0][0]);
        $this->assertEquals('rightsholder', $roles[0][1]);
        $this->assertEquals('oermod_opencast', $roles[0][2]);
        $this->assertEquals(module::ROLE_REQUIRED, $roles[0][3]);

        $this->assertCount(3, $roles[1]);
        $this->assertEquals(module::ROLES[0], $roles[1][0]);
        $this->assertEquals('presenter', $roles[1][1]);
        $this->assertEquals('oermod_opencast', $roles[1][2]);

        $this->assertCount(3, $roles[2]);
        $this->assertEquals(module::ROLES[1], $roles[2][0]);
        $this->assertEquals('contributor', $roles[2][1]);
        $this->assertEquals('oermod_opencast', $roles[2][2]);
    }
}