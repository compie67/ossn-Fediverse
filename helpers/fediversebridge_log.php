<?php
/**
 * FediverseBridge – Logging helper
 * Logs messages to fediverse.log with rotation above 1MB
 */

if (!function_exists('fediversebridge_log')) {
    function fediversebridge_log($msg) {
        if (!defined('FEDIVERSEBRIDGE_DEBUG') || !FEDIVERSEBRIDGE_DEBUG) return;

        $logfile = ossn_get_userdata('components/FediverseBridge/logs/fediverse.log');

        if (file_exists($logfile) && filesize($logfile) > 1024 * 1024) {
            rename($logfile, $logfile . '.' . time() . '.bak');
        }

        file_put_contents($logfile, date('c') . " {$msg}\n", FILE_APPEND);
    }
}
