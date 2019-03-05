<?php
require_once '../../config.php';

$adminRoleId = 18;

$contextid = required_param('contextid', PARAM_INT);
$reason = strip_tags(optional_param('reason', '', PARAM_RAW));

list($context, $course, $cm) = get_context_info_array($contextid);
// Security.
require_login();

$categoryContext = $DB->get_record_sql("SELECT path FROM {context} WHERE contextlevel=40 AND instanceid=$course->category");
$categoryContextPath = $categoryContext->path;

$roles = "SELECT userid, path FROM {role_assignments} AS assignments JOIN {context} AS context ON assignments.contextid = context.id WHERE roleid=$adminRoleId";
$rows = $DB->get_recordset_sql($roles);
$userid = null;
$noAdminsFound = true;
foreach($rows as $row) {
    if (substr($categoryContextPath, 0, strlen($row->path)) === $row->path) {
        $noAdminsFound = false;

        $userid = $row->userid;
        $user = $DB->get_record_sql("SELECT * FROM {user} where id = $userid");

        $approveUrl = "https://kabinet.sfi.ru/local/cohortautoenrol/approve.php?contextid=$contextid&userid=$USER->id";
        $messagehtml = "
Запрос на доступ: <br>
Студент: $USER->firstname $USER->lastname <br>
Курс: $course->fullname <br>
Лекция: $cm->name <br>
Основание: $reason <br>
<br>
Проследуйте по ссылке, чтобы подтвердить запрос: <a href=\"$approveUrl\">$approveUrl</a>
";

        $message = new \core\message\message();
        $message->component = 'moodle';
        $message->name = 'instantmessage';
        $message->replyto = $USER->email;
        $message->replytoname = $USER->fullname;
        $message->userfrom = $USER;
        $message->userto = $user;
        $message->subject = "ЛК Запрос - $USER->firstname $USER->lastname";
        $message->fullmessageformat = FORMAT_HTML;
        $message->fullmessage = html_to_text($messagehtml);
        $message->fullmessagehtml = $messagehtml;
        $message->notification = '1';
        $message->courseid = $course->id;

        try {
            $messageid = message_send($message);
            echo "Отправляем запрос администратору: $user->email<br>";
        } catch (Exception $e) {
            echo 'Что-то пошло не так: ',  $e->getMessage(), "<br>";
        }
    }
}

if ($noAdminsFound) {
    echo "Администраторов у данного курса не найдено. Обратитесь в деканат.";
}
