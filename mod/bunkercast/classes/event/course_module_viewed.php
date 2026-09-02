<?php
// The "viewed" event for mod_bunkercast.

namespace mod_bunkercast\event;

defined('MOODLE_INTERNAL') || die();

/**
 * core\event\course_module_viewed is ABSTRACT — every module must define its
 * own subclass and set objecttable. Calling the core class directly throws
 * "Cannot instantiate abstract class", which does not hint that a subclass is
 * what is wanted.
 */
class course_module_viewed extends \core\event\course_module_viewed {

    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'bunkercast';
    }
}
