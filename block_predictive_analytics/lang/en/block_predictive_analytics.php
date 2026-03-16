<?php
// This file is part of Moodle - http://moodle.org/
//
// English language strings for the Predictive Analytics Dashboard block.
// MoodleMoot Netherlands 2026 — Moodle Ecosystem Demo

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Predictive Analytics Dashboard';
$string['predictive_analytics:viewdashboard'] = 'View predictive analytics dashboard';
$string['predictive_analytics:addinstance'] = 'Add predictive analytics block';
$string['predictive_analytics:myaddinstance'] = 'Add predictive analytics block to Dashboard';
$string['nopermission'] = 'You do not have permission to view analytics data.';
$string['nostudentdata'] = 'No student data available yet. Enable the analytics model and run predictions first. See: Site Admin → Analytics → Analytics models → "Students at risk of dropping out" → Enable → Evaluate → Get predictions.';
$string['privacy:metadata'] = 'The Predictive Analytics Dashboard block calculates and displays risk scores in real-time from existing Moodle activity data (mdl_logstore_standard_log, mdl_assign_submission, mdl_grade_grades). It does not store additional personal data beyond what Moodle already collects.';
$string['silentdropper'] = 'Silent Dropper — good grades but declining engagement';
$string['riskfactor_logindecay'] = 'Login frequency decay';
$string['riskfactor_inactivity'] = 'Days since last activity';
$string['riskfactor_submissions'] = 'Assignment submission gaps';
$string['riskfactor_forum'] = 'Forum engagement decline';
