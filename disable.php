<?php
/**
 * disable.php – Deactivation script for FediverseBridge
 * Created by Eric Redegeld for nlsociaal.nl
 *
 * Features:
 * - Removes test posts of known users
 * - Resets the log file
 * - (optional) Removes key pairs
 * - (optional) Removes full component userdata (test use only)
 */

// Path to component userdata directory
$base = ossn_get_userdata('components/FediverseBridge');
$log_file = "{$base}/logs/fediverse.log";

// Remove test outbox files from known users
$users = ['admin', 'testsociaal'];
foreach ($users as $username) {
    $testfile = "{$base}/outbox/{$username}/enable-test.json";
    if (file_exists($testfile)) {
        unlink($testfile);
        file_put_contents($log_file, date('c') . " INFO: Test message deleted: {$testfile}\n", FILE_APPEND);
    }
}

// Reset the log file (do not delete it completely)
if (file_exists($log_file)) {
    file_put_contents($log_file, date('c') . " INFO: Log file cleared\n");
}

// Optional: Remove private/public key files
$delete_keys = false; // Set to true to remove all key pairs

if ($delete_keys) {
    foreach ($users as $username) {
        $key = "{$base}/private/{$username}.pem";
        $pub = "{$base}/private/{$username}.pubkey";

        if (file_exists($key)) {
            unlink($key);
            file_put_contents($log_file, date('c') . " INFO: Private key deleted: {$key}\n", FILE_APPEND);
        }
        if (file_exists($pub)) {
            unlink($pub);
            file_put_contents($log_file, date('c') . " INFO: Public key deleted: {$pub}\n", FILE_APPEND);
        }
    }
}

// Optional: Fully remove the entire component data directory
$delete_all_data = false; // Use with caution in production environments

if ($delete_all_data && is_dir($base)) {
    /**
     * Recursive directory deletion
     */
    function rrmdir($dir) {
        foreach (glob($dir . '/*') as $f) {
            if (is_dir($f)) {
                rrmdir($f);
            } else {
                unlink($f);
            }
        }
        rmdir($dir);
    }

    rrmdir($base);
    error_log("[FediverseBridge] WARNING: Entire userdata structure removed from {$base}");
}
