<?php
// This file is part of Moodle - http://moodle.org/
//
// Predictive Analytics Dashboard Block
// MoodleMoot Netherlands 2026 — Moodle Ecosystem Demo
// 4-Factor Behavioral Risk Scoring via Moodle Events API

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'block_predictive_analytics';
$plugin->version = 2026030100;
$plugin->requires = 2022112800; // Moodle 4.1+ (broad compatibility, works on MySQL 5.7+)
$plugin->maturity = MATURITY_BETA;
$plugin->release = '1.0.1-beta';
