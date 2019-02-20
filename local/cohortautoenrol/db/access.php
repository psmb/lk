<?php
defined('MOODLE_INTERNAL') || die;
$capabilities = [
    'local/cohortautoenrol:analytics' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => []
    ],
];
