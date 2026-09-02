<?php
// Lists every Bunkercast video in a course.

require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);   // course id
$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);

require_course_login($course);

$context = context_course::instance($course->id);

$PAGE->set_url('/mod/bunkercast/index.php', ['id' => $id]);
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'mod_bunkercast'));

if (!$instances = get_all_instances_in_course('bunkercast', $course)) {
    echo $OUTPUT->notification(get_string('nobunkercasts', 'mod_bunkercast'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = [get_string('name'), get_string('sectionname', 'format_' . $course->format)];
foreach ($instances as $instance) {
    $link = html_writer::link(
        new moodle_url('/mod/bunkercast/view.php', ['id' => $instance->coursemodule]),
        format_string($instance->name),
        $instance->visible ? [] : ['class' => 'dimmed']
    );
    $table->data[] = [$link, get_section_name($course, $instance->section)];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
