<?php
/**
 ** Plugin Name: Domain Name Autoswitch
 ** Description: Display the post configured to be displayed given a domain name (require Advanced Custom Fields plugin).
 ** Version: 1.2.4
 ** Author: Benjamin Menant <dev@menant-benjamin.fr>
 ** Author URI: http://menant-benjamin.fr/
 ** License: WTFPL
 **
 *****************************************************************************
 **                                                                         **
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
 **                                                                         **
 ** This program is free software. It comes without any warranty, to        **
 ** the extent permitted by applicable law. You can redistribute it         **
 ** and/or modify it under the terms of the Do What The Fuck You Want       **
 ** To Public License, Version 2, as published by Sam Hocevar. See          **
 ** http://www.wtfpl.net/ for more details.                                 **
 **                                                                         **
 *****************************************************************************
 **/

/**
 * Manages domain name autoswitch for corresponding content:
 * Displays a particular post for each different domain name requested.
 */
class Domain_Name_Autoswitch {

    /**
     * Instance of this class.
     * @var      object
     */
    protected static $_instance = null;

    /**
     * The ID of the corresponding content
     * @var Integer
     */
    public $post_ID;

    /**
     * The Post Type of the corresponding content
     * @var String
     */
    public $post_type;

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
     * The ID of the matched post types
     * @var String
     */
    private $_post_types_ID = array();

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
    private function __construct () {
        // Setting up
        $categories_ID = get_option('dnas_categories_ID', array(0));
        $post_types_ID = get_option('dnas_post_types_ID', array(''));

        include_once(plugin_dir_path(__FILE__) . 'domain-name-autoswitch-config.php');
        if (isset($dnas_categories_ID)
        && is_array($dnas_categories_ID)
        && !empty($dnas_categories_ID)) {
            update_option('dnas_categories_ID', $dnas_categories_ID);
            $categories_ID = $dnas_categories_ID;
        }
        if (isset($dnas_post_types_ID)
        && is_array($dnas_post_types_ID)
        && !empty($dnas_post_types_ID)) {
            update_option('dnas_post_types_ID', $dnas_post_types_ID);
            $post_types_ID = $dnas_post_types_ID;
        }
        if (empty($categories_ID) && empty($post_types_ID)){
            $this->error_notice(__('Configuration file is missing.
                You should rename the sample config file to:
                <code>domain-name-autoswitch-config.php</code>.', 'dnas'));
        }

        // Check Dependencies.
        add_action('admin_init', array($this, 'check_dependencies'));

        // Populate private properties
        $this->_categories_ID = $categories_ID;
        $this->_post_types_ID = $post_types_ID;
        $this->_field_ID = 'dnas-domain-name';

        // Get domain name request
        $this->domain_name = $_SERVER['SERVER_NAME'];

        // Add a custom post type.
        add_action('init', array($this, 'create_dnas_custom_fields'));

        // Alter the query.
        add_action('pre_get_posts', array($this, 'query_handler'));

        // Filter hooks: alter URL getting.
        add_filter('pre_option_home', array($this, 'baseurl_handler'));
        add_filter('pre_option_siteurl', array($this, 'baseurl_handler'));
        add_filter('post_type_link', array($this, 'permalink_handler'), 10, 2);
        add_filter('post_link', array($this, 'permalink_handler'), 10, 2);

        // Message error handler
        add_action('admin_notices', array($this, 'error_notice'));
    }

    /**
     * Test Dependencies.
     *
     * @since   1.2.4
     */
    public function check_dependencies() {
        $deactivate = false;
        if (!function_exists('register_field_group')) {
            $this->error_notice(__('<a href="http://www.advancedcustomfields.com/">Advanced Custom Fields</a>
                is required and must be activated to manage domain names.', 'dnas'));
            $deactivate = true;
        }
        if ($deactivate) {
            $this->error_notice('The plugin has been deactivated.');
            deactivate_plugins(plugin_basename(__FILE__));
        }
    }

    /**
     * Return an instance of this class.
     * @return    object    A single instance of this class.
     */
    public static function get_instance() {
        // If the single instance hasn"t been set, set it now.
        if (null == self::$_instance) {
        self::$_instance = new self;
        }
        return self::$_instance;
    }

    /**
     * Prepare Categories SQL WHERE
     * @return  String  The SQL WHERE clause,
     *          |null   or null if empty.
     */
    private function _prepare_categories_sql_where () {
        return  !empty($this->_categories_ID)
                ? sprintf('term_taxonomy_id IN ( %s )', implode(', ', $this->_categories_ID))
                : null;
    }

    /**
     * Prepare Post Types SQL WHERE
     * @return  String  The SQL WHERE clause,
     *          |null   or null if empty.
     */
    private function _prepare_post_types_sql_where () {
        if (!empty($this->_post_types_ID)) {
            $post_types = 'post_type IN ( ';
            foreach ($this->_post_types_ID as $post_type) {
                if (isset($i)) $post_types .= ', ';
                else $i = true;
                $post_types .= "'$post_type'";
            }
            $post_types .= ' )';
        }
        else $post_types = null;
        return $post_types;
    }

    /**
     * Look for content which corresponds to the domain name request.
     * Will check for both Post Types and Categories ID.
     * @return Integer|False The corresponding content ID, False if corresponding content not found
     */
    private function _get_post_ID_by_domain_name () {
        $categories = $this->_prepare_categories_sql_where();
        $post_types = $this->_prepare_post_types_sql_where();

        if ($categories || $post_types) {
            global $wpdb;
            $where_categories = "id IN ( SELECT object_id FROM {$wpdb->term_relationships} WHERE $categories )";
            $dn = esc_sql($this->domain_name);

            $sql = "SELECT p.ID AS id, p.post_type AS post_type FROM {$wpdb->postmeta} AS pm ";
            $sql.= "JOIN {$wpdb->posts} AS p ON pm.post_id = p.ID ";
            $sql.= "WHERE ";
            if ($categories && $post_types)
                $sql.= "( $where_categories OR $post_types )";
            elseif ($categories)
                $sql.= $where_categories;
            elseif ($post_types)
                $sql.= $post_types;
            $sql.= " AND pm.meta_key = '{$this->_field_ID}' AND pm.meta_value = '$dn'";
            $sql.= " AND p.post_status = 'publish' LIMIT 1";
            $row = $wpdb->get_row($sql);
            if (!empty($row) && !empty($row->id)) {
                $this->post_ID = $row->id;
                $this->post_type = $row->post_type;
                return $this->post_ID;
            }
        }
        elseif (is_admin()) {
            $this->error_notice(__('Please check the plugin configuration file
                to set up at least one custom post type or one category id.', 'dnas'));
        }
        return false;
    }

    /**
     * Get the post ID from the requested domain name.
     * Do not perform any redirection nor query alteration.
     * @return  Integer The Post ID.
     */
    public function get_post_ID() {
        if (empty($this->post_ID)) {
            $this->_get_post_ID_by_domain_name();
        }
        return $this->post_ID;
    }

    /**
     * Change any permalink value with the correct one.
     * @return  string  The filtered URL.
     */
    public function permalink_handler($url, $post = null) {
        if ($this->get_post_ID() == $post->ID) {
            return "http://$this->domain_name/";
        }
        return $url;
    }

    /**
     * Change the 'home' and 'siteurl' WP option to the requested domain name
     * @todo Do not work with base site URL like http://example.com/basepath/
     * @return String  The base URL
     */
    public function baseurl_handler () {
        return "http://$this->domain_name";
    }

    /**
     * Change the query to load the correct post, with the correct template.
     */
    public function query_handler($query) {
        if (is_home()
        &&  $query->is_main_query()
        &&  $this->_get_post_ID_by_domain_name()) {
            $query->set('post_type', $this->post_type);
            $query->set('p', $this->post_ID);
            $query->is_single = true;
            $query->is_singular = true;
            $query->is_home = false;
        }
    }

    /**
     * Add custom fields to specified post types, using Advanced
     * Custom Fields plugin.
     */
    public function create_dnas_custom_fields() {
        if (function_exists("register_field_group")
        && (!empty($this->_post_types_ID) || !empty($this->_categories_ID))) {
            $custom_field = array (
                'id' => 'acf_dnas-domain-name',
                'title' => __('Domain Name', 'dnas'),
                'fields' => array (
                    array (
                        'key' => 'field_530b5a92bbd22',
                        'label' => __('Dedicated Domain Name', 'dnas'),
                        'name' => $this->_field_ID,
                        'type' => 'text',
                        'instructions' => __('The domain name on which this post should be dislpayed as the front page.<br /> Do <strong>not</strong> mention the protocol (i.e.: <code style="margin:0 1px;padding:0 2px;font-size:inherit">http://</code>) <strong>nor</strong> any trailing slash (i.e.: <code style="margin:0 1px;padding:0 2px;font-size:inherit">/</code>).', 'dnas'),
                        'default_value' => '',
                        'placeholder' => 'ape.example.org',
                        'prepend' => '',
                        'append' => '',
                        'formatting' => 'none',
                        'maxlength' => '',
                    ),
                ),
                'options' => array (
                    'position' => 'side',
                    'layout' => 'default',
                    'hide_on_screen' => array (
                    ),
                ),
                'menu_order' => 0,
                'location' => array(),
            );
            $i = 0;
            if (!empty($this->_post_types_ID)) {
                foreach($this->_post_types_ID as $post_type) {
                    $custom_field['location'][][] = array (
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => $post_type,
                        'order_no' => 0,
                        'group_no' => $i++,
                    );
                }
            }
            if (!empty($this->_categories_ID)) {
                foreach($this->_categories_ID as $categorie) {
                    $custom_field['location'][][] = array (
                        'param' => 'post_category',
                        'operator' => '==',
                        'value' => $categorie,
                        'order_no' => 0,
                        'group_no' => $i++,
                    );
                }
            }
            register_field_group($custom_field);
        }
    }

    /**
     * Display a message error.
     * @param   string  $msg    The message error to display.
     */
    static function error_notice ($msg = null) {
        if(!isset($errors)) static $errors;
        if(!empty($msg)) $errors[] = $msg;
        elseif(!empty($errors)) {
            $errors = array_unique($errors);
            echo '<div class="error"><h3>Domain Name Autoswitch plugin</h3><ul>';
            foreach ($errors as $e) {
                echo "<li> $e </li>";
            }
            echo '</ul></div>';
        }
    }
}

// Initialize.
function dnas() {
    return Domain_Name_Autoswitch::get_instance();
}
$dnas = dnas();

// Get the post ID related to the current domain name.
function dnas_get_post_ID() {
    global $dnas;
    return $dnas->get_post_ID();
}
