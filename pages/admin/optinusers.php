<?php
/**
 * pages/admin/optinusers.php
 * Admin page loader for the Fediverse opt-in user overview
 */

if (!ossn_isAdminLoggedin()) {
    ossn_error_page();
    return;
}

// Load the admin view for opt-in users
$content = ossn_plugin_view('fediversebridge/admin/optinusers');

// Render the page with title
echo ossn_view_page(ossn_print('fediversebridge:optinusers'), $content);
