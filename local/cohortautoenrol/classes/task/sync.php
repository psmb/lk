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
        $DB->delete_records('enrol', ['enrol' => 'cohort']);
        $DB->delete_records('role_assignments', ['component' => 'enrol_cohort']);
        $DB->execute("DELETE ue FROM {user_enrolments} ue LEFT JOIN {enrol} e ON e.id = ue.enrolid WHERE e.id IS NULL");

        $trace->output("Creating new cohort enrollments...");
        $cohortSql = "SELECT course.id AS courseid, course.fullname, joined2.cohortid, joined2.cohortidnumber, joined2.cohortdescription FROM (
            SELECT categories.id, joined1.cohortid, joined1.cohortidnumber, joined1.cohortdescription from (
                SELECT context.instanceid, cohort.id AS cohortid, cohort.idnumber AS cohortidnumber, cohort.description AS cohortdescription FROM {cohort} AS cohort JOIN {context} AS context ON cohort.homeid=context.id WHERE context.contextlevel=40
            ) AS joined1 JOIN {course_categories} AS categories ON categories.path LIKE CONCAT('%/', joined1.instanceid, '/%') OR categories.path LIKE CONCAT('%/', joined1.instanceid)
        ) AS joined2 JOIN {course} AS course ON course.category=joined2.id";

        $cohortRs = $DB->get_recordset_sql($cohortSql);
        foreach($cohortRs as $cohortRow) {
            // if description contains "БЕСПЛАТНО", then assign as unrestricted user
            $roleid = \strpos($cohortRow->cohortdescription, 'БЕСПЛАТНО') !== false ? 9 : 5;
            $record = (object) [
                'enrol' => 'cohort',
                'status' => 0,
                'courseid' => $cohortRow->courseid,
                'roleid' => $roleid,
                'customint1' => $cohortRow->cohortid,
                'customint2' => 0
            ];
            $DB->insert_record('enrol', $record);
            $trace->output(sprintf("  creating cohort sync in course '%s' for cohort '%s' with roleid '%s'", $cohortRow->fullname, $cohortRow->cohortidnumber, $roleid));
        }

        // Sync the cohorts now
        $result = \enrol_cohort_sync($trace, null);
        $trace->finished();
    }
}
