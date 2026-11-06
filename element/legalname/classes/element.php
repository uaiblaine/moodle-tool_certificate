<?php
// This file is part of the tool_certificate plugin for Moodle - http://moodle.org/
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

namespace certificateelement_legalname;

/**
 * Prints the user's first and last name, whatever the site's full name format.
 *
 * The user field element's "Full name" follows `fullnamedisplay`, so a site that shows another
 * name everywhere (such as `alternatename`) prints that name on its certificates too. This
 * element always prints the language's own `fullnamedisplay` string ("{firstname} {lastname}"
 * in English, other orders in other languages), which is what core's fullname() prints when the
 * site format is "language". The language is the one the certificate is generated in
 * ({@see \tool_certificate\template::generate_pdf()} forces it, per the issuelang setting).
 *
 * @package    certificateelement_legalname
 * @copyright  2026 Anderson Blaine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class element extends \tool_certificate\element {
    /**
     * Returns the first and last name of a user, in the current language's order.
     *
     * @param \stdClass $user the user we are rendering this for
     * @return string the name, escaped for HTML (both renderers take HTML)
     */
    protected function get_legal_name(\stdClass $user): string {
        // The language string may name any name field (some languages add the middle name), so all
        // of them are passed, cleaned the way the user field element cleans a user column.
        $names = new \stdClass();
        foreach (\core_user\fields::get_name_fields() as $field) {
            $value = isset($user->$field) ? (string) $user->$field : '';
            $names->$field = $value === '' ? '' : \core_user::clean_field($value, $field);
        }
        return s(trim(get_string('fullnamedisplay', 'core', $names)));
    }

    /**
     * Handles rendering the element on the pdf.
     *
     * @param \pdf $pdf the pdf object
     * @param bool $preview true if it is a preview, false otherwise
     * @param \stdClass $user the user we are rendering this for
     * @param \stdClass $issue the issue we are rendering
     */
    public function render($pdf, $preview, $user, $issue) {
        \tool_certificate\element_helper::render_content($pdf, $this, $this->get_legal_name($user));
    }

    /**
     * Render the element in html.
     *
     * This function is used to render the element when we are using the
     * drag and drop interface to position it.
     *
     * @return string the html
     */
    public function render_html() {
        global $USER;

        // A value is always shown, so the element can be repositioned even for a user with no name.
        $value = $this->get_legal_name($USER);
        $value = strlen($value) ? $value : s(get_string('pluginname', 'certificateelement_legalname'));
        return \tool_certificate\element_helper::render_html_content($this, $value);
    }
}
