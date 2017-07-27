<?php
/**
 * Functions for the plugin in the global scope.
 * These may be useful for users working on theming or extending the plugin.
 *
 * @package   BPDocsTaxonomies
 * @author    dcavins
 * @license   GPL-2.0+
 * @copyright 2017 David Cavins
 */
function bpdt_get_selected_taxonomies() {
	$setting = bp_get_option( 'bpdt_use_taxonomies' );
	if ( empty( $setting ) ) {
		$setting = array();
	}
	return $setting;
}

/**
 * Creates the markup for the doc types filter links on the docs loop.
 *
 * @since 1.0.0
 *
 * @return array of the slug for currently active filters.
 */
function bpdt_get_archive_active_filters() {
	$filter_types = apply_filters( 'bp_docs_filter_types', array() );
	$active_filters = array();

	foreach ( $filter_types as $filter ) {
		if ( isset( $_GET[ $filter['query_arg'] ] ) ) {
			$active_filters[] = $filter['slug'];
		}
	}

	return $active_filters;
}
