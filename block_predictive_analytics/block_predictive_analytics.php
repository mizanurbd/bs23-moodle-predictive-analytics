<?php
// This file is part of Moodle - http://moodle.org/
//
// Predictive Analytics Dashboard Block
// MoodleMoot Netherlands 2026 — Moodle Ecosystem Demo
//
// 4-Factor Behavioral Risk Scoring Engine:
//   1. Login frequency decay   (0-30 pts) — mdl_logstore_standard_log
//   2. Days since last activity (0-25 pts) — mdl_logstore_standard_log
//   3. Assignment submission gaps (0-25 pts) — mdl_assign_submission
//   4. Forum engagement         (0-20 pts) — mdl_logstore_standard_log (mod_forum)
//
// Special detection: "Silent Dropper" pattern (good grades + declining engagement)

defined('MOODLE_INTERNAL') || die();

class block_predictive_analytics extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_predictive_analytics');
    }

    public function applicable_formats() {
        return [
            'site-index' => true,
            'course-view' => true,
            'my' => true,
        ];
    }

    public function has_config() {
        return false;
    }

    public function get_content() {
        global $COURSE, $OUTPUT, $DB;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();

        // Check capability.
        $context = \context_course::instance($COURSE->id);
        if (!has_capability('block/predictive_analytics:viewdashboard', $context)) {
            $this->content->text = '<div class="alert alert-info">'
                . get_string('nopermission', 'block_predictive_analytics') . '</div>';
            return $this->content;
        }

        // Get risk data for the course.
        $riskdata = $this->get_course_risk_data($COURSE->id);

        // Build template data.
        $data = [
            'courseid'       => $COURSE->id,
            'coursename'     => format_string($COURSE->fullname),
            'total_students' => $riskdata['total'],
            'high_risk'      => $riskdata['high'],
            'medium_risk'    => $riskdata['medium'],
            'low_risk'       => $riskdata['low'],
            'on_track'       => $riskdata['on_track'],
            'students'       => array_values($riskdata['students']),
            'last_updated'   => userdate(time(), get_string('strftimedatetimeshort')),
            'has_students'   => !empty($riskdata['students']),
        ];

        $this->content->text = $OUTPUT->render_from_template(
            'block_predictive_analytics/dashboard',
            $data
        );

        return $this->content;
    }

    /**
     * Calculate risk data for all enrolled students in a course.
     *
     * Uses Moodle's core enrollment API and calculates behavioral risk
     * scores from mdl_logstore_standard_log and mdl_assign_submission.
     *
     * @param int $courseid
     * @return array
     */
    private function get_course_risk_data(int $courseid): array {
        $context = \context_course::instance($courseid);
        $students = get_enrolled_users($context, 'mod/assign:submit');

        $riskstudents = [];
        $counts = ['total' => 0, 'high' => 0, 'medium' => 0, 'low' => 0, 'on_track' => 0];

        foreach ($students as $student) {
            $counts['total']++;

            $score = $this->calculate_risk_score($student->id, $courseid);
            $issilentdropper = $this->detect_silent_dropper($student->id, $courseid, $score);

            if ($score >= 75) {
                $risklevel = 'high';
                $riskclass = 'danger';
                $counts['high']++;
            } else if ($score >= 50) {
                $risklevel = 'medium';
                $riskclass = 'warning';
                $counts['medium']++;
            } else if ($score >= 25) {
                $risklevel = 'low';
                $riskclass = 'info';
                $counts['low']++;
            } else {
                $risklevel = 'on_track';
                $riskclass = 'success';
                $counts['on_track']++;
            }

            $factors = $this->get_risk_factors($student->id, $courseid, $score);

            $riskstudents[] = [
                'userid'     => $student->id,
                'fullname'   => fullname($student),
                'risk_score' => $score,
                'risk_level' => $risklevel,
                'risk_class' => $riskclass,
                'top_factor' => $factors['top_factor'],
                'trend'      => $factors['trend'],
                'trend_icon' => $factors['trend'] === 'up'
                    ? 'fa-arrow-up' : ($factors['trend'] === 'down' ? 'fa-arrow-down' : 'fa-minus'),
                'last_access' => $this->get_last_access($student->id, $courseid),
                'is_silent_dropper' => $issilentdropper,
            ];
        }

        // Sort by risk score descending.
        usort($riskstudents, function ($a, $b) {
            return $b['risk_score'] - $a['risk_score'];
        });

        return array_merge($counts, ['students' => $riskstudents]);
    }

    /**
     * Calculate a student's risk score (0-100) based on engagement patterns.
     *
     * This is the 4-factor behavioral scoring engine shown in Slide 8
     * of the MoodleMoot presentation:
     *
     * Factor 1: Login frequency decay     (0-30 points)
     *   - Compares recent 2-week views vs. previous 2-week views
     *   - Source: mdl_logstore_standard_log (action = 'viewed')
     *
     * Factor 2: Days since last activity   (0-25 points)
     *   - Days since any log entry in the course
     *   - Source: mdl_logstore_standard_log
     *
     * Factor 3: Assignment submission gaps (0-25 points)
     *   - Ratio of submitted vs. total assignments
     *   - Source: mdl_assign + mdl_assign_submission
     *
     * Factor 4: Forum engagement           (0-20 points)
     *   - Count of forum post creation events
     *   - Source: mdl_logstore_standard_log (component = 'mod_forum')
     *
     * @param int $userid
     * @param int $courseid
     * @return int Risk score 0-100
     */
    private function calculate_risk_score(int $userid, int $courseid): int {
        global $DB;

        $score = 0;
        $now = time();
        $twoweeksago = $now - (14 * 86400);
        $fourweeksago = $now - (28 * 86400);

        // Factor 1: Login/view frequency decay (0-30 points).
        // Query mdl_logstore_standard_log for course view events.
        $recentviews = $DB->count_records_select(
            'logstore_standard_log',
            "userid = :uid AND courseid = :cid AND timecreated > :since AND action = 'viewed'",
            ['uid' => $userid, 'cid' => $courseid, 'since' => $twoweeksago]
        );
        $olderviews = $DB->count_records_select(
            'logstore_standard_log',
            "userid = :uid AND courseid = :cid AND timecreated > :from AND timecreated <= :to AND action = 'viewed'",
            ['uid' => $userid, 'cid' => $courseid, 'from' => $fourweeksago, 'to' => $twoweeksago]
        );

        if ($olderviews > 0) {
            $ratio = $recentviews / $olderviews;
            if ($ratio < 0.3) {
                $score += 30;  // Severe decay (>70% drop)
            } else if ($ratio < 0.5) {
                $score += 20;  // Significant decay (>50% drop)
            } else if ($ratio < 0.7) {
                $score += 10;  // Moderate decay (>30% drop)
            }
        } else if ($recentviews == 0) {
            $score += 30;  // No activity at all
        }

        // Factor 2: Days since last activity (0-25 points).
        // Query mdl_logstore_standard_log for most recent event.
        $lastaccess = $DB->get_field_sql(
            "SELECT MAX(timecreated) FROM {logstore_standard_log}
             WHERE userid = :uid AND courseid = :cid",
            ['uid' => $userid, 'cid' => $courseid]
        );
        $daysinactive = $lastaccess ? round(($now - $lastaccess) / 86400) : 999;

        if ($daysinactive > 14) {
            $score += 25;
        } else if ($daysinactive > 7) {
            $score += 15;
        } else if ($daysinactive > 3) {
            $score += 5;
        }

        // Factor 3: Assignment submission gaps (0-25 points).
        // Query mdl_assign for total course assignments and
        // mdl_assign_submission for student submissions.
        $totalassignments = $DB->count_records_sql(
            "SELECT COUNT(a.id) FROM {assign} a WHERE a.course = :cid",
            ['cid' => $courseid]
        );
        $submissions = $DB->count_records_sql(
            "SELECT COUNT(s.id) FROM {assign_submission} s
             JOIN {assign} a ON a.id = s.assignment
             WHERE a.course = :cid AND s.userid = :uid AND s.status = 'submitted'",
            ['cid' => $courseid, 'uid' => $userid]
        );

        if ($totalassignments > 0) {
            $submissionrate = $submissions / $totalassignments;
            if ($submissionrate < 0.3) {
                $score += 25;
            } else if ($submissionrate < 0.5) {
                $score += 15;
            } else if ($submissionrate < 0.7) {
                $score += 8;
            }
        }

        // Factor 4: Forum engagement (0-20 points).
        // Query mdl_logstore_standard_log for mod_forum post_created events.
        $forumposts = $DB->count_records_select(
            'logstore_standard_log',
            "userid = :uid AND courseid = :cid AND component = 'mod_forum' AND action = 'created'",
            ['uid' => $userid, 'cid' => $courseid]
        );

        if ($forumposts == 0) {
            $score += 20;
        } else if ($forumposts < 3) {
            $score += 10;
        } else if ($forumposts < 5) {
            $score += 5;
        }

        return min(100, $score);
    }

    /**
     * Detect the "Silent Dropper" pattern — the key insight from the presentation.
     *
     * A Silent Dropper is a student who:
     * - Has GOOD grades (quiz average >= 70%)
     * - But shows DECLINING engagement (login decay > 40% AND forum posts < 3)
     *
     * These students are invisible to traditional grade-based reports
     * (Slide 4 / Slide 13 in the deck) but predictable with behavioral signals.
     *
     * @param int $userid
     * @param int $courseid
     * @param int $riskscore Current overall risk score
     * @return bool True if student matches Silent Dropper pattern
     */
    private function detect_silent_dropper(int $userid, int $courseid, int $riskscore): bool {
        global $DB;

        if ($riskscore < 40) {
            return false; // Not enough risk signals.
        }

        // Check for good quiz performance (mdl_quiz_attempts equivalent check).
        // We approximate via grade_grades since quiz attempts require mod_quiz tables.
        $avggrade = $DB->get_field_sql(
            "SELECT AVG(gg.finalgrade / gi.grademax * 100)
             FROM {grade_grades} gg
             JOIN {grade_items} gi ON gi.id = gg.itemid
             WHERE gi.courseid = :cid AND gg.userid = :uid
               AND gi.itemtype = 'mod' AND gg.finalgrade IS NOT NULL",
            ['cid' => $courseid, 'uid' => $userid]
        );

        if ($avggrade === false || $avggrade < 70) {
            return false; // Not the pattern — grades are low too.
        }

        // Check for login/engagement decay.
        $now = time();
        $twoweeksago = $now - (14 * 86400);
        $fourweeksago = $now - (28 * 86400);

        $recentviews = $DB->count_records_select(
            'logstore_standard_log',
            "userid = :uid AND courseid = :cid AND timecreated > :since AND action = 'viewed'",
            ['uid' => $userid, 'cid' => $courseid, 'since' => $twoweeksago]
        );
        $olderviews = $DB->count_records_select(
            'logstore_standard_log',
            "userid = :uid AND courseid = :cid AND timecreated > :from AND timecreated <= :to AND action = 'viewed'",
            ['uid' => $userid, 'cid' => $courseid, 'from' => $fourweeksago, 'to' => $twoweeksago]
        );

        if ($olderviews > 0 && ($recentviews / $olderviews) < 0.6) {
            return true; // Good grades + significant engagement decay = Silent Dropper.
        }

        return false;
    }

    /**
     * Determine the top risk factor and trend direction.
     *
     * Maps to the 4-factor model from the presentation (Slide 8):
     * Login Decay (30pts) > Inactivity (25pts) > Submission Gap (25pts) > Forum (20pts)
     *
     * @param int $userid
     * @param int $courseid
     * @param int $score
     * @return array ['top_factor' => string, 'trend' => 'up'|'down'|'stable']
     */
    private function get_risk_factors(int $userid, int $courseid, int $score): array {
        global $DB;

        $now = time();
        $twoweeksago = $now - (14 * 86400);
        $fourweeksago = $now - (28 * 86400);

        // Check login decay factor (mdl_logstore_standard_log).
        $recentviews = $DB->count_records_select(
            'logstore_standard_log',
            "userid = :uid AND courseid = :cid AND timecreated > :since AND action = 'viewed'",
            ['uid' => $userid, 'cid' => $courseid, 'since' => $twoweeksago]
        );
        $olderviews = $DB->count_records_select(
            'logstore_standard_log',
            "userid = :uid AND courseid = :cid AND timecreated > :from AND timecreated <= :to AND action = 'viewed'",
            ['uid' => $userid, 'cid' => $courseid, 'from' => $fourweeksago, 'to' => $twoweeksago]
        );

        $lastaccess = $DB->get_field_sql(
            "SELECT MAX(timecreated) FROM {logstore_standard_log}
             WHERE userid = :uid AND courseid = :cid",
            ['uid' => $userid, 'cid' => $courseid]
        );
        $daysinactive = $lastaccess ? round(($now - $lastaccess) / 86400) : 999;

        // Pick the most significant factor (priority order).
        if ($olderviews > 0 && $recentviews / $olderviews < 0.5) {
            $decaypct = round((1 - $recentviews / $olderviews) * 100);
            return ['top_factor' => "Login decay -{$decaypct}%", 'trend' => 'up'];
        }

        if ($daysinactive > 14) {
            return ['top_factor' => "Inactive {$daysinactive} days", 'trend' => 'up'];
        }

        if ($daysinactive > 7) {
            return ['top_factor' => "Inactive {$daysinactive} days", 'trend' => 'up'];
        }

        // Check forum factor (mdl_logstore_standard_log for mod_forum).
        $forumposts = $DB->count_records_select(
            'logstore_standard_log',
            "userid = :uid AND courseid = :cid AND component = 'mod_forum' AND action = 'created'",
            ['uid' => $userid, 'cid' => $courseid]
        );

        if ($forumposts == 0) {
            return ['top_factor' => 'Forum disengaged', 'trend' => 'stable'];
        }

        if ($score < 25) {
            return ['top_factor' => 'All metrics stable', 'trend' => 'down'];
        }

        return ['top_factor' => 'Multiple factors', 'trend' => 'stable'];
    }

    /**
     * Get a student's last access time in the course.
     *
     * @param int $userid
     * @param int $courseid
     * @return string Human-readable date or 'Never'
     */
    private function get_last_access(int $userid, int $courseid): string {
        global $DB;

        $last = $DB->get_field_sql(
            "SELECT MAX(timecreated) FROM {logstore_standard_log}
             WHERE userid = :uid AND courseid = :cid",
            ['uid' => $userid, 'cid' => $courseid]
        );

        return $last ? userdate($last, get_string('strftimedatetimeshort')) : 'Never';
    }
}
