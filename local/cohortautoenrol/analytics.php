<?php
require_once '../../config.php';
# Sorry. There's something wrong with locales here
$monthNames = [
  'Январь',
  'Февраль',
  'Март',
  'Апрель',
  'Май',
  'Июнь',
  'Июль',
  'Август',
  'Сентябрь',
  'Октябрь',
  'Ноябрь',
  'Декабрь'
];

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
$paymentsByMonth = [];
foreach (array_reverse($payments) as $payment) {
    $date = date_parse($payment->DateTime);
    $key = $date['year'] . '-' . $date['month'];
    $paymentsByMonth[$key][] = $payment;
}

foreach ($paymentsByMonth as $date => $payments) {
    [$year, $month] = explode('-', $date);
    $monthName = $monthNames[$month - 1];
    echo "<h1>$monthName $year </h1>";
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

    foreach($payments as $payment) {
        $data = json_decode($payment->Data);

        $total += (float)$payment->Amount;

        if ($data->contextid === 'ltso') {
            $fileName = '-';
            $topCategoryName = 'ЛТСО';
            $courseText = '-';
            $userText = '';
            $userEmail = $payment->AccountId;
        } else {
                $file = $DB->get_record_sql("SELECT * FROM {files} WHERE filesize > 0 AND contextid=$data->contextid AND component = 'mod_resource' LIMIT 1", null, IGNORE_MISSING);
            $fileName = $file ? $file->filename : $data->fileName;
            $user = $DB->get_record_sql("SELECT * FROM {user} where id = $data->userid", null, IGNORE_MISSING);

            try {
            list($context, $course, $cm) = get_context_info_array($data->contextid);
            $path = explode('/', $context->path);
            $topCategoryContextId = $path[3];
            $topCategoryContext = $DB->get_record_sql("SELECT * FROM {context} WHERE contextlevel=40 AND id=$topCategoryContextId", null, IGNORE_MISSING);
            $topCategory = $DB->get_record_sql("SELECT * FROM {course_categories} WHERE id=$topCategoryContext->instanceid", null, IGNORE_MISSING);
                    $topCategoryName = $topCategory->name;
                    $userText = "<a href=\"/user/view.php?id=$user->id&course=$course->id\">$user->firstname $user->lastname</a>";
                    $courseText = "<a href=\"/course/view.php?id=$course->id\">$course->fullname</a>";
            } catch (Exception $e) {
            }
        }



        echo "<tr>
            <td><a target='_blank' href=\"https://merchant.cloudpayments.ru/transactions/$payment->TransactionId\">$payment->DateTime</a></td>
            <td>$payment->Amount</td>
            <td>$fileName</td>
            <td>$courseText</td>
            <td>$topCategoryName</td>
            <td>$userEmail</td>
            <td>$userText</td>
        </tr>";
    }

    echo "</table>";

    echo "Итого: $total руб.<br>";
    echo "Количество платежей: " . count($payments) . "<br><br>";
}


echo $OUTPUT->footer();
