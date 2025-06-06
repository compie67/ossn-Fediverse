<?php
/**
 * pages/fediversebridge/optin.php
 * Toont de opt-in pagina voor Fediverse-federatie
 * Door Eric Redegeld – nlsociaal.nl
 */

// Haal gebruikersnaam op uit input (URL of POST)
$username = input('username');
$user = ossn_user_by_username($username);

// Alleen de gebruiker zelf of een beheerder mag deze pagina zien
if (
    !$user ||
    !ossn_isLoggedIn() ||
    ($user->guid !== ossn_loggedin_user()->guid && !ossn_isAdminLoggedin())
) {
    ossn_error_page(); // Toegang geweigerd
}

// Pagina titel vanuit taalbestand
$title = ossn_print('fediversebridge:optin:profile:title');

// Laad de view met gebruiker als context
$content = ossn_view('fediversebridge/optin', ['user' => $user]);

// Render de pagina met de standaard OSSN layout
echo ossn_view_page($title, $content);
