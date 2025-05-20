<?php
/**
 * plugins/default/fediversebridge/admin/optinusers.php
 * 🇳🇱 Adminpagina: overzicht van gebruikers die Fediverse-opt-in hebben ingeschakeld
 * 🇬🇧 Admin page: overview of users who have enabled Fediverse opt-in
 *
 * Door Eric Redegeld – nlsociaal.nl
 */

// 📁 Pad naar map met opt-in JSON-bestanden
// 📁 Path to opt-in user files
$optins_dir = ossn_get_userdata('components/FediverseBridge/optin/');
$users = [];

// 📦 Verzamel alle gebruikers die een opt-in JSON hebben
// 📦 Collect all users with an opt-in JSON file
if (is_dir($optins_dir)) {
    foreach (glob($optins_dir . '*.json') as $file) {
        $users[] = basename($file, '.json');
    }
}

// ✅ Start HTML layout van adminpagina
// ✅ Start HTML output for admin page
echo "<div class='ossn-admin-page-container'>";
echo "<h2>Fediverse Opt-in gebruikers (" . count($users) . ")</h2>";

if (empty($users)) {
    // ℹ️ Geen gebruikers geopt-in
    echo "<p>⚠️ Er zijn nog geen gebruikers die deelname hebben ingeschakeld.</p>";
} else {
    // 🧾 Toon lijst van gebruikers
    echo "<ul class='fediverse-optin-admin-list' style='margin-top:10px; padding-left:20px;'>";

    foreach ($users as $username) {
        $user = ossn_user_by_username($username);
        $username_escaped = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');

        if ($user) {
            $fullname = htmlspecialchars($user->fullname, ENT_QUOTES, 'UTF-8');
            $profile_url = ossn_site_url("u/{$username_escaped}");
            echo "<li><a href='{$profile_url}' target='_blank'>{$fullname} ({$username_escaped})</a></li>";
        } else {
            // ⚠️ JSON-bestand bestaat maar gebruiker is verwijderd
            echo "<li>{$username_escaped} <em>(gebruiker niet gevonden / user not found)</em></li>";
        }
    }

    echo "</ul>";
}

echo "</div>";
