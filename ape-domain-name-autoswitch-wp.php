<?php
/**
 ** Plugin Name: Domain Name Autoswitch for APE
 ** Description: Display the setted post for setted domain name (require Advanced Custom Fields plugin).
 ** Version: 1.1
 ** Author: Benjamin Menant <dev@menant-benjamin.fr>
 ** Author URI: http://menant-benjamin.fr/
 ** License: WTFPL
 **
 *****************************************************************************
 **            DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE                  **
 **                    Version 2, December 2004                             **
 **                                                                         **
 ** Copyright (C) 2004 Sam Hocevar <sam@hocevar.net>                        **
 **                                                                         **
 ** Everyone is permitted to copy and distribute verbatim or modified       **
 ** copies of this license document, and changing it is allowed as long     **
 ** as the name is changed.                                                 **
 **                                                                         **
 **            DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE                  **
 **   TERMS AND CONDITIONS FOR COPYING, DISTRIBUTION AND MODIFICATION       **
 **                                                                         **
 **  0. You just DO WHAT THE FUCK YOU WANT TO.                              **
 **                                                                         **
 *****************************************************************************
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

/**
 * Manages domain name autoswitch for corresponding content:
 * Displays a particular post for each different domain name requested.
 */
class Domain_Name_Autoswitch {

	/**
	 * Instance of this class.
	 * @var      object
	 */
	protected static $instance = null;

    /**
     * The ID of the corresponding content
     * @var Integer
     */
    public $content_ID;

    /**
     * The domain name request
     * @var String
     */
    public $domain_name;

    /**
     * The ID of the matched posts’ category
     * @var Integer
     */
    private $_categories_ID = array();

    /**
     * The ID of the domain name field
     * @var String
     */
    private $_field_ID;

    /**
     * Constructor
     * @param Array $categories_ID ID of the categories used to select contents
     * @param String $field_ID ID of the field used to catch domain name value for each content
     * @return void
     */
    public function __construct ($categories_ID = array(), $field_ID = null) {
        // Populate private properties
        $this->_categories_ID = $categories_ID;
        $this->_field_ID = $field_ID;

        // Get domain name request
        $this->domain_name = $_SERVER['SERVER_NAME'];

        // Message error handler
        add_action('admin_notices', array($this, 'error_notice'));
    }

  	/**
  	 * Return an instance of this class.
  	 * @return    object    A single instance of this class.
  	 */
  	public static function get_instance() {
  		// If the single instance hasn"t been set, set it now.
  		if (null == self::$instance) {
  			self::$instance = new self;
  		}

  		return self::$instance;
  	}

    /**
     * Look for content which corresponds to the domain name request
     * @return Integer|False The corresponding content ID, False if corresponding content not found
     */
    private function _get_content_ID_by_domain_name () {
        if (!empty($this->_categories_ID)) {
            global $wpdb;
            $dn = esc_sql($this->domain_name);
            $categories = implode(', ', $this->_categories_ID);
            $sql = "SELECT post_id AS id
                    FROM {$wpdb->postmeta}
                    WHERE post_id IN (
                        SELECT object_id
                        FROM {$wpdb->term_relationships}
                        WHERE term_taxonomy_id IN ( $categories )
                    )
                    AND meta_key = '{$this->_field_ID}'
                    AND meta_value = '$dn'
                    LIMIT 1";
            $id_from_db = $wpdb->get_row($sql);
            if (!empty($id_from_db) && !empty($id_from_db->id)) {
                $this->content_ID = $id_from_db->id;
                return $this->content_ID;
            }
        }
        elseif (is_admin()) {
            $this->error_notice('Please edit plugin file to set at least one post type.');
        }
        return false;
    }

    /**
     * Change the 'home' and 'siteurl' WP option to the requested domain name
     * @todo Do not work with base site URL like http://example.com/basepath/
     * @todo Do not work with HTTPS…
     * @return String  The base URL
     */
    public function baseurl_handler () {
        return 'http://' . $this->domain_name;
    }

    /**
     * Change the request to load the correct post
     * @return String  The base URL
     */
    public function request_handler ($request) {
        if (empty($request)
        &&  $this->_get_content_ID_by_domain_name()) {
            $request = array('p' => $this->content_ID);
        }
        return $request;
    }

    /**
     * Display a message error.
     * @param   string  $msg    The message error to display.
     */
    static function error_notice ($msg = null) {
        if(!isset($errors)) static $errors;
        if(!empty($msg)) $errors[] = $msg;
        elseif(!empty($errors)) {
            echo '<div class="error"><h3>Domain Name Autoswitch plugin</h3><ul>';
            foreach ($errors as $e) {
                echo "<li> $e </li>";
            }
            echo '</ul></div>';
        }
    }
}

function ape_dnas() {
    global $ape_dnas;
    $ape_dnas = Domain_Name_Autoswitch::get_instance();
    return $ape_dnas;
}

// Initialize
$ape_dnas = ape_dnas();

// Filter hooks
add_filter('pre_option_home', array($ape_dnas, 'baseurl_handler'));
add_filter('pre_option_siteurl', array($ape_dnas, 'baseurl_handler'));
add_filter('request', array($ape_dnas, 'request_handler'));

