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

        $groupMap = [
            'БФ-БМ' => [['ЛК', 'Богословский факультет', 'Магистратура'], 'Базовая часть'],
            'БФ-БД' => [['ЛК', 'Богословский факультет','Бакалавриат'], 'Очная форма обучения'],
            'БФ-БВ' => [['ЛК', 'Богословский факультет','Бакалавриат'], 'Очно-заочная форма обучения'],
            'БФ-БЗ' => [['ЛК', 'Богословский факультет','Бакалавриат'], 'Заочная форма обучения']
        ];

        $trace->output("Clearing all cohort enrollments");
        $DB->delete_records('enrol', array('enrol' => 'cohort'));

        $trace->output("Creating new cohort enrollments...");
        $cohortSql = "SELECT id, idnumber FROM {cohort}";

        $cohortRs = $DB->get_recordset_sql($cohortSql);
        foreach($cohortRs as $cohortRow) {
            preg_match('/(.+)-(.+)/', $cohortRow->idnumber, $matches);
            $faculty = $matches[1];
            $group = $matches[1];
            if (!isset($groupMap[$group])) {
                $trace->output("Invalid group " . $group);
                break;
            }
            $year = $matches[2];


            $courseStartDate = strtotime('01-09-20' . $year);
            $secondsInMonth = 2629746;
            $monthsPast = (time() - $courseStartDate) / $secondsInMonth;
            $yearsPast = $monthsPast / 12;
            $course = floor($yearsPast) + 1;
            $monthsInSemestr = $monthsPast % 12;
            $semestr = $monthsPast % 12 < 5 ? 1 : 2;

            $innerQuery = "";
            foreach ($groupMap[$group][0] as $name) {
                if ($innerQuery) {
                    $innerQuery = "SELECT id FROM {course_categories} where name = '" . $name . "' and parent = ( $innerQuery )";
                } else {
                    $innerQuery = "SELECT id FROM {course_categories} where name = '" . $name . "'";
                }
            }

            $params['course'] = $course . ' курс';
            $params['semestr'] = $semestr . ' семестр';
            $params['last'] = $groupMap[$group][1];

            $courseSql = "
                SELECT * from {course} where category = (
                    SELECT id from {course_categories} where name = :last and parent = (
                        SELECT id from {course_categories} where name = :semestr and parent = (
                            SELECT id from {course_categories} where name = :course and parent = (
                                $query
                            )
                        )
                    )
                )
            ";

            $courseRs = $DB->get_recordset_sql($courseSql, $params);

            foreach($courseRs as $courseRow) {
                $record = (object) [
                    'enrol' => 'cohort',
                    'status' => '0',
                    'courseid' => $courseRow->id,
                    'roleid' => '9',
                    'customint1' => $cohortRow->id,
                    'customint2' => '0'
                ];
                $DB->insert_record('enrol', $record);
                $trace->output(sprintf("  creating cohort sync in course '%s' for cohort '%s'", $courseRow->fullname, $cohortRow->idnumber));
            }
        }

        // Sync the cohorts now
        $result = \enrol_cohort_sync($trace, null);
        $trace->finished();
    }
}
