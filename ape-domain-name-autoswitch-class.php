<?php
/**
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

/**
 * Manages domain name autoswitch for corresponding content:
 * Displays a particular post for each different domain name requested.
 */
class Domain_Name_Autoswitch {

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
   * $wpdb WordPress Object
   * @var DB_Query
   */
  private $_wpdb;

  /**
   * Constructor
   * @param Array $categories_ID ID of the categories used to select contents
   * @param String $field_ID ID of the field used to catch domain name value for each content
   * @return void
   */
  public function __construct ( $categories_ID = array(), $field_ID = null ) {

    // Populate private properties
    $this->_categories_ID = $categories_ID;
    $this->_field_ID    = $field_ID;

    // Get domain name request
    $this->domain_name = $_SERVER['SERVER_NAME'];

    // WP database reference
    global $wpdb;
    $this->_wpdb = &$wpdb;
  }

  /**
   * Look for content which corresponds to the domain name request
   * @return Integer|False The corresponding content ID, False if corresponding content not found
   */
  private function _get_content_ID_by_domain_name () {
    $dn = esc_sql($this->domain_name);
    $categories = implode(', ', $this->_categories_ID);
    $sql = "SELECT post_id AS id
            FROM {$this->_wpdb->postmeta}
            WHERE post_id IN (
              SELECT object_id
              FROM {$this->_wpdb->term_relationships}
              WHERE term_taxonomy_id IN ( $categories )
            )
            AND meta_key = '{$this->_field_ID}'
            AND meta_value = '$dn'
            LIMIT 1";
    $id_from_db = $this->_wpdb->get_row($sql);
    if (!empty($id_from_db) && !empty($id_from_db->id)) {
      $this->content_ID = $id_from_db->id;
      return $this->content_ID;
    }
    return false;
  }

  /**
   * Change the 'home' and 'siteurl' WP option to the requested domain name
   * @todo Do not work with base site URL like http://example.com/basepath/
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
}
