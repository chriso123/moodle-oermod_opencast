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
     * @param array $wronglicence array of videos with wrong licence
     * @param array $missing array of missing videos
     * @param array $errors array of videos with other errors
     * @return void
     * @throws \dml_exception
     */
    public static function send_missingvideos(array $tofix, array $wronglicence, array $missing, array $errors): void {
        if (empty($tofix) && empty($wronglicence) && empty($missing) && empty($errors) && empty($failed)) {
            return; // Nothing to do.
        }

        $combined = count($tofix) + count($wronglicence) + count($missing) + count($errors);
        logger::add(
            0,
            logger::LOGERROR,
            'Validate Task: ' . $combined .
            ' errors in videos found. Message sent to the main administrator, authorised users and the set email address.',
            'oermod_opencast'
        );

        $message = new \core\message\message();
        $message->component = 'oermod_opencast';
        $message->name = 'missingvideos';
        $message->userfrom = \core_user::get_noreply_user();
        $message->subject = get_string('message:missingvideos', 'oermod_opencast');
        $message->fullmessageformat = FORMAT_HTML;

        $roles = get_config('oermod_opencast', 'rolestoremovewrite');
        $roles = explode("\r\n", $roles);
        $roles = implode(', ', $roles);
        $roles = str_replace('{{', '[', $roles);
        $roles = str_replace('}}', ']', $roles);
        $data = [
            'tofix' => !empty($tofix) ? self::get_video_list_for_message($tofix) : [],
            'roles' => $roles,
            'wronglicence' => !empty($wronglicence) ? self::get_video_list_for_message($wronglicence) : [],
            'missing' => !empty($missing) ? self::get_video_list_for_message($missing) : [],
            'errors' => !empty($errors) ? self::get_video_list_for_message($errors) : [],
        ];
        global $OUTPUT;
        $fullmessage = $OUTPUT->render_from_template('oermod_opencast/message', $data);

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
     * Format the video array for the Mustache template.
     *
     * @param array $videos
     * @return array
     */
    private static function get_video_list_for_message(array $videos): array {
        $data = [];
        foreach ($videos as $video) {
            $data[] = [
                'courseid' => $video['snapshot']->courseid,
                'title' => $video['snapshot']->title,
                'identifier' => $video['snapshot']->identifier,
                'code' => isset($video['response']) ? $video['response']['code'] : '',
                'reason' => isset($video['response']) ? $video['response']['reason'] : '',
            ];
        }
        return $data;
    }
}
