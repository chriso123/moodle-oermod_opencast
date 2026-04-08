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
 * @copyright  2023 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['addpeoplesetting'] = 'Personen zum OER-Element hinzufügen';
$string['addpeoplesetting_description'] = 'Fügen Sie die Personen und ihre Rollen automatisch zum OER-Element hinzu, wenn Sie die Videos aus opencast laden. Die folgenden Rollen werden hinzugefügt: Präsentator:in, Beitragende:r, Rechteinhaber:in.';
$string['check_released_videos_task'] = 'Task zum Überprüfen von veröffentlichten Videos';
$string['contributor'] = 'Beitragende:r';
$string['creator'] = 'Ersteller:in';
$string['duration'] = 'Dauer';
$string['message:errors'] = 'Folgende veröffentlichte Opencast Videos haben Fehler:';
$string['message:missingvideos'] = 'Open Educational Resources (OER): Opencast videos mit Fehlern';
$string['message:missingvideos_body'] = 'Folgende Opencast Videos können nicht mehr gefunden werden (Task returniert 404):';
$string['message:missingvideos_footer'] = 'Diese Videos müssen manuell überprüft werden.';
$string['message:missingvideos_small'] = 'Opencast Videos fehlen, bitte kontrollieren.';
$string['message:tofixvideos_body'] = 'Videos mit falschen ACL-Einstellungen:';
$string['message:tofixvideos_body_extension'] = 'ROLE_ANONYMOUS ist nicht gesetzt oder die Rollen {$a->roles} verfügen über Schreibrechte';
$string['message:top'] = 'Bei der Validierung der veröffentlichten OER-Opencast-Videos wurden einige Probleme festgestellt.';
$string['message:wronglicencevideos_body'] = 'Diese Videos stehen nicht unter einer CC-Lizenz:';
$string['messageprovider:missingvideos'] = 'Benachrichtigung wenn Videos auf Opencast fehlen.';
$string['notify_on_error'] = 'E-Mail zur Benachrichtigung bei Fehlern';
$string['notify_on_error_description'] = 'Eine geplante Aufgabe überprüft, ob die Videos auf Opencast weiterhin als öffentlich eingestellt sind und ob die Rollen, die keinen Schreibzugriff haben sollen (Einstellung „oermod_opencast|rolestoremovewrite“), korrekt konfiguriert wurden. Sollte etwas nicht korrekt sein, wird eine E-Mail an die angegebene Adresse gesendet. Die E-Mail wird außerdem an den Hauptadministrator und an Benutzer mit der Berechtigung „oermod/opencast:missingvideos“ gesendet.';
$string['origin'] = 'Opencast';
$string['pluginname'] = 'OER Subplugin zum Laden von Opencast Videos';
$string['presenter'] = 'Präsentator:in';
$string['privacy:metadata'] = 'Dieses Plugin speichert keine Daten';
$string['releasedvideo'] = '<p>Dieses Video wurde als <strong>Open Educational Resource (OER)</strong> veröffentlicht.</p><p>Auf dieser Oberfläche ist es nicht mehr möglich die Metadaten des Videos zu editieren, oder das Video zu löschen.<br>Um das Video zu löschen, wenden Sie sich bitte an den Support.</p>';
$string['rightsholder'] = 'Rechteinhaber:in';
$string['rolestoremovewrite'] = 'Rollen, von denen Schreibrechte entfernt werden';
$string['rolestoremovewrite_description'] = 'Opencast-Rollen, bei denen die Schreibrechte nach Freigabe eines OER-Objekts entfernt werden. Dadurch wird verhindert, dass das Video in Opencast verändert oder gelöscht wird. Eine Rolle pro Zeile. Der Platzhalter {{courseid}} kann verwendet werden.<p><strong>Wichtig:</strong>Damit dies funktioniert, müssen Opencast-Workflows auf Rollen beschränkt werden, und der Opencast-Admin-Benutzer, der in tool_opencast festgelegt ist, darf keinen Schreibzugriff auf die Standard-Admin-Rolle in Opencast haben (Standardname: ROLE_ADMIN)</p>';
$string['series'] = 'Serie';
