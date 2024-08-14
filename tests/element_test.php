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

// Project implemented by the "Recovery, Transformation and Resilience Plan.
// Funded by the European Union - Next GenerationEU".
//
// Produced by the UNIMOODLE University Group: Universities of
// Valladolid, Complutense de Madrid, UPV/EHU, León, Salamanca,
// Illes Balears, Valencia, Rey Juan Carlos, La Laguna, Zaragoza, Málaga,
// Córdoba, Extremadura, Vigo, Las Palmas de Gran Canaria y Burgos..
namespace certificateelement_activity;

use advanced_testcase;
use tool_certificate_generator;
use core_text;

/**
 * Unit tests for date element.
 *
 * @package    certificateelement_activity
 * @copyright  2024 Proyecto UNIMOODLE
 * @author     UNIMOODLE Group (Coordinator) <direccion.area.estrategia.digital@uva.es>
 * @author     3IPUNT <contacte@tresipunt.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class element_test extends advanced_testcase {

    /**
     * Test set up.
     */
    public function setUp(): void {
        $this->resetAfterTest();
    }

    /**
     * Get certificate generator
     * @return tool_certificate_generator
     */
    protected function get_generator(): tool_certificate_generator {
        return $this->getDataGenerator()->get_plugin_generator('tool_certificate');
    }

    /**
     * Test render_html
     */
    public function test_render_html(): void {
        $certificate1 = $this->get_generator()->create_template((object)['name' => 'Certificate 1']);
        $pageid = $this->get_generator()->create_page($certificate1)->get_id();
        $formdata = ['name' => 'Activity element',
            'activityitem' => element::ACTIVITY_INTRO,
        ];
        $e = $this->get_generator()->create_element($pageid, 'activity', $formdata);
        $this->assertNotEmpty($e->render_html());

        // Generate PDF for preview.
        $filecontents = $this->get_generator()->generate_pdf($certificate1, true);
        $this->assertGreaterThan(30000, core_text::strlen($filecontents, '8bit'));

        // Generate PDF for issue.
        $issue = $this->get_generator()->issue($certificate1, $this->getDataGenerator()->create_user(), time() + YEARSECS);
        $filecontents = $this->get_generator()->generate_pdf($certificate1, false, $issue);
        $this->assertGreaterThan(30000, core_text::strlen($filecontents, '8bit'));
    }

    /**
     * Test save_unique_data
     */
    public function test_save_unique_data(): void {
        global $DB;
        $certificate1 = $this->get_generator()->create_template((object)['name' => 'Certificate 1']);
        $pageid = $this->get_generator()->create_page($certificate1)->get_id();
        $e = $this->get_generator()->new_element($pageid, 'activity');
        $newdata = (object)['activityitem' => element::ACTIVITY_INTRO];
        $expected = json_encode($newdata);
        $e->save_form_data($newdata);
        $el = $DB->get_record('tool_certificate_elements', ['id' => $e->get_id()]);
        $this->assertEquals($expected, $el->data);
    }
}
