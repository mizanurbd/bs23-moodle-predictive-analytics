<?php
/**
 * MoodleMoot Netherlands 2026 — Demo Data Generator
 * AI for Learning Analytics and Predictive Insights
 * Moodle Ecosystem Demo — 4-Factor Behavioral Risk Scoring
 *
 * Generates 200 students with realistic engagement patterns across 4 behavioral
 * profiles, enrolled in "Introduction to Data Science" (DS101).
 *
 * Compatible with Moodle 5.0+
 *
 * Usage:
 *   php admin/cli/populate_demo_data.php
 *
 * For Bitnami Docker:
 *   docker cp populate_demo_data.php <container>:/bitnami/moodle/admin/cli/
 *   docker exec -u www-data <container> php /bitnami/moodle/admin/cli/populate_demo_data.php
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/lib/clilib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/lib/enrollib.php');
require_once($CFG->dirroot . '/lib/gradelib.php');

cli_heading('MoodleMoot Demo Data Generator — Moodle Analytics Ecosystem');
cli_writeln('Moodle version: ' . $CFG->release);
cli_writeln('');

// ============================================================
// CONFIGURATION
// ============================================================
$CONFIG = [
    'num_students'       => 200,
    'course_name'        => 'Introduction to Data Science',
    'course_shortname'   => 'DS101-DEMO',
    'category_id'        => 1,       // Default category
    'start_date'         => strtotime('-16 weeks'),
    'num_weeks'          => 16,
    'num_assignments'    => 8,
    'num_quizzes'        => 6,
];

// ============================================================
// STUDENT BEHAVIOR PROFILES
// ============================================================
// Each student gets a profile that determines their engagement patterns.
// The "silent_dropper" profile is KEY — this is "Student B" from the
// presentation: good grades, but sharply declining engagement.

$PROFILES = [
    'strong' => [
        'weight' => 0.35,
        'login_base' => [35, 50],       // logins per week
        'login_trend' => [0.95, 1.05],  // multiplier per week (stable)
        'submission_rate' => [0.90, 1.0],
        'late_rate' => [0.0, 0.1],
        'forum_posts_week' => [1, 4],
        'quiz_base' => [75, 95],
        'quiz_trend' => [0.98, 1.03],
        'session_duration_base' => [30, 60], // minutes
        'session_trend' => [0.95, 1.05],
        'resource_views' => [15, 30],
        'dropout_prob' => 0.02,
    ],
    'average' => [
        'weight' => 0.30,
        'login_base' => [20, 35],
        'login_trend' => [0.90, 1.02],
        'submission_rate' => [0.70, 0.90],
        'late_rate' => [0.1, 0.3],
        'forum_posts_week' => [0, 2],
        'quiz_base' => [55, 75],
        'quiz_trend' => [0.95, 1.02],
        'session_duration_base' => [15, 35],
        'session_trend' => [0.90, 1.0],
        'resource_views' => [8, 18],
        'dropout_prob' => 0.10,
    ],
    'at_risk' => [
        'weight' => 0.20,
        'login_base' => [15, 30],
        'login_trend' => [0.75, 0.90],  // declining
        'submission_rate' => [0.50, 0.75],
        'late_rate' => [0.2, 0.5],
        'forum_posts_week' => [0, 1],
        'quiz_base' => [40, 65],
        'quiz_trend' => [0.88, 0.96],   // declining scores
        'session_duration_base' => [10, 25],
        'session_trend' => [0.75, 0.90],
        'resource_views' => [3, 10],
        'dropout_prob' => 0.40,
    ],
    'silent_dropper' => [
        // THIS IS "STUDENT B" — the counterintuitive dropout
        // Good grades BUT sharply declining engagement
        'weight' => 0.15,
        'login_base' => [25, 40],
        'login_trend' => [0.60, 0.78],  // SHARP decline in logins
        'submission_rate' => [0.80, 0.95], // Still submitting!
        'late_rate' => [0.05, 0.2],
        'forum_posts_week' => [0, 1],   // Minimal social engagement
        'quiz_base' => [70, 88],        // DECENT grades
        'quiz_trend' => [0.92, 1.0],    // Grades barely declining
        'session_duration_base' => [25, 45],
        'session_trend' => [0.55, 0.72], // SHARP session duration decline
        'resource_views' => [5, 12],
        'dropout_prob' => 0.65,          // High dropout despite "looking fine"
    ],
];

// ============================================================
// DUTCH / INTERNATIONAL NAMES (for MoodleMoot Netherlands)
// ============================================================
$firstnames = [
    'Emma', 'Liam', 'Sophie', 'Noah', 'Julia', 'Daan', 'Tess', 'Sem',
    'Sara', 'Lucas', 'Eva', 'Finn', 'Anna', 'Jesse', 'Lisa', 'Milan',
    'Maria', 'Ahmed', 'Fatima', 'Jan', 'Pieter', 'Bram', 'Fleur', 'Lars',
    'Sanne', 'Thomas', 'Iris', 'Max', 'Noa', 'Luuk', 'Mila', 'Tim',
    'Lotte', 'Stijn', 'Isa', 'Ruben', 'Vera', 'Thijs', 'Roos', 'Jasper',
    'Yusuf', 'Aisha', 'Mohammed', 'Priya', 'Wei', 'Mei', 'Carlos', 'Ana',
    'Daniyal', 'Olivia', 'Jayden', 'Zoey', 'Levi', 'Nina', 'Guus', 'Fenna',
];

$lastnames = [
    'de Jong', 'Jansen', 'de Vries', 'van den Berg', 'van Dijk',
    'Bakker', 'Janssen', 'Visser', 'Smit', 'Meijer', 'de Boer',
    'Mulder', 'de Groot', 'Bos', 'Vos', 'Peters', 'Hendriks',
    'van Leeuwen', 'Dekker', 'Brouwer', 'de Wit', 'Dijkstra',
    'Smeets', 'de Graaf', 'van der Meer', 'Schouten', 'van Beek',
    'Kumar', 'Patel', 'Al-Hassan', 'Chen', 'Kim', 'Santos', 'Okafor',
    'van Houten', 'Vermeer', 'Bosman', 'Willems', 'van der Linden',
];

// ============================================================
// HELPER FUNCTIONS
// ============================================================

function rand_between(float $min, float $max): float {
    return $min + (mt_rand() / mt_getrandmax()) * ($max - $min);
}

function pick_profile(array $profiles): array {
    $r = mt_rand() / mt_getrandmax();
    $cumulative = 0;
    foreach ($profiles as $name => $profile) {
        $cumulative += $profile['weight'];
        if ($r <= $cumulative) {
            return [$name, $profile];
        }
    }
    return [array_key_last($profiles), end($profiles)];
}

// ============================================================
// CHECK FOR EXISTING DEMO DATA
// ============================================================
$existing = $DB->get_record('course', ['shortname' => $CONFIG['course_shortname']]);
if ($existing) {
    cli_error("Course '{$CONFIG['course_shortname']}' already exists (ID: {$existing->id}). "
        . "Delete it first or change the shortname in the config.");
}

// ============================================================
// CREATE COURSE
// ============================================================
cli_writeln('Creating course: ' . $CONFIG['course_name']);

$coursedata = new stdClass();
$coursedata->fullname = $CONFIG['course_name'];
$coursedata->shortname = $CONFIG['course_shortname'];
$coursedata->category = $CONFIG['category_id'];
$coursedata->format = 'weeks';
$coursedata->numsections = $CONFIG['num_weeks'];
$coursedata->startdate = $CONFIG['start_date'];
$coursedata->enablecompletion = 1;
$coursedata->visible = 1;
$coursedata->summary = 'A hands-on course covering data analysis, statistics, and machine learning fundamentals. '
    . 'Demo course for MoodleMoot Netherlands 2026 — Predictive Analytics with Moodle Events API.';
$coursedata->summaryformat = FORMAT_HTML;

$course = create_course($coursedata);
$coursecontext = context_course::instance($course->id);
cli_writeln("  Course created: ID={$course->id}");

// ============================================================
// CREATE STUDENTS & ENROL
// ============================================================
cli_writeln("Creating {$CONFIG['num_students']} students...");

$studentrole = $DB->get_record('role', ['shortname' => 'student']);
if (!$studentrole) {
    cli_error('Student role not found.');
}

// Ensure manual enrolment plugin is active.
$enrolplugin = enrol_get_plugin('manual');
$enrolinstances = $DB->get_records('enrol', ['courseid' => $course->id, 'enrol' => 'manual']);
if (empty($enrolinstances)) {
    $enrolplugin->add_instance($course);
    $enrolinstances = $DB->get_records('enrol', ['courseid' => $course->id, 'enrol' => 'manual']);
}
$enrolinstance = reset($enrolinstances);

$studentdata = []; // For CSV export
$profilecounts = [];

for ($s = 1; $s <= $CONFIG['num_students']; $s++) {
    list($profilename, $profile) = pick_profile($PROFILES);
    $profilecounts[$profilename] = ($profilecounts[$profilename] ?? 0) + 1;

    // Create user.
    $user = new stdClass();
    $user->username = 'demostu' . str_pad($s, 3, '0', STR_PAD_LEFT);
    $user->password = hash_internal_user_password('Demo2026!');
    $user->firstname = $firstnames[array_rand($firstnames)];
    $user->lastname = $lastnames[array_rand($lastnames)];
    $user->email = $user->username . '@moodlemoot-demo.nl';
    $user->confirmed = 1;
    $user->mnethostid = $CFG->mnet_localhost_id;
    $user->auth = 'manual';
    $user->timecreated = $CONFIG['start_date'];
    $user->timemodified = time();

    $user->id = user_create_user($user, false, false);

    // Enrol in course.
    $enrolplugin->enrol_user($enrolinstance, $user->id, $studentrole->id, $CONFIG['start_date']);

    // Determine dropout behavior.
    $dropsout = (mt_rand() / mt_getrandmax()) < $profile['dropout_prob'];
    $dropoutweek = $dropsout ? mt_rand(8, 14) : $CONFIG['num_weeks'];

    // ---- Generate weekly activity logs ----
    $totallogins = 0;
    $totalforumposts = 0;
    $totalsessionminutes = 0;
    $totalresourceviews = 0;

    for ($week = 1; $week <= min($dropoutweek, $CONFIG['num_weeks']); $week++) {
        $weekstart = $CONFIG['start_date'] + (($week - 1) * 7 * 86400);
        $decay = pow(rand_between($profile['login_trend'][0], $profile['login_trend'][1]), $week - 1);

        // Logins this week.
        $loginsweek = max(0, round(rand_between($profile['login_base'][0], $profile['login_base'][1]) * $decay));
        $totallogins += $loginsweek;

        // Generate login log entries.
        for ($l = 0; $l < $loginsweek; $l++) {
            $logtime = $weekstart + mt_rand(0, 6 * 86400) + mt_rand(28800, 79200);
            $log = new stdClass();
            $log->eventname = '\\core\\event\\user_loggedin';
            $log->component = 'core';
            $log->action = 'loggedin';
            $log->target = 'user';
            $log->objecttable = 'user';
            $log->objectid = $user->id;
            $log->userid = $user->id;
            $log->courseid = 0;
            $log->contextid = 1;
            $log->contextlevel = CONTEXT_SYSTEM;
            $log->contextinstanceid = 0;
            $log->timecreated = $logtime;
            $log->origin = 'web';
            $log->ip = '10.0.' . mt_rand(1, 254) . '.' . mt_rand(1, 254);
            $DB->insert_record('logstore_standard_log', $log);
        }

        // Course view events.
        $viewsweek = max(0, round(rand_between($profile['resource_views'][0], $profile['resource_views'][1]) * $decay));
        $totalresourceviews += $viewsweek;

        for ($v = 0; $v < $viewsweek; $v++) {
            $viewtime = $weekstart + mt_rand(0, 6 * 86400) + mt_rand(28800, 79200);
            $log = new stdClass();
            $log->eventname = '\\core\\event\\course_viewed';
            $log->component = 'core';
            $log->action = 'viewed';
            $log->target = 'course';
            $log->objecttable = 'course';
            $log->objectid = $course->id;
            $log->userid = $user->id;
            $log->courseid = $course->id;
            $log->contextid = $coursecontext->id;
            $log->contextlevel = CONTEXT_COURSE;
            $log->contextinstanceid = $course->id;
            $log->timecreated = $viewtime;
            $log->origin = 'web';
            $log->ip = '10.0.' . mt_rand(1, 254) . '.' . mt_rand(1, 254);
            $DB->insert_record('logstore_standard_log', $log);
        }

        // Forum activity.
        $postsweek = max(0, round(rand_between($profile['forum_posts_week'][0], $profile['forum_posts_week'][1]) * $decay));
        $totalforumposts += $postsweek;

        for ($p = 0; $p < $postsweek; $p++) {
            $posttime = $weekstart + mt_rand(0, 6 * 86400) + mt_rand(28800, 79200);
            $log = new stdClass();
            $log->eventname = '\\mod_forum\\event\\post_created';
            $log->component = 'mod_forum';
            $log->action = 'created';
            $log->target = 'post';
            $log->objecttable = 'forum_posts';
            $log->objectid = mt_rand(1, 9999);
            $log->userid = $user->id;
            $log->courseid = $course->id;
            $log->contextid = $coursecontext->id;
            $log->contextlevel = CONTEXT_COURSE;
            $log->contextinstanceid = $course->id;
            $log->timecreated = $posttime;
            $log->origin = 'web';
            $log->ip = '10.0.' . mt_rand(1, 254) . '.' . mt_rand(1, 254);
            $DB->insert_record('logstore_standard_log', $log);
        }

        // Session duration tracking.
        $sessiondecay = pow(rand_between($profile['session_trend'][0], $profile['session_trend'][1]), $week - 1);
        $sessionmin = max(2, round(rand_between($profile['session_duration_base'][0], $profile['session_duration_base'][1]) * $sessiondecay));
        $totalsessionminutes += $sessionmin * max(1, $loginsweek);
    }

    // ---- Generate quiz scores ----
    $quizscores = [];
    for ($q = 1; $q <= $CONFIG['num_quizzes']; $q++) {
        if ($q * 2.5 > $dropoutweek) {
            break;
        }
        $quizdecay = pow(rand_between($profile['quiz_trend'][0], $profile['quiz_trend'][1]), $q - 1);
        $score = max(0, min(100, round(rand_between($profile['quiz_base'][0], $profile['quiz_base'][1]) * $quizdecay)));
        $quizscores[] = $score;
    }

    // ---- Count assignment submissions ----
    $submissions = 0;
    $latesubmissions = 0;
    for ($a = 1; $a <= $CONFIG['num_assignments']; $a++) {
        if ($a * 2 > $dropoutweek) {
            break;
        }
        if ((mt_rand() / mt_getrandmax()) < rand_between($profile['submission_rate'][0], $profile['submission_rate'][1])) {
            $submissions++;
            if ((mt_rand() / mt_getrandmax()) < rand_between($profile['late_rate'][0], $profile['late_rate'][1])) {
                $latesubmissions++;
            }
        }
    }

    // ---- Collect CSV row ----
    $avgquiz = count($quizscores) > 0 ? round(array_sum($quizscores) / count($quizscores), 1) : 0;
    $logintrend = $CONFIG['num_weeks'] > 1
        ? round(($totallogins / max(1, $dropoutweek)) * rand_between($profile['login_trend'][0], $profile['login_trend'][1]), 3)
        : 0;

    $studentdata[] = [
        'student_id' => $user->id,
        'username' => $user->username,
        'name' => $user->firstname . ' ' . $user->lastname,
        'profile_type' => $profilename,
        'total_logins' => $totallogins,
        'logins_per_week' => round($totallogins / max(1, $dropoutweek), 1),
        'login_trend_slope' => $logintrend,
        'total_forum_posts' => $totalforumposts,
        'assignment_submission_rate' => round($submissions / $CONFIG['num_assignments'], 2),
        'late_submission_rate' => $submissions > 0 ? round($latesubmissions / $submissions, 2) : 0,
        'quiz_avg_score' => $avgquiz,
        'quiz_score_trend' => count($quizscores) >= 2 ? round(end($quizscores) - $quizscores[0], 1) : 0,
        'total_session_minutes' => $totalsessionminutes,
        'avg_session_minutes' => $totallogins > 0 ? round($totalsessionminutes / $totallogins, 1) : 0,
        'resource_views' => $totalresourceviews,
        'days_since_last_activity' => $dropsout ? mt_rand(14, 42) : mt_rand(0, 5),
        'weeks_active' => min($dropoutweek, $CONFIG['num_weeks']),
        'dropped_out' => $dropsout ? 1 : 0,
    ];

    if ($s % 25 == 0) {
        cli_writeln("  Student $s/{$CONFIG['num_students']} — {$user->firstname} {$user->lastname} ({$profilename}"
            . ($dropsout ? ", drops out week {$dropoutweek}" : '') . ')');
    }
}

// ============================================================
// EXPORT CSV (for Jupyter notebook demo)
// ============================================================
$csvpath = $CFG->dataroot . '/moodle_student_data.csv';
$fp = fopen($csvpath, 'w');
fputcsv($fp, array_keys($studentdata[0]));
foreach ($studentdata as $row) {
    fputcsv($fp, $row);
}
fclose($fp);

// ============================================================
// SUMMARY
// ============================================================
cli_writeln('');
cli_writeln('========================================');
cli_writeln('  DEMO DATA GENERATION COMPLETE');
cli_writeln('========================================');
cli_writeln('');
cli_writeln("Course: {$CONFIG['course_name']} (ID: {$course->id})");
cli_writeln("Students created: {$CONFIG['num_students']}");
cli_writeln('');
cli_writeln('Profile distribution:');
foreach ($profilecounts as $type => $count) {
    $pct = round($count / $CONFIG['num_students'] * 100);
    cli_writeln("  {$type}: {$count} ({$pct}%)");
}

$dropoutcount = array_sum(array_column($studentdata, 'dropped_out'));
cli_writeln('');
cli_writeln("Total dropouts: {$dropoutcount} (" . round($dropoutcount / $CONFIG['num_students'] * 100) . '%)');
cli_writeln("CSV exported to: {$csvpath}");
cli_writeln('');
cli_writeln('Next steps:');
cli_writeln('  1. Enable Analytics: Site Admin -> Analytics -> Analytics settings');
cli_writeln('  2. Enable model: Site Admin -> Analytics -> Analytics models -> "Students at risk of dropping out"');
cli_writeln('  3. Run: "Evaluate model" then "Get predictions"');
cli_writeln('  4. Install the predictive_analytics block on the course page');
cli_writeln('');
cli_writeln('Done!');
