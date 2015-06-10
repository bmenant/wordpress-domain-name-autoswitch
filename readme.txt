=== Domain Name Autoswitch ===
Contributors: bmenant
Tags: Multi Domain Name, Front Page
Requires at least: 3.5.x
Tested up to: 4.2.2
Stable tag: v1.2.5
License: WTFPL
License URI: http://www.wtfpl.net/

Add a domain name field to the posts’ edit pages you have selected
(through category and/or post type identifiers). Allow each post
to have its own domain name and to be displayed as a front page.

== Description ==

Here is a useful method you should use any where you need it `dnas_get_post_ID()`.

It returns the post ID (if any) related to the current domain name.
For instance, if you would like to display URL of the fake home page:
`<?php echo get_the_permalink( dnas_get_post_ID() ); ?>`

You can check the plugin development on [github](https://github.com/bmenant/wordpress-domain-name-autoswitch).

**Pros:**

*   since the plugin does not modify nor use your `.htaccess` file,
    it works with every permalink formats.
*   allow you to manage your domain names directly through your
    post edit forms.

**Cons:**

*   since it hacks the query variables, some template tags like `is_home()`
    could not work as expected if a dedicated domain name is triggered.
*   be aware there is no conflict support: you can fill the same domain
    for several posts. Only a single one will win the game, without
    any control from your part (but it will be easy to see which one have
    to be checked… if you ever notice it). Be careful!

= Configuration =

The plugin **works with both custom post types and classic post type**.
You have to edit a configuration file in order to indicate on which
posts the plugin should add its domain name field. You can indicate
**Categorie identifiers or/and Post Types** slugs. For instance, to make
the plugin showing its domain field on any posts of the categories #4
and #8, then:

`$dnas_categories_ID = array( 4, 8 );`

= Dependency =

The [Advanced Custom Fields](http://wordpress.org/plugins/advanced-custom-fields)
plugin is required.

= License =

This program is free software. It comes without any warranty, to
the extent permitted by applicable law. You can redistribute it
and/or modify it under the terms of the [Do What The Fuck You Want
To Public License, Version 2](http://www.wtfpl.net/txt/copying/),
as published by Sam Hocevar. See (http://www.wtfpl.net/) for more details.

== Installation ==

1.  First, unzip or upload the plugin into `/wp-content/plugins/`.
1.  In the plugin directory, edit the following file:
    `sample-domain-name-autoswitch-config.php`.
1.  Then, rename the config file to: `domain-name-autoswitch-config.php`.
1.  Activate the plugin through the WordPress Plugins admin page.
1.  Set up your domain names like you want to, directly through the
    posts’ edit pages.
1.  Obviously, you will have to set up your virtual hosts (`ServerAlias`
    directives), so every domain names you want to use are pointing to
    your WordPress instance.

== Changelog ==

= 1.2.5 =

* Fix an issue with the `plugins_url` function.

= 1.2.4 =

* Fix the way settings are saved: from now, settings are not erased after upgrading the plugin.

= 1.2.3 =

*   Fix SQL syntax when using custom post type identifiers.
*   Add license details.

= 1.2.2 =

*   Add the `dnas_get_post_ID()` global function.
*   Enhance the readme.txt.

= 1.2.1 =

*   Add the get_post_ID() method to the class.
*   Fix an issue with permalink: now hooks the permalinks values.
    For instance, the canonical meta link now displays the dedicated domain name.
*   Fix a singleton issue (public constructor).

= 1.2 =

*   First stable release.
*   Handle posts by categories or by post types.

