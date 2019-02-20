<?php
require_once '../../config.php';

require_login();

$syscontext = context_system::instance();
require_capability('local/cohortautoenrol:analytics', $syscontext);

$PAGE->set_url('/local/cohortautoenrol/analytics.php');
$PAGE->set_context($syscontext);

$PAGE->set_heading('Пожертвования за лекции');
echo $OUTPUT->header();

$auth = base64_encode(getenv('SFI_API_AUTH'));
$context = stream_context_create([
    "http" => [
        "header" => "Authorization: Basic $auth"
    ]
]);

$json = file_get_contents('https://payments.sfi.ru/es-projection/lk-payments', false, $context);
$payments = json_decode($json);

echo "<table class='generaltable'>
    <thead>
        <tr>
            <th>Время платежа</th>
            <th>Сумма, руб.</th>
            <th>Имя файла</th>
            <th>Курс</th>
            <th>Подразделение</th>
            <th>Email</th>
            <th>Имя</th>
        </tr>
    </thead>";

$total = 0;

foreach(array_reverse($payments) as $payment) {
    $data = json_decode($payment->Data);

    $total += (float)$payment->Amount;
    
    list($context, $course, $cm) = get_context_info_array($data->contextid);
    $file = $DB->get_record_sql("SELECT * FROM {files} WHERE filesize > 0 AND contextid=$data->contextid AND component = 'mod_resource' LIMIT 1");
    $user = $DB->get_record_sql("SELECT * FROM {user} where id = $data->userid");
    // $context = $DB->get_record_sql("SELECT instanceid, path FROM {context} WHERE contextlevel=70 AND id=$data->contextid");
    $path = explode('/', $context->path);
    $topCategoryContextId = $path[3];
    $topCategoryContext = $DB->get_record_sql("SELECT * FROM {context} WHERE contextlevel=40 AND id=$topCategoryContextId");
    $topCategory = $DB->get_record_sql("SELECT * FROM {course_categories} WHERE id=$topCategoryContext->instanceid");

    echo "<tr>
        <td><a target='_blank' href=\"https://merchant.cloudpayments.ru/transactions/$payment->TransactionId\">$payment->DateTime</a></td>
        <td>$payment->Amount</td>
        <td>$file->filename</td>
        <td><a href=\"/course/view.php?id=$course->id\">$course->fullname</td>
        <td>$topCategory->name</td>
        <td>$user->email</td>
        <td><a href=\"/user/view.php?id=$user->id&course=$course->id\">$user->firstname $user->lastname</a></td>
    </tr>";
}

echo "</table>";

echo "Итого: $total руб.<br>";
echo "Количество платежей: " . count($payments);

echo $OUTPUT->footer();
