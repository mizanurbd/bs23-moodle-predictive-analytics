<?php
// This file is part of Moodle - http://moodle.org/
//
// Privacy provider for the Predictive Analytics Dashboard block.
// Required for Moodle 5.1 GDPR compliance.

namespace block_predictive_analytics\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for block_predictive_analytics.
 *
 * This block does not store any personal data. It calculates risk scores
 * in real-time from existing Moodle log data and does not persist them
 * in a separate table.
 */
class provider implements
    \core_privacy\local\metadata\null_provider {

    /**
     * Get the reason for declaring no user data is stored.
     *
     * @return string
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
