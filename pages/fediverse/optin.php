<?php
/**
 * pages/fediversebridge/optin.php
 * 🇳🇱 Toont de opt-in pagina voor Fediverse-federatie
 * 🇬🇧 Displays the opt-in page for Fediverse federation
 *
 * Door Eric Redegeld – nlsociaal.nl
 */

// 👤 Haal gebruikersnaam uit input (URL of POST)
// 👤 Get username from input
$username = input('username');
$user = ossn_user_by_username($username);

// 🔐 Alleen de gebruiker zelf of een beheerder mag deze pagina zien
// 🔐 Only the user themself or an admin may view this page
if (
    !$user ||
    !ossn_isLoggedIn() ||
    ($user->guid !== ossn_loggedin_user()->guid && !ossn_isAdminLoggedin())
) {
    ossn_error_page(); // 🔒 Toegang geweigerd / Access denied
}

// 🧾 Stel paginatitel in (via taalbestand)
// 🧾 Set page title from language file
$title = ossn_print('fediversebridge:optin:profile:title');

// 🧱 Laad de inhoudsview met de gebruiker als context
// 🧱 Load the view, passing the user as context
$content = ossn_view('fediversebridge/optin', ['user' => $user]);

// 📄 Render de pagina in standaard OSSN layout
// 📄 Render the page in default OSSN layout
echo ossn_view_page($title, $content);
