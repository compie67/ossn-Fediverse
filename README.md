With your help, i make more cool modules
https://buy.stripe.com/6oE6pS1V2eie0p27ss

Please help me with the VPS.
-----------------------------------------------

BE AWARE PRESENT MODULE IS FULL WITH DEBUG AND LOGGING. v1

**
Sources for the Fediverse Integration:
WordPress Fediverse Plugin:
Inspired by WordPress plugins that allow integration with federated networks, providing seamless content sharing.

ActivityPub:
A decentralized social networking protocol used for federating social platforms. It enables servers to share content, posts, and user data in a decentralized manner across various networks (e.g., Mastodon, Pleroma).

WebFinger:
A protocol used to retrieve account information in a decentralized manner. It helps in identifying users across federated networks.

Fediverse Libraries and Documentation:
Various resources like the ActivityPub specification and Fediverse documentation guide the development of decentralized social applications.

Open Source Examples:
There are many open-source implementations that provide a starting point for building Fediverse-compatible platforms, including examples from platforms like Mastodon and Pleroma.

It’s been a fun journey working with these tools and concepts, experimenting with decentralized technologies, and finding creative ways to connect different networks in a privacy-conscious manner. The integration has allowed for an exciting new direction in developing federated social networks.
**

🇳🇱 FediverseBridge voor OSSN – Uitleg
FediverseBridge is een OSSN-module die gebruikers van jouw sociale netwerk (zoals nlsociaal.nl) koppelt aan het bredere Fediverse – het gedecentraliseerde netwerk van Mastodon, Friendica, Pleroma en meer.

✨ Wat doet deze module?
👤 Gebruikers kunnen opt-in kiezen via hun profielinstellingen.

🔐 De module genereert per gebruiker automatisch RSA-sleutels en een Fediverse-identiteit (@gebruikersnaam@jouwdomein.nl).

📝 Elke OSSN-wallpost met een hashtag (#) wordt automatisch doorgestuurd naar de volgers op het Fediverse via het ActivityPub-protocol.

💬 Replies en likes vanaf bijvoorbeeld Mastodon worden ontvangen en getoond in het profiel van de gebruiker.

✅ Een Follow wordt automatisch beantwoord met een Accept, en de actor wordt opgeslagen in een followers.json bestand.

📂 Berichten worden opgeslagen in ossn_data/components/FediverseBridge/outbox/gebruikersnaam/.

🔐 Veilig en decentraal
Gebruikers kunnen zich op elk moment afmelden.

Er is geen centrale afhankelijkheid van externe API's of platforms.

Berichten zijn publiek, maar alleen opt-in gebruikers worden gefedereerd.

🇬🇧 FediverseBridge for OSSN – Overview
FediverseBridge is an OSSN module that connects your social network (e.g. nlsociaal.nl) to the broader Fediverse — the decentralized network of Mastodon, Friendica, Pleroma, and others.

✨ What does this module do?
👤 Users can opt in via their profile settings.

🔐 Upon opt-in, the module generates RSA keys and a Fediverse identity (@username@yourdomain.nl) for the user.

📝 Any OSSN wall post containing a hashtag (#) is automatically published to the user’s Fediverse followers using the ActivityPub protocol.

💬 Replies and likes from platforms like Mastodon are received and displayed in the user’s profile.

✅ Any Follow is responded to with an Accept, and the actor is saved in a followers.json file.

📂 Posts are saved in ossn_data/components/FediverseBridge/outbox/username/.

🔐 Secure and decentralized
Users can opt out at any time.

No dependency on third-party APIs or services.

Posts are public, but only opted-in users are federated.

🌍 Why It Matters
This project bridges OSSN to the larger Fediverse — making nlsociaal.nl a real player in the decentralized web.
It respects user consent, uses open protocols, and doesn't track, spy, or profile.



Important: Always back up your existing .htaccess file before activating this update.

Installation Instructions
Backup:
Create a backup copy of the current .htaccess file in the root of your OSSN installation.

Replace:
Replace the entire content of the .htaccess file with the content provided below.

Verify:
After replacement, verify that the website and the FediverseBridge module work correctly.
restart apache 



# v6.5.0.0
# Open Source Social Network
#
# @package   .htaccess.ossn
# @author    OSSN Core Team <info@opensource-socialnetwork.org>
# @copyright (c) Engr. Syed Arsalan Hussain Shah (OpenTeknik LLC)
# @license   OPEN SOURCE SOCIAL NETWORK LICENSE https://opensource-socialnetwork.org/licence 
# @link      https://www.opensource-socialnetwork.org 

Options -Indexes
Options +SymlinksIfOwnerMatch
DirectoryIndex index.php

<Files "error_log">
	order allow,deny
	deny from all
</Files>

<Files ".user.ini">
	order allow,deny
	deny from all
</Files>

<FilesMatch "(nginx|htaccess).dist">
	order allow,deny
	deny from all
</FilesMatch>

# Disallow CLI and CRON script from browser
RedirectMatch 404 ^/system/handlers/cli$
RedirectMatch 404 ^/system/handlers/cron$

<IfModule mod_mime.c>
    AddType image/vnd.microsoft.icon .ico
</IfModule>

<IfModule mod_expires.c>
	ExpiresActive On
	ExpiresDefault "access plus 1 year"
</IfModule>

<FilesMatch "\.(jpg|jpeg|gif|png|mp3|flv|mov|avi|3pg|html|htm|swf|js|css|ico)$">
	FileETag MTime Size
</FilesMatch>

<IfModule mod_rewrite.c>
RewriteEngine on

	# ✅ Correcte WebFinger Rewrite naar OSSN-style h/p parameters
	RewriteRule ^\.well-known/webfinger$ index.php?h=well-known&p=webfinger [QSA,L]

	# 🔒 Fediverse inbox POST endpoint (ActivityPub)
	RewriteCond %{REQUEST_METHOD} POST
	RewriteRule ^fediverse/inbox/([A-Za-z0-9\-_]+)$ index.php?h=fediverse&p=inbox/$1 [QSA,L]

	RewriteRule ^rewrite.php$ installation/tests/apache_rewrite.php [L]

	RewriteRule ^action\/([A-Za-z0-9\_\-\/]+)$ system/handlers/actions.php?action=$1&%{QUERY_STRING} [L]

	RewriteCond %{REQUEST_FILENAME} !-d
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteRule ^([A-Za-z0-9\_\-]+)$ index.php?h=$1 [QSA,L]

	RewriteCond %{REQUEST_FILENAME} !-d
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteRule ^([A-Za-z0-9\_\-]+)\/(.*)$ index.php?h=$1&p=$2 [QSA,L]

</IfModule>

# https://github.com/nextcloud/server/issues/26569
<IfModule mod_php.c>
	php_value memory_limit 512M
	php_value register_globals 0
	php_value post_max_size 105M
	php_value upload_max_filesize 100M
	php_value default_charset "UTF-8"
	php_flag session.cookie_httponly on
</IfModule>

