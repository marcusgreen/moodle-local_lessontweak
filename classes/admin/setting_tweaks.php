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

namespace local_lessontweak\admin;

/**
 * Admin setting for the appearance tweaks JSON.
 *
 * @package    local_lessontweak
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setting_tweaks extends \admin_setting_configtextarea {

    /**
     * Validate the tweaks JSON: an array of objects each with a name and css.
     *
     * @param string $data
     * @return true|string true if valid, else an error string.
     */
    public function validate($data) {
        $data = trim((string) $data);
        if ($data === '') {
            // Empty means "no tweaks defined" — allowed.
            return true;
        }

        $decoded = json_decode($data);
        if (!is_array($decoded)) {
            return get_string('tweaksinvalidjson', 'local_lessontweak');
        }

        foreach ($decoded as $item) {
            if (!is_object($item)
                    || empty($item->name) || !is_string($item->name)
                    || !isset($item->css) || !is_string($item->css) || trim($item->css) === '') {
                return get_string('tweaksinvalidjson', 'local_lessontweak');
            }
        }

        return true;
    }
}
