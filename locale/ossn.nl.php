<?php
/**
 * OSSN Component: FediverseBridge
 * 🇳🇱 Nederlandse taalbestand
 * Gemaakt door Eric Redegeld voor nlsociaal.nl
 */

ossn_register_languages('nl', array(
    // 🧑‍💻 Admin menu
    'fediversebridge:optinusers' => 'Fediverse Opt-in gebruikers',
    'fediversebridge:adminmenu' => 'Fediverse Opt-in gebruikers',
    'fediversebridge:admin:optinusers:title' => 'Fediverse Opt-in Gebruikers',
    'fediversebridge:admin:optinusers:nousers' => 'Er zijn nog geen gebruikers met Fediverse-opt-in.',
    'fediversebridge:admin:optinusers:view' => 'Bekijk profiel',

    // 📄 Profielpagina opt-in
    'fediversebridge:menu:optin' => 'Fediverse',
    'fediversebridge:optin:profile:title' => 'Fediverse Opt-in',
    'fediversebridge:optin:profile:enabled' => '✅ Je neemt momenteel deel aan het Fediverse.',
    'fediversebridge:optin:profile:disabled' => '❌ Je hebt Fediverse-integratie uitgeschakeld.',
    'fediversebridge:optin:profile:checkbox' => 'Ik wil deelnemen aan het Fediverse',
    'fediversebridge:optin:profile:save' => 'Opslaan',
    'fediversebridge:optin:profile:sharetip' => 'Deel dit adres zodat anderen je kunnen volgen via Mastodon of andere Fediverse-platforms.',

    // 🔘 Knoppen
    'fediversebridge:optin:profile:enablebtn' => '✅ Inschakelen',
    'fediversebridge:optin:profile:disablebtn' => '❌ Uitschakelen',
    'fediversebridge:optin:block:btn' => 'Blokkeer',
    'fediversebridge:optin:block:placeholder' => 'actor URI (bijv. https://...)',
    'fediversebridge:optin:block:title' => '🚫 Specifieke actor blokkeren',
    'fediversebridge:optin:block:success' => '🔒 Actor geblokkeerd: %s',

    // ✅ Feedbackmeldingen
    'fediversebridge:optin:profile:success' => '✅ Fediverse opt-in is ingeschakeld voor %s.',
    'fediversebridge:optin:profile:error' => '❌ Fediverse opt-in is uitgeschakeld voor %s.',
    'fediversebridge:nousers' => 'Geen gebruikers hebben opt-in ingeschakeld.',

    // 📬 Interacties
    'fediversebridge:replies:title' => '💬 Ontvangen reacties',
    'fediversebridge:likes:title' => '❤️ Likes ontvangen',
    'fediversebridge:announces:title' => '🔁 Gedeelde berichten (Announce)',
    'fediversebridge:ownreplies:title' => '💬 Reacties op je eigen berichten',
    'fediversebridge:followers:title' => '👥 Volgers',
    'fediversebridge:reply:by' => '💬 van <strong>%s</strong> op <em>%s</em>',
    'fediversebridge:reply:inreplyto' => '↪️ Antwoord op:',
    'fediversebridge:reply:linktothread' => '🧵 Bekijk volledige thread',
    'fediversebridge:reply:timestamp' => '🕒 %s',

    // 🧵 Thread UI
    'fediversebridge:thread:title' => '🧵 Thread voor post %s',
    'fediversebridge:thread:collapse' => '➖ Verberg thread',
    'fediversebridge:thread:expand' => '➕ Toon thread',


    // 🛠️ Debug info
    'fediversebridge:debug:title' => '[DEBUG]',
    'fediversebridge:debug:username' => 'Gebruiker: %s',
    'fediversebridge:debug:privatekey' => 'Private key: %s',
    'fediversebridge:debug:publickey' => 'Public key: %s',
    'fediversebridge:debug:outbox' => 'Outbox dir: %s',
    'fediversebridge:debug:optinfile' => 'Opt-in json: %s',
    'fediversebridge:debug:userguid' => 'User GUID: %s',

    // 🔍 Fouten
    'fediversebridge:error:usernotfound' => 'Gebruiker niet gevonden.',
    'fediversebridge:error:pageinvalid' => 'Ongeldige Fediverse-pagina.',

    // 🛠️ Installatie / activatie
    'fediversebridge:enable:log:dir:created' => '📁 Map aangemaakt: %s',
    'fediversebridge:enable:log:dir:failed' => '❌ Kon map niet aanmaken: %s',
    'fediversebridge:enable:log:key:priv:created' => '🔑 Private key aangemaakt: %s.pem',
    'fediversebridge:enable:log:key:pub:created' => '🔓 Public key aangemaakt: %s.pubkey',
    'fediversebridge:enable:log:key:pub:failed' => '⚠️ Waarschuwing: public key niet geëxtraheerd voor %s',
    'fediversebridge:enable:log:key:gen:failed' => '❌ Fout bij genereren OpenSSL sleutel voor %s',
    'fediversebridge:enable:log:optin:created' => '✅ Opt-in bestand aangemaakt voor %s',
    'fediversebridge:enable:log:outbox:test' => '📤 Testbericht opgeslagen in: %s',
    'fediversebridge:enable:log:install:done' => '✅ INSTALLATIE: FediverseBridge is succesvol geactiveerd',

    // 📩 Inbox logs
    'fediversebridge:inbox:error:nouser' => '❌ Geen gebruikersnaam opgegeven',
    'fediversebridge:inbox:error:method' => '❌ Alleen POST-verzoeken toegestaan',
    'fediversebridge:inbox:error:contenttype' => '🚫 INBOX: Ongeldige Content-Type: %s',
    'fediversebridge:inbox:error:body' => '❌ Lege of ongeldige body',
    'fediversebridge:inbox:error:json' => '❌ JSON kon niet worden verwerkt',
    'fediversebridge:inbox:error:signature' => '🚫 Ongeldige handtekening voor %s',
    'fediversebridge:inbox:ignored' => '⛔️ Actor %s is geen bekende volger van %s, bericht wordt genegeerd.',
    'fediversebridge:inbox:received' => '📥 INBOX voor %s ontvangen | Type: %s',
    'fediversebridge:inbox:stored' => '📩 Bericht opgeslagen in %s',
    'fediversebridge:inbox:like' => '❤️ Like ontvangen van %s op %s',
    'fediversebridge:inbox:announce' => '🔁 Announce ontvangen van %s op %s',
    'fediversebridge:inbox:create' => '🆕 Create (Note) ontvangen van %s',
    'fediversebridge:inbox:create:reply' => '📝 Reply opgeslagen bij post %s in %s',
    'fediversebridge:inbox:create:noguid' => '⚠️ Geen GUID gevonden in inReplyTo: %s',
    'fediversebridge:inbox:create:skip' => '⏭️ Create is geen reply op lokale post, genegeerd.',
    'fediversebridge:inbox:follow' => '👤 Nieuwe follower: %s',
    'fediversebridge:inbox:follow:added' => '✅ Follower toegevoegd aan %s',
    'fediversebridge:inbox:undo' => '↩️ Undo %s door %s voor %s',

    // 📝 Federated Note (note.php)
    'fediversebridge:note:log:visit' => 'note.php bezocht: username=%s, guid=%s',
    'fediversebridge:note:error:invalid' => '❌ Ongeldig aanvraagformaat',
    'fediversebridge:note:error:user' => '❌ Gebruiker niet gevonden',
    'fediversebridge:note:error:post' => '❌ Bericht niet gevonden',
    'fediversebridge:note:error:mismatch' => '❌ Bericht hoort niet bij deze gebruiker',

    // 👥 Federated followers endpoint
    'fediversebridge:followers:error:missing' => '❌ Gebruikersnaam ontbreekt',
    'fediversebridge:followers:error:notfound' => '❌ Gebruiker niet gevonden',
    'fediversebridge:followers:log:invalidjson' => '⚠️ followers.json voor %s is ongeldig',
    'fediversebridge:log:nofollowersfile' => 'Geen followers.json voor %s, gebruik fallback-inboxes.',
    'fediversebridge:log:nooptinfile' => 'Geen opt-in bestand voor %s, post wordt niet gefedereerd.',
    'fediversebridge:log:invalidfollowersfile' => 'Ongeldige of corrupte followers.json voor %s.',

    // Outbox
    'fediversebridge:outbox:error:missing' => '❌ Gebruikersnaam ontbreekt',
    'fediversebridge:outbox:error:notfound' => '❌ Outbox niet gevonden',

    // 👤 Avatar proxy errors
    'fediversebridge:avatar:error:badrequest' => '❌ Ongeldige aanvraag',
    'fediversebridge:avatar:error:missing' => '❌ Ongeldige aanvraag (GUID of bestandsnaam ontbreekt)',
    'fediversebridge:avatar:error:notfound' => '❌ Avatar niet gevonden',
    'fediversebridge:avatar:error:missing_data' => 'Ongeldige avatar-aanvraag: ontbrekende gegevens.',
    'fediversebridge:avatar:error:notfound_user' => 'Geen avatar gevonden voor deze gebruiker.',

    // 🖼️ Media proxy voor wall-afbeeldingen
    'fediversebridge:proxy:error:missing' => '❌ Ongeldige aanvraag (GUID of bestandsnaam ontbreekt)',
    'fediversebridge:proxy:error:invalidobj' => '❌ Object niet gevonden of ongeldig',
    'fediversebridge:proxy:error:filenotfound' => '❌ Bestand niet gevonden',
    'fediversebridge:proxy:log:show' => '🖼️ Toont %s (%s, %s bytes) uit object %s',

    // 🧑 Actor-profiel foutmeldingen
    'fediversebridge:actor:error:missing' => '❌ Gebruikersnaam ontbreekt',
    'fediversebridge:actor:error:notfound' => '❌ Gebruiker niet gevonden',
    'fediversebridge:actor:error:nopubkey' => '❌ Publieke sleutel ontbreekt',
    
    // Handler
    'fediversebridge:optin:profile:findable' => 'Vindbaar op het Fediverse',
    
    // Optin preview tool
    'fediversebridge:check:title' => 'Externe Fediverse-post bekijken',
    'fediversebridge:check:btn' => 'Inspecteer URL',
    'fediversebridge:check:trying' => 'Bezig met ophalen van de ActivityPub-gegevens...',
    'fediversebridge:check:fail' => 'Kon de URL niet ophalen.',
    'fediversebridge:check:invalidjson' => 'Kon geen geldige JSON-parsen.',
    'fediversebridge:check:success' => 'Bericht succesvol opgehaald!',

    // Sign logs
    'fediversebridge:log:key:missing' => 'Private key niet gevonden voor %s: %s',
    'fediversebridge:log:inbox:invalid' => 'Ongeldige inbox-URL: %s',
    'fediversebridge:log:openssl:loadfail' => 'OpenSSL kon sleutel niet laden voor %s',
    'fediversebridge:log:openssl:signfail' => 'Ondertekening mislukt voor %s. OpenSSL fout: %s',
    'fediversebridge:log:accept:start' => 'Verzend Accept-activiteit naar %s voor %s',
    'fediversebridge:log:accept:headersfail' => 'Kon headers niet genereren voor Accept naar %s',
    'fediversebridge:log:accept:curlfail' => 'cURL-fout bij verzenden van Accept naar %s: %s',
    'fediversebridge:log:accept:success' => 'Accept verzonden naar %s. HTTP status: %s. Reactie: %s',

    // Actor messages
    'fediversebridge:actor:error:missing_url' => 'Gebruikersnaam ontbreekt in de URL.',
    'fediversebridge:actor:error:notfound_user' => 'Gebruiker niet gevonden.',
    'fediversebridge:actor:error:nopubkey_available' => 'Geen publieke sleutel beschikbaar.',
    'fediversebridge:actor:summary' => 'Gebruiker van nlsociaal.nl (@%s)',
));
