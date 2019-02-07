<?php

$contextid = required_param('contextid', PARAM_INT);
$userid = required_param('userid', 0, PARAM_INT);
$roleid = 9;

list($context, $course, $cm) = get_context_info_array($contextid);

// Security.
require_login($course, false, $cm);
require_capability('moodle/role:assign', $context);

role_assign($roleid, $userid, $contextid);
