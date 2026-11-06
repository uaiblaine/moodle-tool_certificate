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

use advanced_testcase;
use core_text;
use tool_certificate_generator;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/pdflib.php');

#[\PHPUnit\Framework\Attributes\Group('tool_certificate')]
#[\PHPUnit\Framework\Attributes\CoversClass(\certificateelement_legalname\element::class)]
/**
 * Unit tests for the legal name element.
 *
 * @package    certificateelement_legalname
 * @group      tool_certificate
 * @covers     \certificateelement_legalname\element
 * @copyright  2026 Anderson Blaine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class element_test extends advanced_testcase {
    /**
     * Test set up.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Get certificate generator
     *
     * @return tool_certificate_generator
     */
    protected function get_generator(): tool_certificate_generator {
        return $this->getDataGenerator()->get_plugin_generator('tool_certificate');
    }

    /**
     * Creates a template page holding one legal name element.
     *
     * @return element the element
     */
    protected function create_legalname_element(): element {
        $template = $this->get_generator()->create_template((object) ['name' => 'Certificate 1']);
        $pageid = $this->get_generator()->create_page($template)->get_id();
        /** @var element $element */
        $element = $this->get_generator()->create_element($pageid, 'legalname', ['name' => 'Legal name element']);
        return $element;
    }

    /**
     * Creates a user whose alternate name differs from the first and last name.
     *
     * @param string $lastname the last name
     * @return \stdClass the user
     */
    protected function create_named_user(string $lastname = 'Silva'): \stdClass {
        return $this->getDataGenerator()->create_user([
            'firstname' => 'João',
            'lastname' => $lastname,
            'alternatename' => 'Sargento Silva',
        ]);
    }

    /**
     * The PDF prints the first and last name while the site's format shows the alternate name.
     */
    public function test_render_ignores_fullnamedisplay(): void {
        set_config('fullnamedisplay', 'alternatename');
        $user = $this->create_named_user();
        $element = $this->create_legalname_element();

        // The precondition: on this site fullname() is the alternate name.
        $this->assertSame('Sargento Silva', fullname($user));

        $pdf = $this->getMockBuilder(\pdf::class)->onlyMethods(['writeHTMLCell'])->getMock();
        $pdf->expects($this->once())
            ->method('writeHTMLCell')
            ->with($this->anything(), $this->anything(), $this->anything(), $this->anything(), 'João Silva');
        $pdf->AddPage();
        $element->render($pdf, false, \core_user::get_user($user->id), null);
    }

    /**
     * The editor preview shows the current user's first and last name, escaped.
     */
    public function test_render_html(): void {
        set_config('fullnamedisplay', 'alternatename');
        $user = $this->create_named_user('Silva & Souza');
        $element = $this->create_legalname_element();
        $this->setUser($user);

        $html = $element->render_html();

        $this->assertStringContainsString('João Silva &amp; Souza', $html);
        $this->assertStringNotContainsString('Sargento', $html);
    }

    /**
     * The editor preview falls back to the element name for a user without a name.
     */
    public function test_render_html_without_name(): void {
        $user = $this->getDataGenerator()->create_user(['firstname' => '', 'lastname' => '']);
        $element = $this->create_legalname_element();
        $this->setUser($user);

        $this->assertStringContainsString('Legal name', $element->render_html());
    }

    /**
     * A whole certificate with the element generates, for preview and for an issue.
     */
    public function test_generate_pdf(): void {
        $this->setAdminUser();
        $user = $this->create_named_user();
        $element = $this->create_legalname_element();
        $template = $element->get_template();

        $filecontents = $this->get_generator()->generate_pdf($template, true);
        $this->assertGreaterThan(30000, core_text::strlen($filecontents, '8bit'));

        $issue = $this->get_generator()->issue($template, $user);
        $filecontents = $this->get_generator()->generate_pdf($template, false, $issue);
        $this->assertGreaterThan(30000, core_text::strlen($filecontents, '8bit'));
    }

    /**
     * Tests that the edit element form can be initiated without any errors.
     */
    public function test_edit_element_form(): void {
        $this->setAdminUser();

        $form = $this->get_generator()->create_template_and_edit_element_form('legalname');
        $this->assertNotEmpty($form->render());
    }
}
