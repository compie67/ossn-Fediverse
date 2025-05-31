# INSTALL GUIDE – FediverseBridge (for OSSN)

**Version:** 1.0.1  
**Author:** Eric Redegeld – [nlsociaal.nl](https://nlsociaal.nl)

## 1. Install the component via OSSN

Go to your OSSN admin panel and upload the component zip file via:


After upload, locate **FediverseBridge** in the component list.

## 2. Enable the component

Navigate to:
Click **FediverseBridge** and then click **Enable**.

When activated, the module will automatically create required folders and keys for the test users (`admin` and `testsociaal`).

## 3. Add required `.htaccess` rewrite (for WebFinger support)

Edit your OSSN site's `.htaccess` file. Inside the `<IfModule mod_rewrite.c>` block, add the following line:

RewriteRule ^.well-known/webfinger$ index.php?h=well-known&p=webfinger [QSA,L]

This rewrite rule is required so other Fediverse servers (such as Mastodon) can discover your users via WebFinger.

> **Tip:** Always make a backup of your original `.htaccess` file before editing it.  
> A sample `.htaccess` file is included in the component directory.

## 4. Test if federation works

Use your browser or run the following `curl` command:

curl "https://yourdomain.com/.well-known/webfinger?resource=acct:admin@yourdomain.com"

You should receive a valid JSON response with ActivityPub actor information.

## 5. Users must manually opt-in

Each user must visit their own profile page and enable federation manually.  
Only after opting in will public wall posts containing hashtags (`#`) be federated to other platforms.

## 6. Support and updates

Visit [https://nlsociaal.nl](https://nlsociaal.nl) to:
- Check for updates
- Report issues
- Suggest improvements
