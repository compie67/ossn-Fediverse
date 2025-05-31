<?php
/**
 * plugins/default/fediversebridge/admin/optinusers.php
 * Admin page: overview of users who have enabled Fediverse opt-in
 *
 * Created by Eric Redegeld – nlsociaal.nl
 */

$optins_dir = ossn_get_userdata('components/FediverseBridge/optin/');
$users = [];

// Collect all users who have an opt-in JSON file
if (is_dir($optins_dir)) {
    foreach (glob($optins_dir . '*.json') as $file) {
        $username = basename($file, '.json');
        $user = ossn_user_by_username($username);
        if ($user) {
            $users[] = $user;
        }
    }
}

// Start HTML output
$list = '<div class="fediverse-admin-optin">';
$list .= '<h2>Fediverse Opt-in Users (' . count($users) . ')</h2>';

if (!empty($users)) {
    $list .= "<table class='table ossn-admin-table'>";
    $list .= "<thead><tr><th>Username</th><th>Full Name</th><th>Email</th><th>Profile</th></tr></thead><tbody>";

    foreach ($users as $user) {
        $username     = htmlspecialchars($user->username, ENT_QUOTES, 'UTF-8');
        $full_name    = htmlspecialchars("{$user->first_name} {$user->last_name}", ENT_QUOTES, 'UTF-8');
        $email        = htmlspecialchars($user->email, ENT_QUOTES, 'UTF-8');
        $profile_url  = ossn_site_url("u/{$username}");

        $list .= "<tr>";
        $list .= "<td>@{$username}</td>";
        $list .= "<td>{$full_name}</td>";
        $list .= "<td>{$email}</td>";
        $list .= "<td><a href='{$profile_url}' target='_blank'>View Profile</a></td>";
        $list .= "</tr>";
    }

    $list .= "</tbody></table>";
} else {
    $list .= "<p>No users have enabled Fediverse integration yet.</p>";
}

$list .= '</div>';

echo $list;
