<?php
/**
 * disable.php – Deactivatie van FediverseBridge
 * 🇳🇱 Gemaakt door Eric Redegeld voor nlsociaal.nl
 * 🇬🇧 Created by Eric Redegeld for nlsociaal.nl
 *
 * 🇳🇱 Functies:
 * - Verwijdert testberichten van bekende gebruikers
 * - Leegt het logbestand
 * - (optioneel) Verwijdert private/public keys
 * - (optioneel) Verwijdert de gehele userdata-directory
 *
 * 🇬🇧 Features:
 * - Removes test posts of known users
 * - Resets the log file
 * - (optional) Removes key pairs
 * - (optional) Removes full component userdata
 */

// 📁 Pad naar gebruikersdata van component
// 📁 Path to component userdata directory
$base = ossn_get_userdata('components/FediverseBridge');
$log_file = "{$base}/logs/fediverse.log";

// 🧹 Testberichten verwijderen van bekende gebruikers
// 🧹 Remove test outbox files from predefined users
$users = ['admin', 'testsociaal'];
foreach ($users as $username) {
    $testfile = "{$base}/outbox/{$username}/enable-test.json";
    if (file_exists($testfile)) {
        unlink($testfile);
        file_put_contents($log_file, date('c') . " 🧹 Testbericht verwijderd: {$testfile}\n", FILE_APPEND);
    }
}

// 📄 Logbestand overschrijven met marker (niet verwijderen)
// 📄 Overwrite log file with marker (don't delete completely)
if (file_exists($log_file)) {
    file_put_contents($log_file, date('c') . " 🧹 Logbestand gewist\n");
}

// 🔐 Sleutels verwijderen (alleen als expliciet ingeschakeld)
// 🔐 Delete private/public keys (only if enabled)
$verwijder_sleutels = false; // 🔁 Zet op true indien gewenst

if ($verwijder_sleutels) {
    foreach ($users as $username) {
        $key = "{$base}/private/{$username}.pem";
        $pub = "{$base}/private/{$username}.pubkey";

        if (file_exists($key)) {
            unlink($key);
            file_put_contents($log_file, date('c') . " ❌ Private key verwijderd: {$key}\n", FILE_APPEND);
        }
        if (file_exists($pub)) {
            unlink($pub);
            file_put_contents($log_file, date('c') . " ❌ Public key verwijderd: {$pub}\n", FILE_APPEND);
        }
    }
}

// ⚠️ Volledige userdata verwijderen (alleen testomgevingen)
// ⚠️ Fully wipe all component data (use in test only!)
$volledig_verwijderen = false; // ⚠️ Pas op met productieomgevingen

if ($volledig_verwijderen && is_dir($base)) {
    /**
     * 🇳🇱 Recursieve mapverwijdering
     * 🇬🇧 Recursive directory deletion
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
    error_log("[FediverseBridge] ⚠️ Gehele userdata-structuur verwijderd uit {$base}");
}
