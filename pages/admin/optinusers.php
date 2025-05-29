<?php
/**
 * pages/admin/optinusers.php
 * 🇳🇱 Adminpagina loader voor Fediverse opt-in gebruikersoverzicht
 * 🇬🇧 Admin page loader for Fediverse opt-in user overview
 */

if (!ossn_isAdminLoggedin()) {
    ossn_error_page();
    return;
}

// 🔄 Laad de admin view voor opt-in gebruikers
$content = ossn_plugin_view('fediversebridge/admin/optinusers');

// 📄 Toon pagina met titel
echo ossn_view_page(ossn_print('fediversebridge:optinusers'), $content);
