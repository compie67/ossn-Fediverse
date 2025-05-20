<?php
/**
 * pages/admin/optinusers.php
 * 🇳🇱 Adminpagina: Overzicht van gebruikers die Fediverse-opt-in hebben geactiveerd
 * 🇬🇧 Admin page: Lists all users who opted in to Fediverse integration
 *
 * Gemaakt door Eric Redegeld voor nlsociaal.nl
 */

if (!ossn_isAdminLoggedin()) {
    ossn_error_page(); // 🛡️ Blokkeer toegang voor niet-admins
}

// 📁 Pad naar optin-bestanden
// 📁 Path to opt-in user data files
$optin_dir = ossn_get_userdata('components/FediverseBridge/optin/');
$users = [];

// 📦 Verzamel gebruikers met een geldig opt-in JSON-bestand
// 📦 Collect users who have a valid opt-in JSON file
if (is_dir($optin_dir)) {
    $files = glob("{$optin_dir}/*.json");
    foreach ($files as $file) {
        $username = basename($file, '.json');
        $user = ossn_user_by_username($username);
        if ($user) {
            $users[] = $user;
        }
    }
}

// 🧾 Begin HTML-output
// 🧾 Start rendering the HTML output
$list = '';
$list .= '<div class="fediverse-admin-optin">';
$list .= '<h2>🔐 Fediverse Opt-in Gebruikers</h2>';

if ($users) {
    // 🧮 Tabel met gebruikersinformatie
    // 🧮 Table of user info
    $list .= "<table class='table ossn-admin-table'>";
    $list .= "<thead><tr><th>Gebruikersnaam</th><th>Naam</th><th>Email</th></tr></thead><tbody>";

    foreach ($users as $user) {
        // 🛡️ Escapen voor XSS-veiligheid
        // 🛡️ Escape output to prevent XSS
        $username = htmlspecialchars($user->username, ENT_QUOTES, 'UTF-8');
        $name = htmlspecialchars("{$user->first_name} {$user->last_name}", ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars($user->email, ENT_QUOTES, 'UTF-8');
        $profile_url = ossn_site_url("u/{$username}");

        $list .= "<tr>";
        $list .= "<td><a href='{$profile_url}' target='_blank'>{$username}</a></td>";
        $list .= "<td>{$name}</td>";
        $list .= "<td>{$email}</td>";
        $list .= "</tr>";
    }

    $list .= "</tbody></table>";
} else {
    // ℹ️ Geen opt-in gebruikers gevonden
    // ℹ️ No users found who opted in
    $list .= "<p>📭 Geen gebruikers hebben zich aangemeld voor Fediverse-integratie.</p>";
}

$list .= '</div>';

// 💬 Toon het resultaat
// 💬 Output the result
echo $list;
