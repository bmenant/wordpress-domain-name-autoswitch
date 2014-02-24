<?php
/**
 ** Plugin Name: Domain Name Autoswitch for APE
 ** Description: Display the setted post for setted domain name (uses Advanced Custom Fields plugin)
 ** Version: 1.0
 ** Author: Benjamin Menant <dev@menant-benjamin.fr>
 ** Author URI: http://menant-benjamin.fr/
 ** License: WTFPL
 **
 ***************************************************************************
 **                                                                       **
 **           DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE                 **
 **                    Version 2, December 2004                           **
 **                                                                       **
 ** Copyright (C) 2013 Benjamin Menant <dev@menant-benjamin.fr>           **
 **                                                                       **
 ** Everyone is permitted to copy and distribute verbatim or modified     **
 ** copies of this license document, and changing it is allowed as long   **
 ** as the name is changed.                                               **
 **                                                                       **
 **            DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE                **
 **   TERMS AND CONDITIONS FOR COPYING, DISTRIBUTION AND MODIFICATION     **
 **                                                                       **
 ** 0. You just DO WHAT THE FUCK YOU WANT TO.                             **
 **                                                                       **
 ***************************************************************************
 **
 **/

//
// Configuration
//

// The system name for domain name field (see custom field plugin settings)
$domain_name_autoswitch_acf_field_id = 'domain_name';

// The categories ID used to select affected posts
$domain_name_autoswitch_categories_id = array(2);


//
// CODE SHOULD NOT BE EDITED BEYOND THIS LINE
//

// Include class file
require_once plugin_dir_path( __FILE__ ) . 'ape-domain-name-autoswitch-class.php';

// Class instance
$apednas = new Domain_Name_Autoswitch ($domain_name_autoswitch_categories_id, $domain_name_autoswitch_acf_field_id);

// Filter hooks
add_filter ('pre_option_home', array($apednas, 'baseurl_handler'));
add_filter ('pre_option_siteurl', array($apednas, 'baseurl_handler'));
add_filter('request', array($apednas, 'request_handler'));
