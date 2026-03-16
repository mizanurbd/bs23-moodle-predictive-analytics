<?php
/**
 * MySQL 5.7 Compatibility Fix for Moodle 5.0
 * MoodleMoot Netherlands 2026 Demo
 *
 * This script patches Moodle's environment.xml to accept MySQL 5.7+
 * instead of MySQL 8.0+. This is safe for DEMO PURPOSES because:
 *   - Our plugin uses only standard Moodle DB API ($DB-> methods)
 *   - No MySQL 8.0+ specific features (CTEs, window functions, JSON) are used
 *   - The core Moodle 5.0 code mostly works on MySQL 5.7 for basic operations
 *
 * USAGE:
 *   Upload to: public_html/moodle/admin/fix_mysql_check.php
 *   Access:    https://brainstation-23.jp/moodle/admin/fix_mysql_check.php
 *
 * IMPORTANT: This is for DEMO/PRESENTATION purposes only.
 *            Not recommended for production environments.
 *            DELETE THIS FILE AFTER USE.
 */

require(__DIR__ . '/../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Must be admin.
require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$PAGE->set_context($context);
$PAGE->set_url('/admin/fix_mysql_check.php');
$PAGE->set_title('MySQL 5.7 Compatibility Fix');

header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html><html><head><meta charset="utf-8">';
echo '<title>MySQL 5.7 Compatibility Fix</title>';
echo '<style>
body { font-family: -apple-system, sans-serif; max-width: 700px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
.box { background: #fff; padding: 25px; border-radius: 10px; border: 1px solid #dee2e6; margin-bottom: 20px; }
.header { background: linear-gradient(135deg, #0F6CBF, #1177CC); color: #fff; padding: 20px 25px; border-radius: 10px 10px 0 0; margin: -25px -25px 20px; }
.success { background: #d4edda; border-color: #c3e6cb; }
.error { background: #f8d7da; border-color: #f5c6cb; }
.warning { background: #fff3cd; border-color: #ffc107; }
code { background: #e9ecef; padding: 2px 6px; border-radius: 4px; font-size: 14px; }
pre { background: #1D2125; color: #A8E6CF; padding: 15px; border-radius: 6px; overflow-x: auto; font-size: 13px; }
</style></head><body>';

echo '<div class="box">';
echo '<div class="header"><h2 style="margin:0;">MySQL 5.7 Compatibility Fix</h2>';
echo '<p style="margin:5px 0 0;opacity:0.9;">MoodleMoot NL 2026 Demo Environment</p></div>';

// Locate environment.xml
$envfile = $CFG->dirroot . '/admin/environment.xml';

if (!file_exists($envfile)) {
    echo '<div class="box error"><strong>Error:</strong> Cannot find environment.xml at: <code>' . $envfile . '</code></div>';
    echo '</body></html>';
    exit;
}

// Check if already patched.
$content = file_get_contents($envfile);
$currentdb = 'Unknown';
if (preg_match('/<DATABASE version="([^"]+)"[^>]*>.*?<VENDOR name="mysql".*?version="([^"]+)"/s', $content, $m)) {
    $currentdb = $m[2];
}

echo '<h3>Current Status</h3>';
echo '<table style="width:100%;border-collapse:collapse;">';
echo '<tr><td style="padding:8px;border:1px solid #dee2e6;"><strong>Moodle Version</strong></td><td style="padding:8px;border:1px solid #dee2e6;">' . $CFG->release . '</td></tr>';
echo '<tr><td style="padding:8px;border:1px solid #dee2e6;"><strong>Environment File</strong></td><td style="padding:8px;border:1px solid #dee2e6;"><code>' . $envfile . '</code></td></tr>';

// Get actual database version.
$dbinfo = $DB->get_server_info();
echo '<tr><td style="padding:8px;border:1px solid #dee2e6;"><strong>Actual DB Version</strong></td><td style="padding:8px;border:1px solid #dee2e6;">' . htmlspecialchars(is_array($dbinfo) ? json_encode($dbinfo) : $dbinfo) . '</td></tr>';
echo '<tr><td style="padding:8px;border:1px solid #dee2e6;"><strong>Required MySQL (in XML)</strong></td><td style="padding:8px;border:1px solid #dee2e6;">' . $currentdb . '</td></tr>';
echo '</table>';

$confirm = optional_param('confirm', '', PARAM_ALPHANUM);

if ($confirm !== 'PATCHNOW') {
    echo '<h3>What This Fix Does</h3>';
    echo '<p>It modifies <code>admin/environment.xml</code> to lower the MySQL minimum version requirement from <strong>8.0</strong> (or higher) to <strong>5.7</strong>.</p>';
    echo '<p>This allows the Moodle upgrade/plugin install page to proceed without the database version error.</p>';

    echo '<div class="box warning">';
    echo '<strong>Note:</strong> This is safe for demo purposes. Our Predictive Analytics plugin uses only standard Moodle DB API methods, which are fully compatible with MySQL 5.7.';
    echo '</div>';

    // Create backup info.
    echo '<h3>Before Patching</h3>';
    echo '<p>A backup will be created at: <code>admin/environment.xml.backup</code></p>';

    echo '<div style="text-align:center;margin:25px 0;">';
    echo '<a href="?confirm=PATCHNOW" style="background:#F98012;color:#fff;padding:12px 35px;border-radius:8px;text-decoration:none;font-size:16px;font-weight:bold;">';
    echo 'Apply MySQL 5.7 Fix</a>';
    echo '</div>';

} else {
    // Create backup.
    $backupfile = $envfile . '.backup';
    if (!file_exists($backupfile)) {
        $backed = copy($envfile, $backupfile);
        if ($backed) {
            echo '<p>Backup created: <code>environment.xml.backup</code></p>';
        } else {
            echo '<div class="box error"><strong>Error:</strong> Could not create backup. Check file permissions on the admin/ directory.</div>';
            echo '<p>You can manually create a backup via cPanel File Manager before proceeding.</p>';
        }
    } else {
        echo '<p>Backup already exists: <code>environment.xml.backup</code></p>';
    }

    // Patch: Lower MySQL version requirements.
    // Match patterns like: <VENDOR name="mysql" version="8.0" />  or  version="8.4"
    $patched = $content;

    // Replace MySQL version requirements (various formats in environment.xml).
    // Pattern: VENDOR name="mysql" version="8.x" -> version="5.7"
    $patched = preg_replace(
        '/(<VENDOR\s+name\s*=\s*"mysql"\s+version\s*=\s*")[0-9]+\.[0-9]+(")/i',
        '${1}5.7${2}',
        $patched,
        -1,
        $count1
    );

    // Also handle: <VENDOR name="mysql" version="8.0.0" (3-part version)
    $patched = preg_replace(
        '/(<VENDOR\s+name\s*=\s*"mysql"\s+version\s*=\s*")[0-9]+\.[0-9]+\.[0-9]+(")/i',
        '${1}5.7.0${2}',
        $patched,
        -1,
        $count2
    );

    $totalchanges = $count1 + $count2;

    if ($totalchanges > 0) {
        $written = file_put_contents($envfile, $patched);
        if ($written !== false) {
            echo '<div class="box success">';
            echo '<h3 style="margin-top:0;color:#155724;">Fix Applied Successfully!</h3>';
            echo '<p>Changed <strong>' . $totalchanges . '</strong> MySQL version requirement(s) from 8.x to 5.7 in environment.xml.</p>';
            echo '<p><strong>Next step:</strong> Go to <a href="' . $CFG->wwwroot . '/admin/index.php">Moodle upgrade page</a> to install the plugin.</p>';
            echo '</div>';
        } else {
            echo '<div class="box error">';
            echo '<h3 style="margin-top:0;">Write Failed</h3>';
            echo '<p>Could not write to environment.xml. The file may be read-only.</p>';
            echo '<h4>Manual Fix via cPanel File Manager:</h4>';
            echo '<ol>';
            echo '<li>Open cPanel > File Manager</li>';
            echo '<li>Navigate to: <code>public_html/moodle/admin/environment.xml</code></li>';
            echo '<li>Right-click > Edit (Code Editor)</li>';
            echo '<li>Use Find & Replace: Find <code>name="mysql" version="8.</code> → Replace with <code>name="mysql" version="5.7</code></li>';
            echo '<li>Save the file</li>';
            echo '<li>Go to <a href="' . $CFG->wwwroot . '/admin/index.php">Moodle upgrade page</a></li>';
            echo '</ol>';
            echo '</div>';
        }
    } else {
        // Check if already at 5.7
        if (strpos($patched, 'name="mysql" version="5.7"') !== false ||
            strpos($patched, "name='mysql' version='5.7'") !== false) {
            echo '<div class="box success">';
            echo '<h3 style="margin-top:0;color:#155724;">Already Patched!</h3>';
            echo '<p>The environment.xml already has MySQL 5.7 as the minimum. No changes needed.</p>';
            echo '<p><strong>Next step:</strong> Go to <a href="' . $CFG->wwwroot . '/admin/index.php">Moodle upgrade page</a> to install the plugin.</p>';
            echo '</div>';
        } else {
            echo '<div class="box warning">';
            echo '<h3 style="margin-top:0;">No Matches Found</h3>';
            echo '<p>Could not find the expected MySQL version pattern in environment.xml.</p>';
            echo '<h4>Manual Fix via cPanel File Manager:</h4>';
            echo '<ol>';
            echo '<li>Open cPanel > File Manager</li>';
            echo '<li>Navigate to: <code>public_html/moodle/admin/environment.xml</code></li>';
            echo '<li>Right-click > Edit (Code Editor)</li>';
            echo '<li>Search for <code>mysql</code> and find all lines with <code>version="8</code></li>';
            echo '<li>Change the version number to <code>5.7</code></li>';
            echo '<li>Save and go to <a href="' . $CFG->wwwroot . '/admin/index.php">upgrade page</a></li>';
            echo '</ol>';
            echo '</div>';
        }
    }
}

echo '<div class="box warning" style="margin-top:20px;">';
echo '<strong>Security:</strong> Delete both helper scripts from your server after completing the setup:<br>';
echo '<code>public_html/moodle/admin/fix_mysql_check.php</code><br>';
echo '<code>public_html/moodle/admin/populate_demo_data_web.php</code>';
echo '</div>';

echo '</div></body></html>';
