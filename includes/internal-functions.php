<?php
/**
 * Functions for the plugin in the global scope.
 * These are utility functions and are not meant to be used by other plugins or developers.
 *
 * @package   BPDocsTaxonomies
 * @author    dcavins
 * @license   GPL-2.0+
 * @copyright 2017 David Cavins
 */

/**
 * Sanitize the saved option value from the administration screen.
 *
 * @since 1.0.0
 *
 * @return string Level of enforcement for overriding the default settings.
 */
function bpdt_sanitize_taxonomy_selection( $value ) {
	$retval = array();

	$valid = get_taxonomies( array( 'public' => true, 'show_ui' => true ), 'names' );
	$do_not_include = array(
		apply_filters( 'bp_docs_docs_tag_tax_name', 'bp_docs_tag' ),
		'bp_docs_folder_in_user',
		'bp_docs_folder_in_group',
		apply_filters( 'bp_docs_associated_item_tax_name', 'bp_docs_associated_item' ),
	);

	$valid = array_diff( $valid, $do_not_include );

	foreach ( $value as $k => $v ) {
		if ( in_array( $v, $valid ) ) {
			$retval[] = $v;
		}
	}

	return $retval;
}