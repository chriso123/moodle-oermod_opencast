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
 * Test module_test
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
     */
    public function test_load_elements(): void {
        // TODO.
    }

    /**
     * Test writable_fields function.
     *
     * @covers ::writable_fields
     *
     * @return void
     */
    public function test_writable_fields(): void {
        // TODO.
    }

    /**
     * Test write_to_source function
     *
     * @covers ::write_to_source
     *
     * @return void
     */
    public function test_write_to_source(): void {
        // TODO.
    }

    /**
     * Test supported_licences function
     *
     * @covers ::supported_licences
     *
     * @return void
     */
    public function test_supported_licences(): void {
        // TODO.
    }

    /**
     * Test supported_roles function
     *
     * @covers ::supported_roles
     *
     * @return void
     */
    public function test_supported_roles(): void {
        // TODO.
    }

    /**
     * Test set_element_to_release function
     *
     * @covers ::set_element_to_release
     *
     * @return void
     */
    public function test_set_element_to_release(): void {
        // TODO.
    }
}