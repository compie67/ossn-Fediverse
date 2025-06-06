<?php
/**
 * pages/admin/optinusers.php
 * 🇳🇱 Adminpagina loader voor Fediverse opt-in gebruikersoverzicht
 * 🇬🇧 Admin page loader for Fediverse opt-in user overview
 */

if (!ossn_isAdminLoggedin()) {
    ossn_error_page();
}

$content = ossn_plugin_view('fediversebridge/admin/optinusers');
echo ossn_view_page('🔐 Fediverse Opt-in Gebruikers', $content);
