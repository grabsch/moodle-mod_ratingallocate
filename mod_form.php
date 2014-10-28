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
 * The main ratingallocate configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package mod_ratingallocate
 * @copyright 2014 M Schulze
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once(dirname(__FILE__) . '/locallib.php');

/**
 * Module instance settings form
 */
class mod_ratingallocate_mod_form extends moodleform_mod {
    // deklariere feste Felder
    /* public $strategy;
      public $accesstimestart;
      public $accesstimestop;
      public $wahloptionen;
     */
    // hier kaemen dann die strategie-optionen, aber die kenne ich ja nicht im voraus

    /**
     * Defines forms elements
     */
    public function definition() {
        /* @var $DB moodle_database */
        global $DB, $COURSE;

        define('ACTION_INSTANCE_ADD', 'instance_add');
        define('ACTION_INSTANCE_UPDATE', 'instance_update');

        $action = '';
        if (empty($this->_instance)) {
            // This is a new instance
            $action = ACTION_INSTANCE_ADD;
        } else {
            $ratingallocateinstanceid = $this->_instance;
            $action = ACTION_INSTANCE_UPDATE;
        }

        $mform = $this->_form;

        // -------------------------------------------------------------------------------
        // Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('ratingallocatename', 'ratingallocate'), array(
            'size' => '64'
        ));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'ratingallocatename', 'ratingallocate');

        // Adding the standard "intro" and "introformat" fields
        $this->add_intro_editor();

        // -------------------------------------------------------------------------------
        $mform->addElement('header', 'ratingallocatefieldset', get_string('ratingallocatefieldset', 'ratingallocate'));
        $mform->addElement('static', 'label2', '', get_string('select_strategy', 'ratingallocate'));

        // Strategiewahl
        $radioarray = array();

        foreach (\strategymanager::get_strategies() as $strategy) {
            $radioarray [] = & $mform->createElement('radio', 'strategy', '', get_string($strategy . '_name', 'ratingallocate'), $strategy);
        }

        $mform->addGroup($radioarray, 'strategy', '', array(
            ' '
                ), false);
        $mform->addRule('strategy', null, 'required', null, 'client');
        // Start- und Endzeitpunkt
        $mform->addElement('static', 'label2', get_string('timespan_votes', 'ratingallocate'), get_string('timespan_votes_open', 'ratingallocate'));
        $mform->addElement('date_time_selector', 'accesstimestart', get_string('from'));
        $mform->addElement('date_time_selector', 'accesstimestop', get_string('to'));

        $mform->addElement('date_time_selector', 'publishdate', get_string('publishdate', 'ratingallocate'));

        $mform->addElement('advcheckbox', 'publishdate_show', get_string('publishdate_show', 'ratingallocate'), '', null, array(0, 1));
        $mform->addHelpButton('publishdate_show', 'publishdate', 'ratingallocate');

        // gucken, ob wir dazu schon was in der DB haben könnten
        if ($action == ACTION_INSTANCE_UPDATE && $DB->record_exists('ratingallocate', array(
                    'id' => $ratingallocateinstanceid
                ))) {
            $ratingallocate = $DB->get_record('ratingallocate', array(
                'id' => $ratingallocateinstanceid
            ));

            // Auch fuer die Strategien
            $strategyoptions = json_decode($ratingallocate->setting, true);
        } else {
            $mform->setDefault('accesstimestart', time() + 24 * 60 * 60); // //beginnt morgen abend
            $mform->setDefault('accesstimestop', time() + 7 * 24 * 60 * 60); // default: now + one week
            $mform->setDefault('publishdate', time() + 9 * 24 * 60 * 60); // default: now + one week
            $mform->setDefault('publishdate_show', 0);
        }

        if ($action == ACTION_INSTANCE_ADD) {
            // Schnellanlage-Optionen, nur bei neuer Instanz
            $mform->addElement('static', 'label2', get_string('choices_options', 'ratingallocate'), get_string('choices_options_oneperline', 'ratingallocate'));
            $mform->addElement('textarea', 'wahloptionen', get_string('choices_quickadd', 'ratingallocate'), 'wrap="virtual" rows="20" cols="50"');
            $mform->addHelpButton('wahloptionen', 'choices_quickadd', 'ratingallocate');
        } else if ($action == ACTION_INSTANCE_UPDATE) {

            $choices = $DB->get_records('ratingallocate_choices', array(
                'ratingallocateid' => $ratingallocateinstanceid
                    ), 'title ASC');

            foreach ($choices as $choice) {
                $elemprefix = 'choices[' . $choice->id . ']';
                $mform->addElement('hidden', $elemprefix . '[id]', $choice->id); // Save the record's id
                $mform->setType($elemprefix . '[id]', PARAM_INT);

                $mform->addElement('header', 'fieldset_edit_choice' . $choice->id, get_string('edit_choice', 'ratingallocate', $choice->title));
                $mform->addElement('text', $elemprefix . '[title]', get_string('choice_title', 'ratingallocate'));
                $mform->setDefault($elemprefix . '[title]', $choice->title);
                $mform->setType($elemprefix . '[title]', PARAM_TEXT);
                $mform->addHelpButton($elemprefix . '[title]', 'choice_title', 'ratingallocate');

                $mform->addElement('text', $elemprefix . '[explanation]', get_string('choice_explanation', 'ratingallocate'));
                $mform->setDefault($elemprefix . '[explanation]', $choice->explanation);
                $mform->setType($elemprefix . '[explanation]', PARAM_TEXT);

                $mform->addElement('text', $elemprefix . '[maxsize]', get_string('choice_maxsize', 'ratingallocate'));
                $mform->setDefault($elemprefix . '[maxsize]', $choice->maxsize);
                $mform->setType($elemprefix . '[maxsize]', PARAM_INT);

                $mform->addElement('checkbox', $elemprefix . '[active]', get_string('choice_active', 'ratingallocate'));
                $mform->setDefault($elemprefix . '[active]', $choice->active);
                $mform->addHelpButton($elemprefix . '[active]', 'choice_active', 'ratingallocate');

                $mform->addElement('checkbox', $elemprefix . '[delete]', get_string('choice_delete', 'ratingallocate'));
                $mform->setDefault($elemprefix . '[delete]', false);
            }

            // Form to add a choice
            $elemprefix = 'newchoice';

            $mform->addElement('header', 'fieldset_edit_newchoice', get_string('newchoice', 'ratingallocate'));
            $mform->addElement('text', $elemprefix . '[title]', get_string('choice_title', 'ratingallocate'));
            $mform->setType($elemprefix . '[title]', PARAM_TEXT);

            $mform->addElement('text', $elemprefix . '[explanation]', get_string('choice_explanation', 'ratingallocate'));
            $mform->setType($elemprefix . '[explanation]', PARAM_TEXT);

            $mform->addElement('text', $elemprefix . '[maxsize]', get_string('choice_maxsize', 'ratingallocate'));
            $mform->setDefault($elemprefix . '[maxsize]', '10');
            $mform->setType($elemprefix . '[maxsize]', PARAM_INT);

            $mform->addElement('checkbox', $elemprefix . '[active]', get_string('choice_active', 'ratingallocate'));
            $mform->setDefault($elemprefix . '[active]', true);
        }

        $attributes = array('size' => '20');

        foreach (\strategymanager::get_strategies() as $strategy) {
            // strategieklasse laden
            $strategyclassp = 'ratingallocate\\' . $strategy . '\\strategy';
            /* @var $strategyclass \strategytemplate */
            $strategyclass = new $strategyclassp;

            $mform->addElement('header', 'strategy_' . $strategy . '_fieldset', get_string('strategyoptions_for_strategy', 'ratingallocate', $strategyclass::STRATEGYNAME));

            // Add options fields
            foreach ($strategyclass::get_settingfields() as $key => $value) {
                // currently only text supported
                if ($value[0] == "text") {
                    $curstratid = 'strategyopt[' . $strategy . '][' . $key . ']';
                    $mform->addElement('text', $curstratid, $value[1], $attributes);
                    $mform->setType($curstratid, PARAM_TEXT);
                    if (isset($strategyoptions) && key_exists($strategy, $strategyoptions)) {
                        $mform->setDefault($curstratid, $strategyoptions[$strategy][$key]);
                    }
                }
            }
        }
        // -------------------------------------------------------------------------------
        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();
        // -------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons();
    }

    public function definition_after_data() {
        parent::definition_after_data();
        $mform = & $this->_form;

        $strategy = $mform->getElementValue('strategy')['strategy'];

        if (!empty($strategy)) { // Make sure the strategy's options are now mandatory
            $strategyclassp = 'ratingallocate\\' . $strategy . '\\strategy';
            /* @var $strategyclass \strategytemplate */
            $strategyclass = new $strategyclassp;
            foreach (array_keys($strategyclass::get_settingfields()) as $key) {
                $mform->addRule('strategyopt[' . $strategy . '][' . $key . ']', null, 'required', null, 'server');
            }
        }
    }

    /**
     * Checks that accesstimestart is before accesstimestop
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data ['accesstimestop'] <= $data ['accesstimestart']) {
            $errors ['accesstimestart'] = get_string('invalid_dates', 'ratingallocate');
        }

        if ($data ['publishdate_show'] && $data['publishdate'] <= $data ['accesstimestart']) {
            $errors ['publishdate'] = get_string('invalid_publishdate', 'ratingallocate');
        }

        // User has to select one strategy
        if (empty($data['strategy'])) {
            $errors['strategy'] = get_string('strategy_not_specified', 'ratingallocate');
        }

        return $errors;
    }

}