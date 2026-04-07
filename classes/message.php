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

use context_system;
use local_oer\logger;

/**
 * Class message
 */
class message {
    /**
     * Notify set email or main admin that some OER released Opencast videos have errors.
     *
     * This requires manual action to clean up or restore the videos in Opencast.
     *
     * @param array $tofix array of videos with wrong ACL settings
     * @param array $missing array of missing videos
     * @param array $errors array of videos with other errors
     * @param array $failed array of videos that could not be released
     * @return void
     * @throws \dml_exception
     */
    public static function send_missingvideos(array $tofix, array $missing, array $errors, array $failed): void {
        if (empty($tofix) && empty($missing) && empty($errors) && empty($failed)) {
            return;
        }
        $message = new \core\message\message();
        $message->component = 'oermod_opencast';
        $message->name = 'missingvideos';
        $message->userfrom = \core_user::get_noreply_user();
        $message->subject = get_string('message:missingvideos', 'oermod_opencast');
        $message->fullmessageformat = FORMAT_HTML;
        $fullmessage = '';
        if (!empty($tofix)) {
            $roles = get_config('oermod_opencast', 'rolestoremovewrite');
            $roles = explode("\r\n", $roles);
            $roles = implode(', ', $roles);
            $fullmessage .= '<h3>' .
                get_string('message:tofixvideos_body', 'oermod_opencast') .
                '</h3><p>' .
                get_string('message:tofixvideos_body_extension', 'oermod_opencast', ['roles' => $roles]) .
                '</p>';
            $filelisthtml = '<p>';
            logger::add(
                0,
                logger::LOGERROR,
                count($tofix) . ' videos have wrong ACL settings. Notification has been sent to admins.',
                'oermod_opencast'
            );
            foreach ($tofix as $video) {
                $filelisthtml .= self::get_video_for_message(
                    $video['snapshot']->courseid,
                    $video['snapshot']->title,
                    $video['snapshot']->identifier
                );
            }
            $fullmessage .= $filelisthtml . '</p>';
        }
        if (!empty($failed)) {
            $fullmessage .= '<h3>' . get_string('message:failedvideos_body', 'oermod_opencast') . '</h3>';
            $filelisthtml = '<p>';
            logger::add(
                0,
                logger::LOGERROR,
                count($failed) . ' videos failed to be set to release. Notification has been sent to admins.',
                'oermod_opencast'
            );
            foreach ($failed as $video) {
                $filelisthtml .= self::get_video_for_message(
                    $video['snapshot']->courseid,
                    $video['snapshot']->title,
                    $video['snapshot']->identifier
                );
            }
            $fullmessage .= $filelisthtml . '</p>';
        }
        if (!empty($missing)) {
            $fullmessage .= '<h3>' . get_string('message:missingvideos_body', 'oermod_opencast') . '</h3>';
            $filelisthtml = '<p>';
            logger::add(
                0,
                logger::LOGERROR,
                count($missing) . ' missing videos. Notification has been sent to admins.',
                'oermod_opencast'
            );
            foreach ($missing as $video) {
                $filelisthtml .= self::get_video_for_message(
                    $video['snapshot']->courseid,
                    $video['snapshot']->title,
                    $video['snapshot']->identifier
                );
            }
            $fullmessage .= $filelisthtml . '</p>';
        }
        if (!empty($errors)) {
            $fullmessage .= '<h3>' . get_string('message:errors', 'oermod_opencast') . '</h3>';
            $filelisthtml = '<p>';
            logger::add(
                0,
                logger::LOGERROR,
                count($errors) . ' errors with videos. Notification has been sent to admins.',
                'oermod_opencast'
            );
            foreach ($errors as $error) {
                $filelisthtml .= self::get_video_for_message(
                    $error['snapshot']->courseid,
                    $error['snapshot']->title,
                    $error['snapshot']->identifier
                );
                $filelisthtml .= '&nbsp;&nbsp;&nbsp;Error: ' . $error['response']['code'] . ' | ' . $error['response']['reason'] .
                    '<br>';
            }
            $fullmessage .= $filelisthtml . '</p>';
        }
        $message->fullmessage = $fullmessage;
        $message->fullmessagehtml = $fullmessage;
        $message->smallmessage = get_string('message:missingvideos_small', 'oermod_opencast');
        $message->notification = 1;
        $content = [
            '*' => [
                'header' => get_string('message:missingvideos', 'oermod_opencast'),
                'footer' => get_string('message:missingvideos_footer', 'oermod_opencast'),
            ],
        ];
        $message->set_additional_content('email', $content);

        $users = self::get_users();
        foreach ($users as $user) {
            $message->userto = $user;
            message_send($message);
        }
        self::send_message_to_support_email($message);
    }

    /**
     * Send mail to main admin and users with missingvideos capability.
     *
     * @return array
     * @throws \dml_exception
     */
    private static function get_users(): array {
        return array_merge([get_admin()], get_users_by_capability(context_system::instance(), 'oermod/opencast:missingvideos'));
    }

    /**
     * Send message to email set in oermod_opencast|notificationemail setting.
     *
     * @param \core\message\message $message
     * @return void
     * @throws \dml_exception
     */
    private static function send_message_to_support_email(\core\message\message $message): void {
        if ($email = get_config('oermod_opencast', 'notificationemail')) {
            $admin = get_admin();
            $admin->email = $email;
            email_to_user($admin, $admin, $message->subject, $message->fullmessage, $message->fullmessagehtml);
        }
    }

    /**
     * Concatenate information to a single line string for message.
     *
     * @param int $courseid
     * @param string $title
     * @param string $identifier
     * @return string
     */
    private static function get_video_for_message(int $courseid, string $title, string $identifier): string {
        return "* CourseID: $courseid | $title | $identifier<br>";
    }
}
