<?php
// The add/edit form for a Bunkercast video activity.

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * The video list is fetched server-side from Bunkercast, so this form needs no
 * JavaScript and no build step — unlike the TinyMCE picker, which has to do the
 * same job from the browser.
 */
class mod_bunkercast_mod_form extends moodleform_mod {

    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('bunkercastname', 'mod_bunkercast'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

        // The video itself.
        $options = [];
        $error = null;
        try {
            foreach (\filter_bunkercast\api::list_videos() as $video) {
                $label = $video['name'];
                if (!empty($video['durationminutes'])) {
                    $label .= ' (' . round($video['durationminutes']) . ' min)';
                }
                $options[$video['fileid']] = $label;
            }
        } catch (Throwable $e) {
            // An unreachable API or a missing key must not make the form
            // unusable — say what is wrong instead of throwing a stack trace at
            // a teacher.
            $error = get_string('listfailed', 'mod_bunkercast');
            debugging('mod_bunkercast: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        if ($error !== null) {
            $mform->addElement('static', 'videoerror', get_string('video', 'mod_bunkercast'), $error);
            $mform->addElement('hidden', 'fileid', '');
            $mform->setType('fileid', PARAM_ALPHANUMEXT);
        } else if (empty($options)) {
            $mform->addElement('static', 'videoempty', get_string('video', 'mod_bunkercast'),
                get_string('novideos', 'mod_bunkercast'));
            $mform->addElement('hidden', 'fileid', '');
            $mform->setType('fileid', PARAM_ALPHANUMEXT);
        } else {
            $mform->addElement('select', 'fileid', get_string('video', 'mod_bunkercast'), $options);
            $mform->setType('fileid', PARAM_ALPHANUMEXT);
            $mform->addRule('fileid', null, 'required', null, 'client');
            $mform->addHelpButton('fileid', 'video', 'mod_bunkercast');
        }

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // The stored id must be a uuid: a hidden empty field (API unreachable)
        // or a tampered value would otherwise create an activity that can never
        // play anything.
        $fileid = strtolower(trim($data['fileid'] ?? ''));
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $fileid)) {
            $errors['fileid'] = get_string('required');
        }

        return $errors;
    }
}
