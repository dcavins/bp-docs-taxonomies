<?php
/**
 * Apply existing taxonomies to your BuddyPress Docs library.
 *
 * @package   BPDocsTaxonomies
 * @author    dcavins
 * @license   GPL-2.0+
 * @copyright 2017 David Cavins
 *
 * @wordpress-plugin
 * Plugin Name:       BP Docs Taxonomies
 * Plugin URI:        https://github.com/dcavins/bp-docs-taxonomies
 * Description:       Apply existing taxonomies to your BuddyPress Docs library.
 * Version:           1.1.0
 * Author:            dcavins
 * Text Domain:       bp-docs-taxonomies
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * GitHub Plugin URI: https://github.com/dcavins/bp-docs-taxonomies
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

function bp_docs_taxonomies_init() {
	// Functions
	require_once( plugin_dir_path( __FILE__ ) . 'includes/functions.php' );
	require_once( plugin_dir_path( __FILE__ ) . 'includes/internal-functions.php' );

	// Template output functions
	require_once( plugin_dir_path( __FILE__ ) . 'public/views/template-tags.php' );

	// The main class
	require_once( plugin_dir_path( __FILE__ ) . 'public/class-bpdt.php' );
	$public_class = new BPDT_Public();
	$public_class->add_action_hooks();

	// Admin and dashboard functionality
	if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
		require_once( plugin_dir_path( __FILE__ ) . 'admin/class-bpdt-admin.php' );
		$admin_class = new BPDT_Admin();
		$admin_class->add_action_hooks();
	}

}
add_action( 'bp_docs_load', 'bp_docs_taxonomies_init' );

/**
 * Fetch the URI to the plugin's base directory.
 *
 * @return URI to the root of the plugin.
 */
function bpdt_get_plugin_base_uri(){
	return plugin_dir_url( __FILE__ );
}

/**
 * Fetch the path to the plugin's base directory.
 *
 * @return Directory path to the root of the plugin.
 */
function bpdt_get_plugin_base_name(){
	return plugin_basename( dirname( __FILE__ ) );
}

/**
 * Get the current version of the plugin.
 *
 * @return string Current version of plugin.
 */
function bpdt_get_plugin_version(){
	return '1.0.0';
}

/**
 * Get the current version of the plugin.
 *
 * @return string Current version of plugin.
 */
function bpdt_get_plugin_slug(){
	return 'bp-docs-taxonomies';
}
