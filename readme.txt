=== Domain Name Autoswitch ===
Contributors: bmenant
Tags: Multi Domain Name, Front Page
Tested up to: 3.8.1
Stable tag: v1.2
License: WTFPL
License URI: http://www.wtfpl.net/

Add a domain name field to posts’ form, so each post can have its own domain name and can be displayed as a front page.

== Description ==
The plugin works with both custom post types and classic post type. 
You have to edit a configuration file in order to indicate on which posts the plugin should add its domain name field. You can indicate Categorie IDs or/and Post Types.

N.B.:  The plugin will not modify your .htaccess file (and does not need it).

== Installation ==
Unzip the plugin into /wp-content/plugins/. 
Edit the following file: sample-domain-name-autoswitch-config.php.
Enable the plugin from the WordPress plugins admin page.
Set your domain name for each post you want to, from the post form… and that’s it.

Obviously, you will have to set up your virtual hosts (ServerAlias directive) so every domain names you want to use are pointing to your WP installation.
