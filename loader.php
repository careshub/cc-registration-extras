<?php
/**
 *
 * @package   CC Registration Extras
 * @author    CARES staff
 * @license   GPL-2.0+
 * @copyright 2014 CommmunityCommons.org
 *
 * Plugin Name: CC Registration Form Extras
 * Description: Adds e-mail confirmation field, a splogger check on the "About Me" field and provides instant registration (disables BuddyPress e-mail account verification).  
 * Version: 0.1
 * Requires at least: 3.5
 * Tested up to: 3.6
 * License: GPL3
 * Author: David Cavins
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
  die;
}

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 *
 */
// register_activation_hook( __FILE__, array( 'CC_Registration_Extras', 'activate' ) );
// register_deactivation_hook( __FILE__, array( 'CC_Registration_Extras', 'deactivate' ) );

/* Do our setup after BP is loaded, but before we create the group extension */
function cc_reg_extras_class_init() {

  // Helper functions
  // require_once( plugin_dir_path( __FILE__ ) . 'includes/ccgn-functions.php' );

  // The main class
  require_once( plugin_dir_path( __FILE__ ) . 'public/class-cc-registration-extras.php' );

  add_action( 'bp_include', array( 'CC_Registration_Extras', 'get_instance' ), 17 );

  // Admin and dashboard functionality
  if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {

    require_once( plugin_dir_path( __FILE__ ) . 'admin/class-admin-pane.php' );
    // add_action( 'bp_include', array( 'CC_Group_Narratives_Admin', 'get_instance' ), 21 );

  }

}
add_action( 'bp_include', 'cc_reg_extras_class_init' );