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

/**
 * Test message_test
 *
 * @coversDefaultClass  \oermod_opencast\message
 */
final class message_test extends \advanced_testcase {
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
     * Test send_missing_videos function.
     *
     * @covers ::send_missingvideos
     * @covers ::get_users
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_send_missing_videos(): void {
        $testcourse = new \oermod_opencast\testcourse();
        $course = $testcourse->generate_testcourse_with_opencast_series($this->getDataGenerator());

        // Create some users to check if they get messages too.
        $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->create_user();
        global $DB;
        $manager = $DB->get_record('role', ['shortname' => 'manager']);
        $context = \context_system::instance();
        role_assign($manager->id, $user4->id, $context);

        // Case 1: empty missing and error arrays.
        $sink = $this->redirectEmails();
        \oermod_opencast\message::send_missingvideos([], []);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(0, $messages, 'Empty arrays, so no messages send.');

        // Case 2: missing array has an entry.
        $sink = $this->redirectEmails();
        $video = new \stdClass();
        $video->courseid = $course->id;
        $video->identifier = $testcourse->generate_opencast_identifier('12345');
        $video->title = 'Unit test';
        $missing = [
            [
                'snapshot' => $video,
            ],
        ];
        \oermod_opencast\message::send_missingvideos($missing, []);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('admin', $messages[0]->to);
        $this->assertStringContainsString(get_string('message:missingvideos', 'oermod_opencast'), $messages[0]->subject);
        $message = $messages[0]->body;
        $message = str_replace("\r\n", ' ', $message); // Remove linebreaks to compare get_string.
        $this->assertStringContainsString(get_string('message:missingvideos_body', 'oermod_opencast'), $message);
        $this->assertStringContainsString('* CourseID: ' . $course->id, $message);
        $this->assertStringContainsString($video->identifier, $message);

        // The messages have courseid 0, as they do not belong to a specific course.
        $logs = $DB->get_records('local_oer_log', ['courseid' => 0, 'type' => logger::LOGERROR]);
        $this->assertCount(1, $logs);
        $this->assertEquals('1 missing videos. Notification has been sent to admins.', $logs[array_key_first($logs)]->message);
        $this->assertEquals('oermod_opencast', $logs[array_key_first($logs)]->component);

        // Case 3: error array has an entry.
        $sink = $this->redirectEmails();
        $errors = [
            [
                'snapshot' => $video,
                'response' => [
                    'code' => 401,
                    'reason' => 'unauthorized',
                ],
            ],
        ];
        \oermod_opencast\message::send_missingvideos([], $errors);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $messages);
        $message = $messages[0]->body;
        $message = str_replace("\r\n", ' ', $message); // Remove linebreaks to compare get_string.
        $this->assertStringContainsString(get_string('message:errors', 'oermod_opencast'), $message);
        $this->assertStringContainsString('* CourseID: ' . $course->id, $message);
        $this->assertStringContainsString($video->identifier, $message);

        $logs = $DB->get_records('local_oer_log', ['courseid' => 0, 'type' => logger::LOGERROR]);
        $this->assertCount(2, $logs, 'missing and error');
        $this->assertEquals('1 errors with videos. Notification has been sent to admins.', $logs[array_key_last($logs)]->message);
        $this->assertEquals('oermod_opencast', $logs[array_key_last($logs)]->component);

        // Case 4: both arrays have entries.
        $sink = $this->redirectEmails();
        \oermod_opencast\message::send_missingvideos($missing, $errors);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('admin', $messages[0]->to);
        $this->assertStringContainsString(get_string('message:missingvideos', 'oermod_opencast'), $messages[0]->subject);
        $message = $messages[0]->body;
        $message = str_replace("\r\n", ' ', $message); // Remove linebreaks to compare get_string.
        $this->assertStringContainsString(get_string('message:errors', 'oermod_opencast'), $message);
        $this->assertStringContainsString('* CourseID: ' . $course->id, $message);
        $this->assertStringContainsString($video->identifier, $message);
        $this->assertStringContainsString(get_string('message:missingvideos_body', 'oermod_opencast'), $message);
        $this->assertStringContainsString('* CourseID: ' . $course->id, $message);
        $this->assertStringContainsString($video->identifier, $message);

        $logs = $DB->get_records('local_oer_log', ['courseid' => 0, 'type' => logger::LOGERROR]);
        $this->assertCount(4, $logs);

        // Case 5: Manager also gets message.
        assign_capability('oermod/opencast:missingvideos', CAP_ALLOW, $manager->id, $context);
        $this->assertTrue(has_capability('oermod/opencast:missingvideos', $context, $user4));
        $sink = $this->redirectEmails();
        \oermod_opencast\message::send_missingvideos($missing, $errors);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(2, $messages);
        $this->assertStringContainsString('admin', $messages[0]->to);
        $this->assertEquals($user4->email, $messages[1]->to);

        $logs = $DB->get_records('local_oer_log', ['courseid' => 0, 'type' => logger::LOGERROR]);
        $this->assertCount(6, $logs);
    }
}
