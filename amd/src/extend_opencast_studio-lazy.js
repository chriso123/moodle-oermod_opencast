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
 * @copyright  2026 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Log from 'core/log';

/**
 * Add additional parameters to the record button of the Opencast block.
 *
 * @param {string} params
 * @returns {Promise<void>}
 */
export const init = (params) => {
    const studioLink = document.querySelector('.opencast-recordvideo-wrap a');
    if (studioLink) {
        const currentUrl = studioLink.getAttribute('href');
        try {
            const urlObj = new URL(currentUrl, window.location.origin);
            let currentValue = urlObj.searchParams.get('rd') || '';
            const paramsToAppend = new URLSearchParams(params).toString();
            if (paramsToAppend) {
                const separator = currentValue.includes('=') ? '&' : '';
                const newValue = currentValue + separator + paramsToAppend;
                urlObj.searchParams.set('rd', newValue);
            }
            studioLink.setAttribute('href', urlObj.toString());
        } catch (error) {
            Log.error('Could not extend opencast studio url', error);
        }
    }
};