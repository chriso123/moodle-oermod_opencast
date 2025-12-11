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

$plugin->version = 2025112500;
$plugin->requires = 2024100707;
$plugin->component = 'oermod_opencast';
$plugin->release = 'v1.0.4';
$plugin->maturity = MATURITY_STABLE;
$plugin->dependencies = [
        'local_oer' => 2023111701, // Older versions do not have mod subplugin support.
        'tool_opencast' => 2023112200, // Needed for API access.
        'block_opencast' => 2023112200, // Needed for series id.
];
$plugin->supported = [405, 405];
