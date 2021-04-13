<?php

defined('MOODLE_INTERNAL') || die;

$capabilities = array(
    'mod/courselinks:view'   => array(
        'captype'       => 'read',
        'contextlevel'  => CONTEXT_MODULE,
        'archetypes' => array(
            'user' => CAP_ALLOW,
            'guest' => CAP_ALLOW
        )
    ),

    'mod/courselinks:addinstance' => array(
        'riskbitmask'   => RISK_XSS,
        'captype'       => 'write',
        'contextlevel'  => CONTEXT_COURSE,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'teacher'        => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'moodle/course:manageactivities'
    ),
);

