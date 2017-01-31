<?php
/*
Plugin Name: Multisite Auth MailChimp Addon
Plugin URI: https://samelh.com/
Description: MailChimp addon for Multisite Auth plugin
Author: Samuel Elh
Version: 0.1
Author URI: https://samelh.com
Text Domain: muauth-mc
*/

// prevent direct access
defined('ABSPATH') || exit('Direct access not allowed.' . PHP_EOL);

class MUAUTH_MC
{
    /** Class instance **/
    protected static $instance = null;

    /** Constants **/
    public $constants;

    /** Get Class instance **/
    public static function instance()
    {
        return null == self::$instance ? new self : self::$instance;
    }

    public static function init()
    {
        return self::instance()
            ->setupConstants()
            ->setupGlobals();
    }

    /** define necessary constants **/
    public function setupConstants()
    {
        $this->constants = array(
            "MUAUTH_MC_FILE" => __FILE__,
            "MUAUTH_MC_DIR" => plugin_dir_path(__FILE__),
            "MUAUTH_MC_DOMAIN" => 'muauth-mc',
            "MUAUTH_MC_BASE" => plugin_basename(__FILE__)
        );

        foreach ( $this->constants as $constant => $def ) {
            if ( !defined( $constant ) ) {
                define( $constant, $def );
            }
        }

        return $this;
    }

    public function setupGlobals()
    {
        global $muauth_mc;

        $muauth_mc = (object) array(
            'api_key' => null,
            'lists' => array(),
            'check_by_default' => true,
            'label' => __('Sign me up for the newsletter!', MUAUTH_MC_DOMAIN),
            'activation' => false,
            'select_freedom' => true
        );

        return $this;
    }
}

// init
MUAUTH_MC::init();

// core filters
include MUAUTH_MC_DIR . 'includes/filters.php';
// core functions
include MUAUTH_MC_DIR . 'includes/functions.php';

if ( is_admin() ) {
    // admin filters
    include MUAUTH_MC_DIR . 'includes/admin/filters.php';
    // admin functions
    include MUAUTH_MC_DIR . 'includes/admin/functions.php';
}