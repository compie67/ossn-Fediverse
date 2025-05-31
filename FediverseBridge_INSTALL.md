
📦 INSTALLATIE-INSTRUCTIES – FediverseBridge (voor OSSN)

Versie: 1.0
Auteur: Eric Redegeld – nlsociaal.nl

# 📁 1. Bestanden uploaden

Plaats de map `FediverseBridge/` in de OSSN `components/` map van jouw installatie.

Bijvoorbeeld:
  /var/www/html/components/FediverseBridge

Of upload via de OSSN adminmodule in:
  Administrator > Components > Upload

# ⚙️ 2. Component activeren

Ga naar:
  Administrator > Components
Klik op "FediverseBridge" > Enable

Bij activeren worden automatisch outbox/inbox/publieke sleutels aangemaakt voor de testgebruikers `admin` en `testsociaal`.

# 🌐 3. Vereiste .htaccess-regel toevoegen (WebFinger)

Open jouw `.htaccess` bestand in de root van je OSSN-installatie en voeg de volgende regel toe binnen het blok <IfModule mod_rewrite.c>:

RewriteRule ^\.well-known/webfinger$ index.php?h=well-known&p=webfinger [QSA,L]

Zonder deze regel kunnen andere Fediverse servers jouw gebruikers niet vinden.

💡 TIP: Maak altijd eerst een backup van je originele `.htaccess`. 
Een voorbeeldbestand met deze regel staat ook in de modulemap.

# 🧪 4. Test of federatie werkt

Open in je browser of gebruik curl:

curl "https://jouwdomein.nl/.well-known/webfinger?resource=acct:admin@jouwdomein.nl"

Je moet een JSON-antwoord zien met actor-informatie.

# 👤 5. Gebruikers activeren

Elke gebruiker moet zichzelf "opt-in'en" via hun profielpagina. Pas daarna worden berichten met een `#` doorgestuurd naar het Fediverse.

# 🛠️ Support en updates

Bekijk updates of meld problemen op: https://nlsociaal.nl

# 📦 INSTALL GUIDE – FediverseBridge (for OSSN)

Version: 1.0
Author: Eric Redegeld – nlsociaal.nl

# 📁 1. Upload the files

Place the folder `FediverseBridge/` into your OSSN `components/` folder.

Example:
  /var/www/html/components/FediverseBridge

Or upload the zip via OSSN Admin at:
  Administrator > Components > Upload

# ⚙️ 2. Enable the component

Go to:
  Administrator > Components
Click "FediverseBridge" > Enable

When enabled, it will create keys and folders for test users `admin` and `testsociaal`.

# 🌐 3. Add required .htaccess rewrite (WebFinger)

Edit your site's `.htaccess` file and add the following rule inside the <IfModule mod_rewrite.c> block:

RewriteRule ^\.well-known/webfinger$ index.php?h=well-known&p=webfinger [QSA,L]

Without this, other Fediverse servers won't be able to discover your users.

💡 TIP: Always back up your original `.htaccess` file first.
A sample `.htaccess` file with this rule is included in the module directory.


# 🧪 4. Test federation support
Use your browser or curl to check:

curl "https://yourdomain.com/.well-known/webfinger?resource=acct:admin@yourdomain.com"

You should get a JSON response with actor info.

# 👤 5. Users must opt-in

Each user must manually enable federation via their profile page before posts with hashtags (`#`) will be federated.

# 🛠️ Support and updates

Find updates or report issues at: https://nlsociaal.nl
