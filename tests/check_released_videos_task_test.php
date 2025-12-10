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

use local_oer\logger;
use oermod_opencast\task\check_released_videos_task;

/**
 * Test check_released_videos_task
 *
 * @coversDefaultClass  \oermod_opencast\task\check_released_videos_task
 */
final class check_released_videos_task_test extends \advanced_testcase {
    /**
     * Test set up.
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        require_once(__DIR__ . '/helper/testcourse.php');
        $this->resetAfterTest(true);
        $this->setAdminUser();
        set_config('enabledmodplugins', 'folder,resource,opencast', 'local_oer');
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
        parent::tearDown();
    }

    /**
     * Test get_name.
     *
     * @covers ::get_name
     *
     * @return void
     */
    public function test_get_name(): void {
        $task = new check_released_videos_task();
        $this->assertEquals(get_string('check_released_videos_task', 'oermod_opencast'), $task->get_name());
    }

    /**
     * Test inject_javascript_to_block_opencast function.
     *
     * The used functions are already tested in the other unit tests.
     * This runs through the task and checks if everything is called appropriately.
     *
     * @covers ::execute
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_execute(): void {
        /*
         * When using postgres database, the messages get buffered because the whole test environment runs inside
         * a database transaction. So we use the following function to tell Moodle not to use a transaction but
         * to clean up by deleting the new database entries. This test will be a bit slower then.
         */
         $this->preventResetByRollback();

        $testcourse = new \oermod_opencast\testcourse();
        $course = $testcourse->generate_testcourse_with_opencast_series($this->getDataGenerator());
        $identifier = $testcourse->generate_opencast_identifier('abcd-abcd-abcd-abcd');
        $testcourse->insert_to_snapshot_table($course->id, $identifier);
        $identifier = $testcourse->generate_opencast_identifier('abcd-abcd-abcd-abce');
        $testcourse->insert_to_snapshot_table($course->id, $identifier);
        $testcourse->set_json_response_for_testapi('api_events_acl_success.json', 'get');
        $testcourse->set_json_response_for_testapi('api_events_updateacl_success.json', 'put');
        $testcourse->set_json_response_for_testapi('api_workflows_updatemetadata_success.json', 'post');
        $testcourse->set_testapi();

        $this->run_execute($course->id);

        $testcourse->reset_json_response();
        api_helper::reset_api();
        $testcourse->set_json_response_for_testapi('api_events_acl_fail.json', 'get');
        $testcourse->set_testapi();

        $this->run_execute($course->id);

        $testcourse->reset_json_response();
        api_helper::reset_api();
        $testcourse->set_json_response_for_testapi('api_events_acl_success_w_anon.json', 'get');
        $testcourse->set_testapi();

        $this->run_execute($course->id);
    }

    /**
     * This function is used multiple times in the test_execute method.
     *
     * @param int $courseid
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    private function run_execute(int $courseid): void {
        $sink = $this->redirectEmails();
        $task = new check_released_videos_task();
        $task->execute();
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $messages);
        global $DB;
        $logs = $DB->get_records('local_oer_log', ['courseid' => $courseid, 'type' => logger::LOGSUCCESS]);
        $this->assertCount(1, $logs, 'One success log, does not change through different calls for this test.');
    }
}
