<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Mink\Exception\ExpectationException;

/**
 * Behat steps for local_efscorm: where the browser landed.
 *
 * @package    local_efscorm
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_local_efscorm extends behat_base {
    /**
     * Checks that the current page is the core SCORM player (player.php).
     *
     * @Then /^I should be on the SCORM player page$/
     */
    public function i_should_be_on_the_scorm_player_page(): void {
        $url = $this->getSession()->getCurrentUrl();
        if (strpos($url, '/mod/scorm/player.php') === false) {
            throw new ExpectationException('Expected the SCORM player page, got ' . $url, $this->getSession());
        }
    }

    /**
     * Checks that the current page is not the core SCORM player.
     *
     * @Then /^I should not be on the SCORM player page$/
     */
    public function i_should_not_be_on_the_scorm_player_page(): void {
        $url = $this->getSession()->getCurrentUrl();
        if (strpos($url, '/mod/scorm/player.php') !== false) {
            throw new ExpectationException('Did not expect the SCORM player page, got ' . $url, $this->getSession());
        }
    }

    /**
     * Checks that the player was opened in "popup" display mode (the new-window player, in the same tab).
     *
     * @Then /^the SCORM player is in popup display mode$/
     */
    public function the_scorm_player_is_in_popup_display_mode(): void {
        $url = $this->getSession()->getCurrentUrl();
        if (strpos($url, 'display=popup') === false) {
            throw new ExpectationException('Expected display=popup in the player URL, got ' . $url, $this->getSession());
        }
    }
}
