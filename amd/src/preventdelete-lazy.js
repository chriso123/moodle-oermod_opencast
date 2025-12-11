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
 * @author     Christian Ortner <christian.ortner@tugraz.at>
 * @copyright  2024 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as Templates from "core/templates";
import * as Str from "core/str";

/**
 * Replace the edit and delete icon of already releaased OER videos with an information.
 *
 * @param {{series: id, videos: {videoid, title}}}released
 * @returns {Promise<void>}
 */
export const init = async (released) => { // eslint-disable-line space-before-function-paren
    const series = released.series;
    const videos = released.videos;
    const action = await Str.get_string('haction', 'block_opencast');
    const visibility = await Str.get_string('hvisibility', 'block_opencast');
    let foundaction = -1;
    let foundvisibility = -1;

    for (const key in series) {
        const table = document.getElementById('opencast-videos-table-' + series[key]);
        if (!table) {
            return;
        }
        const thead = table.rows[0].cells;
        for (let i = 0; i < thead.length; i++) {
            if (thead[i].innerHTML.includes(action + '<div class="commands"></div>')) {
                foundaction = i;
            }
            if (thead[i].innerHTML.includes(visibility)) {
                foundvisibility = i;
            }
        }
        if (foundaction === -1 && foundvisibility === -1) {
            return; // Nothing to do here.
        }
        for (let i = 0; i < table.tBodies[0].rows.length; i++) {
            const cell = document.getElementById('opencast-videos-table-' + series[key]
                + '_r' + i + '_c' + foundaction.toString());
            const visibility = document.getElementById('opencast-videos-table-' + series[key]
                + '_r' + i + '_c' + foundvisibility.toString());
            const content = cell.innerHTML;
            for (const key in videos) {
                if (content.includes(videos[key].videoid) && foundaction > -1) {
                    const {html,} = await Templates.renderForPromise('oermod_opencast/info', {});
                    cell.innerHTML = html;
                }
                if (content.includes(videos[key].videoid) && foundvisibility > -1) {
                    visibility.innerHTML = '-';
                }
            }
        }
    }
};