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
 * CLI sync for cohort enrolments, use for debugging or immediate sync
 * of all courses.
 *
 * Notes:
 *   - it is required to use the web server account when executing PHP CLI scripts
 *   - you need to change the "www-data" to match the apache user account
 *   - use "su" if "sudo" not available
 *
 * @package    enrol_cohort
 * @copyright  2011 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once("$CFG->libdir/clilib.php");
require_once("$CFG->dirroot/enrol/cohort/locallib.php");

global $CFG, $DB;

$groupMap = [
    'БФ-БМ' => ['Магистратура', 'Базовая часть']
];


echo "Clearing all cohort enrollments... \r\n";
$DB->delete_records('enrol', array('enrol' => 'cohort'));


$plugin = enrol_get_plugin('cohort');
$unenrolaction = $plugin->get_config('unenrolaction', ENROL_EXT_REMOVED_UNENROL);

$cohortSql = "SELECT id, idnumber FROM {cohort}";

$cohortRs = $DB->get_recordset_sql($cohortSql);
foreach($cohortRs as $cohortRow) {
    preg_match('/(.+)-(.+)/', $cohortRow->idnumber, $matches);
    $faculty = $matches[1];
    $group = $matches[1];
    if (!isset($groupMap[$group])) {
        echo "Invalid group " . $group;
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

    $params['course'] = $course . ' курс';
    $params['semestr'] = $semestr . ' семестр';
    $params['first'] = $groupMap[$group][0];
    $params['last'] = $groupMap[$group][1];

    $courseSql = "
        SELECT * from {course} where category = (
            SELECT id from {course_categories} where name = :last and parent = (
                SELECT id from {course_categories} where name = :semestr and parent = (
                    SELECT id from {course_categories} where name = :course and parent = (
                        SELECT id FROM {course_categories} where name = :first
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
        echo sprintf("Creating cohort sync in course '%s' for cohort '%s' \r\n", $courseRow->fullname, $cohortRow->idnumber);
    }
}






// Sync the cohorts now
echo "Run enrol_cohort_sync\r\n";
$trace = new text_progress_trace();
$result = enrol_cohort_sync($trace, null);
$trace->finished();
exit($result);
