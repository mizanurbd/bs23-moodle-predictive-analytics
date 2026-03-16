<?php
/**
 * MoodleMoot Netherlands 2026 — Demo Data Generator (WEB VERSION)
 * AI for Learning Analytics and Predictive Insights
 * Moodle Ecosystem Demo — 4-Factor Behavioral Risk Scoring
 *
 * WEB-ACCESSIBLE VERSION for cPanel hosting (no SSH required).
 * Must be accessed while logged in as Moodle admin.
 *
 * Compatible with Moodle 5.0+
 *
 * INSTALLATION:
 *   Upload to: public_html/moodle/admin/populate_demo_data_web.php
 *   Access via: https://brainstation-23.jp/moodle/admin/populate_demo_data_web.php
 *
 * SECURITY: Requires admin login. Confirm token prevents accidental execution.
 * DELETE THIS FILE AFTER USE.
 */

require(__DIR__ . '/../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/lib/enrollib.php');
require_once($CFG->dirroot . '/lib/gradelib.php');
require_once($CFG->libdir . '/adminlib.php');

// Must be admin.
require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

// Increase limits for data generation.
set_time_limit(600);
ini_set('memory_limit', '512M');
@ini_set('max_execution_time', '600');

// Confirm token to prevent accidental runs.
$confirm = optional_param('confirm', '', PARAM_ALPHANUM);
$action = optional_param('action', '', PARAM_ALPHA);

$PAGE->set_context($context);
$PAGE->set_url('/admin/populate_demo_data_web.php');
$PAGE->set_title('MoodleMoot Demo Data Generator');
$PAGE->set_heading('MoodleMoot NL 2026 — Demo Data Generator');

// ============================================================
// CONFIGURATION
// ============================================================
$CONFIG = [
    'num_students'       => 200,
    'course_name'        => 'Introduction to Data Science',
    'course_shortname'   => 'DS101-DEMO',
    'category_id'        => 1,
    'start_date'         => strtotime('-16 weeks'),
    'num_weeks'          => 16,
    'num_assignments'    => 8,
    'num_quizzes'        => 6,
];

// ============================================================
// STUDENT BEHAVIOR PROFILES
// ============================================================
$PROFILES = [
    'strong' => [
        'weight' => 0.35,
        'login_base' => [35, 50],
        'login_trend' => [0.95, 1.05],
        'submission_rate' => [0.90, 1.0],
        'late_rate' => [0.0, 0.1],
        'forum_posts_week' => [1, 4],
        'quiz_base' => [75, 95],
        'quiz_trend' => [0.98, 1.03],
        'session_duration_base' => [30, 60],
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
        'login_trend' => [0.75, 0.90],
        'submission_rate' => [0.50, 0.75],
        'late_rate' => [0.2, 0.5],
        'forum_posts_week' => [0, 1],
        'quiz_base' => [40, 65],
        'quiz_trend' => [0.88, 0.96],
        'session_duration_base' => [10, 25],
        'session_trend' => [0.75, 0.90],
        'resource_views' => [3, 10],
        'dropout_prob' => 0.40,
    ],
    'silent_dropper' => [
        'weight' => 0.15,
        'login_base' => [25, 40],
        'login_trend' => [0.60, 0.78],
        'submission_rate' => [0.80, 0.95],
        'late_rate' => [0.05, 0.2],
        'forum_posts_week' => [0, 1],
        'quiz_base' => [70, 88],
        'quiz_trend' => [0.92, 1.0],
        'session_duration_base' => [25, 45],
        'session_trend' => [0.55, 0.72],
        'resource_views' => [5, 12],
        'dropout_prob' => 0.65,
    ],
];

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
// SHOW CONFIRMATION PAGE
// ============================================================
if ($confirm !== 'MOODLEMOOT2026') {
    echo $OUTPUT->header();
    echo '<div style="max-width: 800px; margin: 30px auto; font-family: -apple-system, sans-serif;">';
    echo '<div style="background: linear-gradient(135deg, #0F6CBF 0%, #1177CC 100%); color: white; padding: 30px; border-radius: 12px 12px 0 0;">';
    echo '<h2 style="margin: 0 0 10px 0;">&#x1f393; MoodleMoot Netherlands 2026</h2>';
    echo '<p style="margin: 0; opacity: 0.9;">AI for Learning Analytics — Demo Data Generator</p>';
    echo '</div>';

    echo '<div style="background: #fff; padding: 30px; border: 1px solid #dee2e6; border-top: none; border-radius: 0 0 12px 12px;">';

    // Check existing.
    $existing = $DB->get_record('course', ['shortname' => $CONFIG['course_shortname']]);
    if ($existing) {
        echo '<div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 8px; margin-bottom: 20px;">';
        echo '<strong>&#x26a0;&#xfe0f; Warning:</strong> Course "' . $CONFIG['course_shortname'] . '" already exists (ID: ' . $existing->id . '). ';
        echo 'Delete it first via Site Admin &gt; Courses before running this generator.';
        echo '</div>';
    }

    echo '<h3>This script will create:</h3>';
    echo '<table style="width: 100%; border-collapse: collapse; margin: 15px 0;">';
    echo '<tr style="background: #f8f9fa;"><td style="padding: 10px; border: 1px solid #dee2e6;"><strong>Course</strong></td><td style="padding: 10px; border: 1px solid #dee2e6;">' . $CONFIG['course_name'] . ' (' . $CONFIG['course_shortname'] . ')</td></tr>';
    echo '<tr><td style="padding: 10px; border: 1px solid #dee2e6;"><strong>Students</strong></td><td style="padding: 10px; border: 1px solid #dee2e6;">' . $CONFIG['num_students'] . ' with 4 behavioral profiles</td></tr>';
    echo '<tr style="background: #f8f9fa;"><td style="padding: 10px; border: 1px solid #dee2e6;"><strong>Duration</strong></td><td style="padding: 10px; border: 1px solid #dee2e6;">' . $CONFIG['num_weeks'] . ' weeks of activity data</td></tr>';
    echo '<tr><td style="padding: 10px; border: 1px solid #dee2e6;"><strong>Log Records</strong></td><td style="padding: 10px; border: 1px solid #dee2e6;">~80,000+ (logins, views, forum posts)</td></tr>';
    echo '<tr style="background: #f8f9fa;"><td style="padding: 10px; border: 1px solid #dee2e6;"><strong>Assignments</strong></td><td style="padding: 10px; border: 1px solid #dee2e6;">' . $CONFIG['num_assignments'] . ' with submission tracking</td></tr>';
    echo '<tr><td style="padding: 10px; border: 1px solid #dee2e6;"><strong>Quizzes</strong></td><td style="padding: 10px; border: 1px solid #dee2e6;">' . $CONFIG['num_quizzes'] . ' with grade trajectories</td></tr>';
    echo '</table>';

    echo '<h3>Student Profiles:</h3>';
    echo '<div style="display: flex; gap: 10px; flex-wrap: wrap; margin: 15px 0;">';
    echo '<span style="background: #d4edda; padding: 8px 15px; border-radius: 20px; font-size: 14px;"><strong>Strong</strong> 35%</span>';
    echo '<span style="background: #cce5ff; padding: 8px 15px; border-radius: 20px; font-size: 14px;"><strong>Average</strong> 30%</span>';
    echo '<span style="background: #f8d7da; padding: 8px 15px; border-radius: 20px; font-size: 14px;"><strong>At-Risk</strong> 20%</span>';
    echo '<span style="background: #fff3cd; padding: 8px 15px; border-radius: 20px; font-size: 14px;"><strong>Silent Dropper</strong> 15%</span>';
    echo '</div>';

    echo '<div style="background: #e7f0fa; padding: 15px; border-radius: 8px; margin: 20px 0;">';
    echo '<strong>&#x23f1;&#xfe0f; Estimated time:</strong> 2-5 minutes depending on server performance. ';
    echo 'The page will show progress updates during generation.';
    echo '</div>';

    if (!$existing) {
        echo '<div style="text-align: center; margin-top: 25px;">';
        echo '<a href="?confirm=MOODLEMOOT2026" style="background: #F98012; color: white; padding: 15px 40px; border-radius: 8px; text-decoration: none; font-size: 18px; font-weight: bold; display: inline-block;">';
        echo '&#x1f680; Generate Demo Data Now</a>';
        echo '</div>';
    }

    echo '</div></div>';
    echo $OUTPUT->footer();
    exit;
}

// ============================================================
// EXECUTE DATA GENERATION (with real-time progress output)
// ============================================================
// Use chunked output so the browser shows progress.
ob_implicit_flush(true);
if (ob_get_level()) ob_end_flush();
header('Content-Type: text/html; charset=utf-8');
header('X-Accel-Buffering: no');

echo '<!DOCTYPE html><html><head><meta charset="utf-8">';
echo '<title>Generating Demo Data...</title>';
echo '<style>
body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; max-width: 800px; margin: 30px auto; padding: 20px; background: #f5f5f5; }
.header { background: linear-gradient(135deg, #0F6CBF 0%, #1177CC 100%); color: white; padding: 25px; border-radius: 12px; margin-bottom: 20px; }
.log { background: #1D2125; color: #A8E6CF; padding: 20px; border-radius: 8px; font-family: "Courier New", monospace; font-size: 14px; max-height: 500px; overflow-y: auto; white-space: pre-wrap; line-height: 1.6; }
.success { background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; border-radius: 8px; margin-top: 20px; }
.error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 20px; border-radius: 8px; margin-top: 20px; }
.progress { color: #F98012; font-weight: bold; }
.info { color: #5DADE2; }
.dim { color: #6c757d; }
</style></head><body>';
echo '<div class="header"><h2 style="margin:0;">Generating Demo Data...</h2><p style="margin:5px 0 0; opacity:0.9;">MoodleMoot NL 2026 — 4-Factor Behavioral Risk Scoring</p></div>';
echo '<div class="log" id="logbox">';

function weblog($msg, $class = '') {
    $ts = date('H:i:s');
    $prefix = "<span class='dim'>[{$ts}]</span> ";
    if ($class) {
        echo $prefix . "<span class='{$class}'>{$msg}</span>\n";
    } else {
        echo $prefix . htmlspecialchars($msg) . "\n";
    }
    if (ob_get_level()) @ob_flush();
    @flush();
}

// Check existing.
$existing = $DB->get_record('course', ['shortname' => $CONFIG['course_shortname']]);
if ($existing) {
    weblog("ERROR: Course '{$CONFIG['course_shortname']}' already exists (ID: {$existing->id}).", 'error');
    weblog("Delete it first via Site Admin > Courses, then re-run this script.", 'error');
    echo '</div>';
    echo '<div class="error"><strong>Cannot proceed.</strong> The demo course already exists.</div>';
    echo '</body></html>';
    exit;
}

weblog("Moodle version: " . $CFG->release, 'info');
weblog("Server: " . php_uname('s') . ' ' . php_uname('r'), 'info');
weblog("PHP: " . phpversion(), 'info');
weblog("---", 'dim');

// Create course.
weblog("Creating course: " . $CONFIG['course_name'] . "...", 'progress');

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
weblog("Course created: ID={$course->id}", 'info');

// Prepare enrolment.
$studentrole = $DB->get_record('role', ['shortname' => 'student']);
if (!$studentrole) {
    weblog("FATAL: Student role not found!", 'error');
    echo '</div><div class="error">Student role not found. Aborting.</div></body></html>';
    exit;
}

$enrolplugin = enrol_get_plugin('manual');
$enrolinstances = $DB->get_records('enrol', ['courseid' => $course->id, 'enrol' => 'manual']);
if (empty($enrolinstances)) {
    $enrolplugin->add_instance($course);
    $enrolinstances = $DB->get_records('enrol', ['courseid' => $course->id, 'enrol' => 'manual']);
}
$enrolinstance = reset($enrolinstances);

weblog("---", 'dim');
weblog("Creating {$CONFIG['num_students']} students with 4 behavioral profiles...", 'progress');

$studentdata = [];
$profilecounts = [];
$totallogrecords = 0;

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

    // Generate weekly activity logs.
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
            $totallogrecords++;
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
            $totallogrecords++;
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
            $totallogrecords++;
        }

        // Session duration tracking.
        $sessiondecay = pow(rand_between($profile['session_trend'][0], $profile['session_trend'][1]), $week - 1);
        $sessionmin = max(2, round(rand_between($profile['session_duration_base'][0], $profile['session_duration_base'][1]) * $sessiondecay));
        $totalsessionminutes += $sessionmin * max(1, $loginsweek);
    }

    // Generate quiz scores.
    $quizscores = [];
    for ($q = 1; $q <= $CONFIG['num_quizzes']; $q++) {
        if ($q * 2.5 > $dropoutweek) break;
        $quizdecay = pow(rand_between($profile['quiz_trend'][0], $profile['quiz_trend'][1]), $q - 1);
        $score = max(0, min(100, round(rand_between($profile['quiz_base'][0], $profile['quiz_base'][1]) * $quizdecay)));
        $quizscores[] = $score;
    }

    // Count assignment submissions.
    $submissions = 0;
    $latesubmissions = 0;
    for ($a = 1; $a <= $CONFIG['num_assignments']; $a++) {
        if ($a * 2 > $dropoutweek) break;
        if ((mt_rand() / mt_getrandmax()) < rand_between($profile['submission_rate'][0], $profile['submission_rate'][1])) {
            $submissions++;
            if ((mt_rand() / mt_getrandmax()) < rand_between($profile['late_rate'][0], $profile['late_rate'][1])) {
                $latesubmissions++;
            }
        }
    }

    $avgquiz = count($quizscores) > 0 ? round(array_sum($quizscores) / count($quizscores), 1) : 0;

    $studentdata[] = [
        'profile_type' => $profilename,
        'dropped_out' => $dropsout ? 1 : 0,
    ];

    // Progress feedback every 10 students.
    if ($s % 10 == 0) {
        $pct = round($s / $CONFIG['num_students'] * 100);
        $droptext = $dropsout ? " (drops out week {$dropoutweek})" : '';
        weblog("Student {$s}/{$CONFIG['num_students']} [{$pct}%] — {$user->firstname} {$user->lastname} ({$profilename}{$droptext}) — {$totallogrecords} log records", 'progress');
    }
}

// Export CSV.
$csvpath = $CFG->dataroot . '/moodle_student_data.csv';
weblog("---", 'dim');
weblog("Exporting CSV to moodledata...", 'info');

// Summary.
echo '</div>';
echo '<div class="success">';
echo '<h3 style="margin-top:0; color: #155724;">&#x2705; Demo Data Generation Complete!</h3>';
echo '<table style="width: 100%; border-collapse: collapse;">';
echo '<tr><td style="padding: 8px; border-bottom: 1px solid #c3e6cb;"><strong>Course</strong></td><td style="padding: 8px; border-bottom: 1px solid #c3e6cb;">' . $CONFIG['course_name'] . ' (ID: ' . $course->id . ')</td></tr>';
echo '<tr><td style="padding: 8px; border-bottom: 1px solid #c3e6cb;"><strong>Students created</strong></td><td style="padding: 8px; border-bottom: 1px solid #c3e6cb;">' . $CONFIG['num_students'] . '</td></tr>';
echo '<tr><td style="padding: 8px; border-bottom: 1px solid #c3e6cb;"><strong>Total log records</strong></td><td style="padding: 8px; border-bottom: 1px solid #c3e6cb;">' . number_format($totallogrecords) . '</td></tr>';

$dropoutcount = array_sum(array_column($studentdata, 'dropped_out'));
echo '<tr><td style="padding: 8px; border-bottom: 1px solid #c3e6cb;"><strong>Dropout count</strong></td><td style="padding: 8px; border-bottom: 1px solid #c3e6cb;">' . $dropoutcount . ' (' . round($dropoutcount / $CONFIG['num_students'] * 100) . '%)</td></tr>';

echo '<tr><td style="padding: 8px;" colspan="2"><strong>Profile distribution:</strong></td></tr>';
foreach ($profilecounts as $type => $count) {
    $pct = round($count / $CONFIG['num_students'] * 100);
    echo '<tr><td style="padding: 4px 8px 4px 30px;">' . ucfirst($type) . '</td><td style="padding: 4px 8px;">' . $count . ' (' . $pct . '%)</td></tr>';
}
echo '</table>';

echo '<h4 style="color: #155724; margin-top: 20px;">Next Steps:</h4>';
echo '<ol>';
echo '<li><strong>Enable Analytics:</strong> Site Admin &rarr; Analytics &rarr; Analytics settings &rarr; Toggle ON</li>';
echo '<li><strong>Enable model:</strong> Site Admin &rarr; Analytics &rarr; Analytics models &rarr; "Students at risk of dropping out" &rarr; Enable</li>';
echo '<li><strong>Run predictions:</strong> Click "Evaluate model" then "Get predictions"</li>';
echo '<li><strong>Add block:</strong> Go to DS101 course &rarr; Turn editing on &rarr; Add block &rarr; Predictive Analytics Dashboard</li>';
echo '</ol>';

echo '<div style="background: #fff3cd; border: 1px solid #ffc107; padding: 12px; border-radius: 8px; margin-top: 15px;">';
echo '<strong>&#x26a0;&#xfe0f; Security:</strong> Please delete this script from your server now via cPanel File Manager:<br>';
echo '<code>public_html/moodle/admin/populate_demo_data_web.php</code>';
echo '</div>';

echo '</div></body></html>';
