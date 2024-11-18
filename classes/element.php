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

/**
 * This file contains the certificate element activity's core interaction API.
 *
 * @package    certificateelement_activity
 * @copyright  2024 Proyecto UNIMOODLE
 * @author     UNIMOODLE Group (Coordinator) <direccion.area.estrategia.digital@uva.es>
 * @author     3IPUNT <contacte@tresipunt.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace certificateelement_activity;

use mod_certifygen\persistents\certifygen;
use mod_certifygen\persistents\certifygen_model;
use mod_certifygen\persistents\certifygen_validations;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/lib/grade/constants.php');

/**
 * The certificate element activity's core interaction API.
 *
 * @package    certificateelement_activity
 * @copyright  2024 Proyecto UNIMOODLE
 * @author     UNIMOODLE Group (Coordinator) <direccion.area.estrategia.digital@uva.es>
 * @author     3IPUNT <contacte@tresipunt.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class element extends \tool_certificate\element {
    /**
     * @var int Show activity name
     */
    const ACTIVITY_NAME = 0;

    /**
     * @var int Show activity intro
     */
    const ACTIVITY_INTRO = 1;

    /**
     * This function renders the form elements when adding a certificate element.
     *
     * @param \MoodleQuickForm $mform the edit_form instance
     * @throws \coding_exception
     */
    public function render_form_elements($mform) {

        // Get the possible date options.
        $dateoptions = [];
        $dateoptions[self::ACTIVITY_NAME] = get_string('activityname', 'certificateelement_activity');
        $dateoptions[self::ACTIVITY_INTRO] = get_string('activityintro', 'certificateelement_activity');

        $mform->addElement('select', 'activityitem', get_string('activityitem', 'certificateelement_activity'), $dateoptions);
        $mform->addHelpButton('activityitem', 'activityitem', 'certificateelement_activity');

        parent::render_form_elements($mform);
    }

    /**
     * Handles saving the form elements created by this element.
     * Can be overridden if more functionality is needed.
     *
     * @param \stdClass $data the form data or partial data to be updated (i.e. name, posx, etc.)
     */
    public function save_form_data(\stdClass $data) {
        $data->data = json_encode(['activityitem' => $data->activityitem]);
        parent::save_form_data($data);
    }

    /**
     * Handles rendering the element on the pdf.
     *
     * @param \pdf $pdf the pdf object
     * @param bool $preview true if it is a preview, false otherwise
     * @param \stdClass $user the user we are rendering this for
     * @param \stdClass $issue the issue we are rendering
     * @throws \coding_exception
     */
    public function render($pdf, $preview, $user, $issue) {
        $certifygen = null;
        if (!is_null($issue)) {
            $params = ['userid' => (int)$user->id, 'issueid' => (int)$issue->id];
            $validation = certifygen_validations::get_record($params);
            $name = '';
            if ($validation) {
                $model = new certifygen_model((int)$validation->get('modelid'));
                $certifygen = certifygen::get_record(['modelid' => $model->get('id'), 'course' => $issue->courseid]);
            }
        }
        // Decode the information stored in the database.
        $datainfo = @json_decode($this->get_data(), true) + ['activityitem' => ''];
        // If we are previewing this certificate then just show a demonstration date.
        if ($preview) {
            $name = get_string('activityexamplename', 'certificateelement_activity');
        } else if ($datainfo['activityitem'] == self::ACTIVITY_NAME && !is_null($certifygen)) {
            $name = $certifygen->get('name');
        } else if ($datainfo['activityitem'] == self::ACTIVITY_INTRO && !is_null($certifygen)) {
            $name = $certifygen->get('intro');
            $name = trim(format_text($name, $certifygen->get('introformat')));
        }

        // Ensure that a date has been set.
        if (!empty($name)) {
            \tool_certificate\element_helper::render_content($pdf, $this, $name);
        }
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
        // Decode the information stored in the database.
        $datainfo = @json_decode($this->get_data(), true) + ['activityitem' => ''];
        return \tool_certificate\element_helper::render_html_content($this, $datainfo['activityitem']);
    }

    /**
     * Prepare data to pass to moodleform::set_data()
     *
     * @return \stdClass|array
     */
    public function prepare_data_for_form() {
        $record = parent::prepare_data_for_form();
        if (!empty($this->get_data())) {
            $dateinfo = json_decode($this->get_data());
            $record->activityitem = $dateinfo->activityitem;
        }
        return $record;
    }
}
