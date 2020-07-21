<?php
/**
 * The public class.
 *
 * @package   BPDocsTaxonomies
 * @author    dcavins
 * @license   GPL-2.0+
 * @copyright 2017 David Cavins
 */

class BPDT_Public {

	/**
	 *
	 * The current version of the plugin.
	 *
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $version = '1.0.0';

	/**
	 *
	 * Unique identifier for your plugin.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'bp-docs-taxonomies';

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	public function __construct() {
		$this->version     = bpdt_get_plugin_version();
		$this->plugin_slug = bpdt_get_plugin_slug();
	}

	/**
	 * Add actions and filters to WordPress/BuddyPress hooks.
	 *
	 * @since    1.0.0
	 */
	public function add_action_hooks() {

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ) );

		// Apply the selected taxonomies to bp_docs
		add_action( 'bp_docs_load', array( $this, 'load_plugin_textdomain' ), 12 );

		// Apply the selected taxonomies to bp_docs
		add_action( 'bp_docs_init', array( $this, 'apply_taxonomies_to_bp_docs' ) );

		/* Changes to the BP Docs Archive *************************************/
		// Add the filter markup
		add_filter( 'bp_docs_filter_types', array( $this, 'add_filter_toggles' ) );
		add_action( 'bp_docs_filter_sections', 'bpdt_add_archive_filter_markup' );

		// Add "type" and "channel" to the current filters when viewing the docs directory.
		add_filter( 'bp_docs_get_current_filters', array( $this, 'add_tax_filters' ) );
		// Add some header info when a filter is selected
		add_filter( 'bp_docs_info_header_message', array( $this, 'info_header_message' ), 11, 2 );
		// Modify the main tax_query in the doc loop
		add_filter( 'bp_docs_tax_query', array( $this, 'tax_query_filter' ), 10, 2 );
		// Determine whether the directory view is filtered by category.
		add_filter( 'bp_docs_is_directory_view_filtered', array( $this, 'is_directory_view_filtered' ), 10, 2 );

		/* Changes to the Docs edit view. *************************************/
		// Add a meta box for custom taxonomies to the docs edit screen
		add_action( 'bp_docs_after_tags_meta_box', 'bpdt_terms_metaboxes', 10, 1 );
		// Save term selections from doc edit screen.
		add_action( 'bp_docs_doc_saved', array( $this, 'save_terms' ) );

		/* Changes to the doc single view *************************************/
		// Display a doc's other terms on the single doc page
		add_filter( 'bp_docs_taxonomy_show_terms', 'bpdt_add_terms_single_doc', 10, 2 );
		// Add terms output to the docs-loop title cell
		add_action( 'bp_docs_loop_after_doc_excerpt', 'bpdt_add_terms_docs_loop' );

	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 *
	 * @return   string Plugin slug.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Loads the textdomain for the plugin.
	 * Language files are used in this order of preference:
	 *    - WP_LANG_DIR/plugins/bp-docs-taxonomies-LOCALE.mo
	 *    - WP_PLUGIN_DIR/bp-docs-taxonomies/languages/bp-docs-taxonomies-LOCALE.mo
	 *
	 * @since 1.1.0
	 */
	public function load_plugin_textdomain() {
		/*
		 * As of WP 4.6, WP has, by this point in the load order, already
		 * automatically added language files in this location:
		 * wp-content/languages/plugins/bp-docs-taxonomies-es_ES.mo
		 * load_plugin_textdomain() also looks for language files in that location,
		 * then it falls back to translations in the plugin's /languages folder, like
		 * wp-content/plugins/bp-docs-taxonomies/languages/bp-docs-taxonomies-es_ES.mo
		 */
		load_plugin_textdomain( 'bp-docs-taxonomies', false, bpdt_get_plugin_base_name() . '/languages' );
	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles_scripts() {
		// @todo: scope this?
		// Styles
		if ( is_rtl() ) {
			wp_enqueue_style( $this->plugin_slug . '-plugin-styles-rtl', plugins_url( 'css/public-rtl.css', __FILE__ ), array(), $this->version );
		} else {
			wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'css/public.css', __FILE__ ), array(), $this->version );
		}

		// Scripts
		wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'js/public.min.js', __FILE__ ), array( 'jquery' ), $this->version );
	}


	/**
	 * Apply the selected taxonomies to bp_docs.
	 *
	 * @since    1.0.0
	 */
	public function apply_taxonomies_to_bp_docs() {
		$taxonomies = bpdt_get_selected_taxonomies();
		$post_type  = bp_docs_get_post_type_name();
		foreach ( $taxonomies as $taxonomy ) {
			register_taxonomy_for_object_type( $taxonomy, $post_type );
		}
	}

	/**
	 * Adds the toggle for the doc types filter links container on the docs loop.
	 *
	 * @since 1.0.0
	 *
 	 * @param array $types Filter descriptions for docs list archive filters.
	 */
	public function add_filter_toggles( $types ) {
		$taxonomies = bpdt_get_selected_taxonomies();
		foreach ( $taxonomies as $tax_name ) {
			$taxonomy = get_taxonomy( $tax_name );
			if ( ! empty( $taxonomy ) ) {
				$types[] = array(
					'slug'      => $taxonomy->name,
					'title'     => $taxonomy->labels->singular_name,
					'query_arg' => $taxonomy->query_var,
				);
			}
		}

		return $types;
	}

	/**
	 * Add taxonomy-related filters to the list of current directory filters.
	 *
	 * @since 1.0.0
	 *
	 * @param array $filters
	 * @return array
	 */
	function add_tax_filters( $filters ) {
		$taxonomies = bpdt_get_selected_taxonomies();
		foreach ( $taxonomies as $tax_name ) {
			$taxonomy = get_taxonomy( $tax_name );
			// Are we filtering by this taxonomy?
			if ( ! empty( $_REQUEST[$taxonomy->query_var] ) ) {
				// The tax argument may be comma-separated
				$terms = explode( ',', urldecode( $_REQUEST[$taxonomy->query_var] ) );

				foreach ( $terms as $term ) {
					$filters[$taxonomy->name][] = $term;
				}
			}
		}

		return $filters;
	}

	/**
	 * Modifies the info header message to account for current filters.
	 *
	 * @since 1.0.0
	 *
	 * @param array $message An array of the messages explaining the current view
	 * @param array $filters The filters pulled out of the $_REQUEST global
	 *
	 * @return array $message The maybe modified message array
	 */
	function info_header_message( $message, $filters ) {
		$taxonomies = bpdt_get_selected_taxonomies();
		foreach ( $taxonomies as $tax_name ) {
			$taxonomy = get_taxonomy( $tax_name );

			// Are we filtering by this taxonomy?
			if ( ! empty( $filters[$taxonomy->name] ) ) {
				$termtext = array();

				foreach ( $filters[$taxonomy->name] as $filter_term ) {
					$term = get_term_by( 'slug', $filter_term, $tax_name );
					$termtext[] = bpdt_get_term_link( array(
						'query_var' => $taxonomy->query_var,
						'term' => $term->slug,
						'name' => $term->name,
						'type' => 'html'
					) );
				}

				$message[] = sprintf( __( 'You are viewing docs with the %s: %s', 'bp-docs-taxonomies' ), $taxonomy->label, implode( ', ', $termtext ) );
			}
		}

		return $message;
	}

	/**
	 * Modifies the tax_query on the doc loop to account for new taxonomies.
	 *
	 * @since 1.0.0
	 *
	 * @param array $tax_query a WP-ready tax_query array.
	 * @return array $tax_query a WP-ready tax_query array.
	 */
	public function tax_query_filter( $tax_query ) {
		$check_operator = false;

		$taxonomies = bpdt_get_selected_taxonomies();
		foreach ( $taxonomies as $tax_name ) {
			$taxonomy = get_taxonomy( $tax_name );
			// Are we filtering by this taxonomy?
			if ( ! empty( $_REQUEST[$taxonomy->query_var] ) ) {
				// The tax argument may be comma-separated
				$terms = explode( ',', urldecode( $_REQUEST[$taxonomy->query_var] ) );

				// Clean up the input
				$terms = array_map( 'esc_attr', $terms );

				$tax_query[] = array(
					'taxonomy'	=> $taxonomy->name,
					'terms'		=> $terms,
					'field'		=> 'slug',
				);

				$check_operator = true;
			}
		}

		if ( $check_operator ) {
			if ( ! empty( $_REQUEST['bool'] ) && $_REQUEST['bool'] == 'and' ) {
				$tax_query['operator'] = 'AND';
			}
		}

		return $tax_query;
	}

	/**
	 * Determine whether the directory view is filtered by one of our taxonomies.
	 *
	 * @since 1.0.0
	 *
	 * @param bool  $is_filtered Is the current directory view filtered?
	 * @param array $exclude Array of filter types to ignore.
	 *
	 * @return bool $is_filtered
	 */
	public function is_directory_view_filtered( $is_filtered, $exclude ) {
		$taxonomies = bpdt_get_selected_taxonomies();
		foreach ( $taxonomies as $tax_name ) {
			$taxonomy = get_taxonomy( $tax_name );
			// If this filter is not excluded, and applied, stop now.
			if ( ! in_array( $taxonomy->query_var, $exclude ) && isset( $_GET[$taxonomy->query_var] ) ) {
				$is_filtered = true;
				break;
			}
		}

	    return $is_filtered;
	}

	/* Changes to the front-end Docs edit view. *******************************/

	/**
	 * Save terms selected from the front-end edit form.
	 *
	 * @since 1.0.0
	 *
	 * @param $query Docs_Query instance.
	 */
	public function save_terms( $query ) {
		$taxonomies = bpdt_get_selected_taxonomies();
		foreach ( $taxonomies as $tax_name ) {
			/*
			 * God knows why but wp_terms_checklist() outputs 'category' terms
			 * as 'post_category'-named inputs.
			 * And, wp_set_post_terms() doesn't recognize 'post_category'
			 * as a taxonomy.
			 */
			if ( 'category' == $tax_name ) {
				$terms = ! empty( $_POST['post_category'] ) ? array_map( 'absint', $_POST['post_category'] ) : array();
			} else {
				// And every other taxonomy is structured differently. Of course they are.
				$terms = ! empty( $_POST['tax_input'][$tax_name] ) ? array_map( 'absint', $_POST['tax_input'][$tax_name] ) : array();
			}

			wp_set_post_terms( $query->doc_id, $terms, $tax_name );
		}

	}

}
