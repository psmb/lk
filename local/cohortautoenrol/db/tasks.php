<?php

defined('MOODLE_INTERNAL') || die();
$tasks = [
    [
        'classname' => '\local_cohortautoenrol\task\sync',
        'blocking' => 0,
        'minute' => '*',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
];
