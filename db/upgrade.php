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
 * Database upgrades for Video Tracker Ultra.
 *
 * @package   mod_videotrackerultra
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade Video Tracker Ultra.
 *
 * @param int $oldversion Previously installed plugin version.
 * @return bool
 */
function xmldb_videotrackerultra_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026100601) {
        $table = new xmldb_table('videotrackerultra');
        $index = new xmldb_index('course', XMLDB_INDEX_NOTUNIQUE, ['course']);

        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        upgrade_mod_savepoint(true, 2026100601, 'videotrackerultra');
    }

    return true;
}
