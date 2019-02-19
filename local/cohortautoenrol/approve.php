<?php
require_once '../../config.php';

$contextid = required_param('contextid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$amount = optional_param('amount', 0, PARAM_FLOAT);
$key = optional_param('key', false, PARAM_RAW);
$roleid = 9;

// Security.
if ($key) {
    if ($key !== getenv('AUTOENROL_KEY')) {
        throw new \Exception('Provided key is invalid!');
    }
} else {
    list($context, $course, $cm) = get_context_info_array($contextid);

    require_login($course, false, $cm);
    require_capability('moodle/role:assign', $context);
}

$file = $DB->get_record_sql("SELECT id, author, filename FROM {files} WHERE filesize > 0 AND contextid=$contextid AND component = 'mod_resource' LIMIT 1");

if (
    !$key || // if not $key provided then it's a manual approval by admin, so no need to check price
    (is_numeric($file->author) && (int)$file->author <= $amount) // amount paid is greater than amount stored in author field (yup, we hijacked it)
) {
    role_assign($roleid, $userid, $contextid);

    $context = $DB->get_record_sql("SELECT instanceid FROM {context} WHERE contextlevel=70 AND id=$contextid");
    $link = "https://kabinet.sfi.ru/mod/resource/view.php?id=$context->instanceid";
    echo "Запрос одобрен! <span style='display: none'>Техническая информация: $userid - $contextid - $link</span><br>";

    $user = $DB->get_record_sql("SELECT * FROM {user} where id = $userid");
    $admin = $DB->get_record_sql("SELECT * FROM {user} where id = 2");

    $message = new \core\message\message();
    $message->component = 'moodle';
    $message->name = 'instantmessage';
    $message->userfrom = $admin;
    $message->userto = $user;
    $message->subject = "Вам доступен файл - $file->filename";
    $message->fullmessageformat = FORMAT_PLAIN;
    $message->fullmessage = "
Вам доступен новый файл для скачивания: $link
";
    $message->notification = '1';

    try {
        $messageid = message_send($message);
        echo "Отправляем письмо пользователю: $user->email<br>";
    } catch (Exception $e) {
        echo 'Что-то пошло не так: ',  $e->getMessage(), "<br>";
    }

} else {
    throw new \Exception(sprintf('Not sufficient payment, paid %d, requested %s', $amount, (int)$file->author));
}
