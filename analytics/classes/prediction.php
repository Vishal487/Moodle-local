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
 * Representation of a prediction.
 *
 * @package   core_analytics
 * @copyright 2017 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_analytics;

defined('MOODLE_INTERNAL') || die();

/**
 * Representation of a prediction.
 *
 * @package   core_analytics
 * @copyright 2017 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class prediction {

    /**
     * @var \stdClass
     */
    private $prediction;

    /**
     * @var array
     */
    private $sampledata;

    /**
     * @var array
     */
    private $calculations = array();

    /**
     * Constructor
     *
     * @param \stdClass $prediction
     * @param array $sampledata
     * @return void
     */
    public function __construct($prediction, $sampledata) {
        global $DB;

        if (is_scalar($prediction)) {
            $prediction = $DB->get_record('analytics_predictions', array('id' => $prediction), '*', MUST_EXIST);
        }
        $this->prediction = $prediction;

        $this->sampledata = $sampledata;

        $this->format_calculations();
    }

    /**
     * Get prediction object data.
     *
     * @return \stdClass
     */
    public function get_prediction_data() {
        return $this->prediction;
    }

    /**
     * Get prediction sample data.
     *
     * @return array
     */
    public function get_sample_data() {
        return $this->sampledata;
    }

    /**
     * Gets the prediction calculations
     *
     * @return array
     */
    public function get_calculations() {
        return $this->calculations;
    }

    /**
     * format_calculations
     *
     * @return \stdClass[]
     */
    private function format_calculations() {

        $calculations = json_decode($this->prediction->calculations, true);

        foreach ($calculations as $featurename => $value) {

            list($indicatorclass, $subtype) = $this->parse_feature_name($featurename);

            if ($indicatorclass === 'range') {
                // Time range indicators don't belong to any indicator class, we don't store them.
                continue;
            } else if (!\core_analytics\manager::is_valid($indicatorclass, '\core_analytics\local\indicator\base')) {
                throw new \moodle_exception('errorpredictionformat', 'analytics');
            }

            $this->calculations[$featurename] = new \stdClass();
            $this->calculations[$featurename]->subtype = $subtype;
            $this->calculations[$featurename]->indicator = \core_analytics\manager::get_indicator($indicatorclass);
            $this->calculations[$featurename]->value = $value;
        }
    }

    /**
     * parse_feature_name
     *
     * @param string $featurename
     * @return string[]
     */
    private function parse_feature_name($featurename) {

        $indicatorclass = $featurename;
        $subtype = false;

        // Some indicator result in more than 1 feature, we need to see which feature are we dealing with.
        $separatorpos = strpos($featurename, '/');
        if ($separatorpos !== false) {
            $subtype = substr($featurename, ($separatorpos + 1));
            $indicatorclass = substr($featurename, 0, $separatorpos);
        }

        return array($indicatorclass, $subtype);
    }
}