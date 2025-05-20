<?php
/**
 * plugins/default/fediversebridge/admin/optinusers.php
 * 🇳🇱 Adminpagina: overzicht van gebruikers die Fediverse-opt-in hebben ingeschakeld
 * 🇬🇧 Admin page: overview of users who have enabled Fediverse opt-in
 *
 * Door Eric Redegeld – nlsociaal.nl
 */

$optins_dir = ossn_get_userdata('components/FediverseBridge/optin/');
$users = [];

if (is_dir($optins_dir)) {
    foreach (glob($optins_dir . '*.json') as $file) {
        $username = basename($file, '.json');
        $user = ossn_user_by_username($username);
        if ($user) {
            $users[] = $user;
        }
    }
}

$list = '';
$list .= '<div class="fediverse-admin-optin">';
$list .= '<h2>🔐 Fediverse Opt-in Gebruikers (' . count($users) . ')</h2>';

if ($users) {
    $list .= "<table class='table ossn-admin-table'>";
    $list .= "<thead><tr><th>Gebruikersnaam</th><th>Naam</th><th>Email</th><th>Profiel</th></tr></thead><tbody>";
    foreach ($users as $user) {
        $username = htmlspecialchars($user->username, ENT_QUOTES, 'UTF-8');
        $name = htmlspecialchars("{$user->first_name} {$user->last_name}", ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars($user->email, ENT_QUOTES, 'UTF-8');
        $profile_url = ossn_site_url("u/{$username}");

        $list .= "<tr>";
        $list .= "<td>@{$username}</td>";
        $list .= "<td>{$name}</td>";
        $list .= "<td>{$email}</td>";
        $list .= "<td><a href='{$profile_url}' target='_blank'>Bekijk profiel</a></td>";
        $list .= "</tr>";
    }
    $list .= "</tbody></table>";
} else {
    $list .= "<p>⚠️ Er zijn nog geen gebruikers die Fediverse-integratie hebben ingeschakeld.</p>";
}

$list .= '</div>';

echo $list;
