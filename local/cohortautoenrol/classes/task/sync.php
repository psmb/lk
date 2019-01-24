<?php
namespace local_cohortautoenrol\task;
global $CFG;
require_once("$CFG->dirroot/enrol/cohort/locallib.php");

class sync extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('sync', 'local_cohortautoenrol');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $CFG, $DB;

        $trace = new \text_progress_trace();

        $trace->output("Clearing all cohort enrollments");
        $DB->delete_records('enrol', array('enrol' => 'cohort'));

        $trace->output("Creating new cohort enrollments...");
        $cohortSql = "SELECT course.id AS courseid, course.fullname, joined2.cohortid, joined2.cohortidnumber FROM (
            SELECT categories.id, joined1.cohortid, joined1.cohortidnumber from (
                SELECT context.instanceid, cohort.id AS cohortid, cohort.idnumber AS cohortidnumber FROM {cohort} AS cohort JOIN {context} as context on cohort.contextid=context.id where context.contextlevel=40
            ) AS joined1 JOIN {course_categories} AS categories ON categories.path LIKE CONCAT('%/', joined1.instanceid, '/%')
        ) AS joined2 JOIN {course} AS course ON course.category=joined2.id";

        $cohortRs = $DB->get_recordset_sql($cohortSql);
        foreach($cohortRs as $cohortRow) {
            $record = (object) [
                'enrol' => 'cohort',
                'status' => '0',
                'courseid' => $cohortRow->courseid,
                'roleid' => '9',
                'customint1' => $cohortRow->cohortid,
                'customint2' => '0'
            ];
            $DB->insert_record('enrol', $record);
            $trace->output(sprintf("  creating cohort sync in course '%s' for cohort '%s'", $cohortRow->fullname, $cohortRow->cohortidnumber));
        }

        // Sync the cohorts now
        $result = \enrol_cohort_sync($trace, null);
        $trace->finished();
    }
}
