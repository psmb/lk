<?php

defined('MOODLE_INTERNAL') || die();
$tasks = [
    [
        'classname' => '\local_cohortautoenrol\task\sync',
        'blocking' => 0,
        'minute' => '*/15',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
];
