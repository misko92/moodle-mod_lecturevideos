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
 * Expand / collapse all students on the progress report.
 *
 * @module     mod_lecturevideos/report
 * @copyright  2026 misko92
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export const init = () => {
    const root = document.querySelector('[data-region="lv-report"]');
    if (!root) {
        return;
    }
    root.addEventListener('click', e => {
        const button = e.target.closest('[data-action="expand-all"], [data-action="collapse-all"]');
        if (!button) {
            return;
        }
        const open = button.dataset.action === 'expand-all';
        root.querySelectorAll('[data-region="student"]').forEach(d => {
            d.open = open;
        });
    });
};
