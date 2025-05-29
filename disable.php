<?php
/**
 * disable.php – Deactivation script for FediverseBridge
 * Created by Eric Redegeld for nlsociaal.nl
 *
 * Functionality:
 * - Removes test posts for opted-in users
 * - Resets the log file
 * - (optional) Removes private/public key pairs
 * - (optional) Fully deletes component data directory
 */

// 📁 Component data path
$base     = ossn_get_userdata('components/FediverseBridge');
$log_file = "{$base}/logs/fediverse.log";

// 👥 Determine users based on opt-in files
$users = [];
$optin_dir = "{$base}/optin";
if (is_dir($optin_dir)) {
    $files = scandir($optin_dir);
    foreach ($files as $file) {
        if (str_ends_with($file, '.json')) {
            $username = basename($file, '.json');
            $users[] = $username;
        }
    }
}

// ➕ Optionally include central system users
$users = array_unique(array_merge($users, ['admin']));

// 🧹 Remove test message from outbox
foreach ($users as $username) {
    $testfile = "{$base}/outbox/{$username}/enable-test.json";
    if (file_exists($testfile)) {
        unlink($testfile);
        file_put_contents($log_file, date('c') . " 🧹 Removed test post: {$testfile}\n", FILE_APPEND);
    }
}

// 🧾 Clear the log file with marker (do not fully delete it)
if (file_exists($log_file)) {
    file_put_contents($log_file, date('c') . " 🧹 Log reset via disable.php\n");
}

// 🔐 Remove private/public keys (set to true only if needed)
$remove_keys = false;

if ($remove_keys) {
    foreach ($users as $username) {
        $key  = "{$base}/private/{$username}.pem";
        $pub  = "{$base}/private/{$username}.pubkey";

        if (file_exists($key)) {
            unlink($key);
            file_put_contents($log_file, date('c') . " ❌ Private key removed: {$key}\n", FILE_APPEND);
        }
        if (file_exists($pub)) {
            unlink($pub);
            file_put_contents($log_file, date('c') . " ❌ Public key removed: {$pub}\n", FILE_APPEND);
        }
    }
}

// ⚠️ Completely remove component data folder (only for full uninstall or test)
$full_remove = false;

if ($full_remove && is_dir($base)) {
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
    error_log("[FediverseBridge] ⚠️ Component data folder fully removed from {$base}");
}
