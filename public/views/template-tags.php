<?php
/**
 * @package   BPDocsTaxonomies
 * @author    dcavins
 * @license   GPL-2.0+
 * @copyright 2017 David Cavins
 */

/**
 * Creates the markup for the doc types filter links on the docs loop.
 *
 * @since 1.0.0
 *
 * @return string html for filter controls.
 */
function bpdt_add_archive_filter_markup() {
	$active_filters = bpdt_get_archive_active_filters();
	$taxonomies = bpdt_get_selected_taxonomies();
	foreach ( $taxonomies as $tax_name ) {
		$taxonomy = get_taxonomy( $tax_name );
		$toggle_class = ( in_array( $taxonomy->name, $active_filters ) ) ? ' docs-filter-section-open' : '';
		$terms = get_terms( array(
			'taxonomy'   => $taxonomy->name,
			'hide_empty' => true
		) );
		?>
		<div id="docs-filter-section-<?php echo $taxonomy->name; ?>" class="docs-filter-section<?php echo $toggle_class; ?>">
			<ul id="<?php echo $taxonomy->name; ?>-list" class="term-filter-links">
			<?php if ( ! empty( $terms ) ) : ?>
				<?php foreach ( $terms as $term ) :	?>
					<li>
						<a href="<?php echo bpdt_get_term_link( array( 'query_var' => $taxonomy->query_var, 'term' => $term->slug, 'name' => $term->name, 'type' => 'url' ) ); ?>" title="<?php echo esc_html( $term->name ) ?>"><?php echo esc_html( $term->name ) ?></a>
					</li>
				<?php endforeach; ?>
			<?php else: ?>
				<li><?php _e( 'No terms to show.', 'bp-docs-taxonomies' )  ?></li>
			<?php endif; ?>
			</ul>
		</div>
		<?php
	}
}

/**
 * Get an archive link for a given term
 *
 * Optional arguments:
 *  - 'query_var' Required. The taxonomy's query_var to check.
 *  - 'term' 	  Required. The term linked to.
 *  - 'type' 	 'html' returns a link; anything else returns a URL
 *
 * @since 1.0.0
 *
 * @param array $args Optional arguments
 * @return string URL or link
 */
function bpdt_get_term_link( $args = array() ) {
	global $bp;

	$defaults = array(
		'query_var' => false,
		'term' 	    => false,
		'name'      => false,
		'type'    	=> 'html',
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	if ( bp_is_user() ) {
		$current_action = bp_current_action();
		$item_docs_url = bp_displayed_user_domain() . bp_docs_get_docs_slug() . '/';
		if ( empty( $current_action ) || BP_DOCS_STARTED_SLUG == $current_action ) {
			$item_docs_url = bp_docs_get_displayed_user_docs_started_link();
		} elseif ( BP_DOCS_EDITED_SLUG == $current_action ) {
			$item_docs_url = bp_docs_get_displayed_user_docs_edited_link();
		}
	} elseif ( bp_is_active( 'groups' ) && $current_group = groups_get_current_group() ) {
		/*
		 * Pass the group object to bp_get_group_permalink() so that it works
		 * when $groups_template may not be set, like during AJAX requests.
		 */
		$item_docs_url = trailingslashit( bp_get_group_permalink( $current_group ) . bp_docs_get_docs_slug() );
	} else {
		$item_docs_url = bp_docs_get_archive_link();
	}

	$url = apply_filters( 'bp_docs_get_tag_link_url', add_query_arg( urlencode( $query_var ), urlencode( $term ), $item_docs_url ), $args, $item_docs_url );

	if ( $type != 'html' )
		return apply_filters( 'bp_docs_get_tag_link_url', $url, $term, $type );

	$html = '<a href="' . $url . '" title="' . sprintf( __( 'Docs tagged %s', 'bp-docs-taxonomies' ), esc_attr( $term ) ) . '">' . esc_html( $name ) . '</a>';

	return apply_filters( 'bp_docs_get_term_link', $html, $url, $term, $type );
}

/**
 * Markup for the terms meta boxes on the front-end docs edit screen.
 *
 * @since 1.0.0
 *
 * @param int ID of the doc
 *
 * @return string html markup
 */
function bpdt_terms_metaboxes( $doc_id ) {
	// Make sure that wp_terms_checklist() will work.
	require_once( ABSPATH . 'wp-admin/includes/template.php' );

	$taxonomies = bpdt_get_selected_taxonomies();
	foreach ( $taxonomies as $tax_name ) {
		$taxonomy = get_taxonomy( $tax_name );
		?>
		<div id="doc-<?php echo $tax_name; ?>" class="doc-meta-box">
			<div class="toggleable <?php bp_docs_toggleable_open_or_closed_class(); ?>">
				<p id="<?php echo $tax_name; ?>-toggle-edit" class="toggle-switch">
					<span class="hide-if-js toggle-link-no-js"><?php echo $taxonomy->label; ?></span>
					<a class="hide-if-no-js toggle-link" id="<?php echo $tax_name; ?>-toggle-link" href="#"><span class="show-pane plus-or-minus"></span><?php echo $taxonomy->label; ?></a>
				</p>

				<div class="toggle-content">
					<table class="toggle-table" id="toggle-table-<?php echo $tax_name; ?>">
						<tr>
							<td class="desc-column">
								<label for="bp_docs_<?php echo $tax_name; ?>"><?php _e( 'Select the terms that describe your Doc.', 'bp-docs-taxonomies' ) ?></label>
							</td>

							<td>
								<ul class="bpdt-term-select">
									<?php
									/**
									 * Filters the arguments array passed to wp_terms_checklist().
									 *
									 * @since 1.0.0
									 *
									 * @param array $checklist_args Arguments for wp_terms_checklist().
									 */
									$checklist_args = apply_filters( 'bpdt_edit_metabox_terms_checklist_args', array( 'taxonomy' => $tax_name ) );
									wp_terms_checklist( $doc_id, $checklist_args );
									?>
								</ul>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>
	<?php
	}
}

/**
 * Shows a doc's terms on the single doc view.
 *
 * @since 1.0.0
 *
 * @param string $html Calculated markup for tag links.
 * @param string $tag_array Tags for the doc.
 * @return string html markup
 */
function bpdt_add_terms_single_doc( $html, $tag_array ) {
	$output = bpdt_build_terms_list();

	// Add the BP Docs tags back in.
	if ( ! empty( $tag_array ) ) {
		$taxonomy = get_taxonomy( apply_filters( 'bp_docs_docs_tag_tax_name', 'bp_docs_tag' ) );
		$tags_list = implode( ' ', $tag_array );
		$output .= $taxonomy->label . ' <span class="tag-links">'. $tags_list . '</span> <br />';
	}

	if ( $output ) {
		echo '<footer class="entry-meta">' . $output . '</footer>';
	}
}

/**
 * Shows a doc's channels on the docs table view.
 *
 * @since 1.0.0
 *
	 * @return string html markup
 */
function bpdt_add_terms_docs_loop() {
	$output = bpdt_build_terms_list();

	if ( $output ) {
		echo '<footer class="entry-meta">' . $output . '</footer>';
	}
}

function bpdt_build_terms_list() {
	$output = '';

	$taxonomies = bpdt_get_selected_taxonomies();
	foreach ( $taxonomies as $tax_name ) {
		$taxonomy = get_taxonomy( $tax_name );
		$terms = wp_get_post_terms( get_the_ID(), $tax_name );

		$link_array = array();
	    foreach ( $terms as $term ) {
	    	$link_array[] = bpdt_get_term_link( array(
				'query_var' => $taxonomy->query_var,
				'term' 	    => $term->slug,
				'name'      => $term->name,
				'type'    	=> 'html',
			) );
	    }

    	if ( ! empty( $link_array ) ) {
			$term_list = implode( ' ', $link_array );
			$output .= $taxonomy->label . ' <span class="category-links">'. $term_list . '</span> <br />';
		}
	}

	return $output;
}
