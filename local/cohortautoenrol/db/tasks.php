<?php

defined('MOODLE_INTERNAL') || die();
$tasks = [
    [
        'classname' => '\local_cohortautoenrol\task\sync',
        'blocking' => 0,
        'minute' => '30',
        'hour' => '1',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
];
