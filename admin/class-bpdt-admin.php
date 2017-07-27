<?php
/**
 * BP Docs Taxonomies
 *
 * @package   BPDocsTaxonomies
 * @author    dcavins
 * @license   GPL-2.0+
 * @copyright 2017 David Cavins
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * administrative side of the WordPress site.
 *
 * If you're interested in introducing public-facing
 * functionality, then refer to `public/class-hgbp.php`
 *
 * @package   HierarchicalGroupsForBP_Admin
 * @author  dcavins
 */
class BPDT_Admin extends BPDT_Public {

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Add actions and filters to WordPress/BuddyPress hooks.
	 *
	 * @since    1.0.0
	 */
	public function add_action_hooks() {

		// Add the options page and menu item.
		add_action( bp_core_admin_hook(), array( $this, 'add_plugin_admin_menu' ), 99 );

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );

		// Add settings to the admin page.
		add_action( bp_core_admin_hook(), array( $this, 'settings_init' ) );

		/*
		 * Save settings. On networks, the settings API won't save settings, so
		 * we have to do it ourselves.
		 */
		add_action( 'bp_admin_init', array( $this, 'settings_save' ) );
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {
		$this->plugin_screen_hook_suffix = add_submenu_page(
			'edit.php?post_type=' . bp_docs_get_post_type_name(),
			__( 'BuddyPress Docs Taxonomies', 'bp-docs-taxonomies' ),
			__( 'Taxonomies', 'bp-docs-taxonomies' ),
			'bp_moderate',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {
		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'admin.php?page=' . $this->plugin_slug ) . '">' . __( 'Settings', 'bp-docs-taxonomies' ) . '</a>'
			),
			$links
		);
	}

	/**
	 * Register the settings and set up the sections and fields for the
	 * global settings screen.
	 *
	 * @since    1.0.0
	 */
	public function settings_init() {

		// Setting for showing groups directory as tree.
		add_settings_section(
			'bpdt_apply_taxonomies',
			__( 'Select Taxonomies', 'bp-docs-taxonomies' ),
			array( $this, 'apply_taxonomies_section_callback' ),
			$this->plugin_slug
		);

		// Registering the setting. (Single site uses the settings API to save settings.)
		register_setting( $this->plugin_slug, 'bpdt_use_taxonomies', array( 'sanitize_callback' => 'bpdt_sanitize_taxonomy_selection' ) );
		add_settings_field(
			'bpdt_use_taxonomies',
			__( 'Choose which taxonomies you wish to use with BuddyPress Docs.', 'bp-docs-taxonomies' ),
			array( $this, 'render_bpdt_use_taxonomies' ),
			$this->plugin_slug,
			'bpdt_apply_taxonomies'
		);
	}

	/**
	 * Provide a section description for the global settings screen.
	 *
	 * @since    1.0.0
	 */
	public function apply_taxonomies_section_callback() {}

	/**
	 * Set up the fields for the global settings screen.
	 *
	 * @since    1.0.0
	 */
	public function render_bpdt_use_taxonomies() {
		// What is the value of our setting?
		$setting = bpdt_get_selected_taxonomies();
		// What taxonomies have been registered that we could use?
		$taxonomies = get_taxonomies( array( 'public' => true, 'show_ui' => true ), 'objects' );
		// What taxonomies are associated with the bp_docs post type?
		// $docs_taxonomies = get_object_taxonomies( bp_docs_get_post_type_name(), 'names' );
		// echo '<pre>docs_taxonomies'; var_dump($docs_taxonomies); echo '</pre>';

		/*
		 * Taxonomies required for bp_docs can't be disassociated.
		 * Must apply filters in the unlikely event someone is filtering the taxonomy ID.
		 */
		$required_taxonomies = array(
			apply_filters( 'bp_docs_docs_tag_tax_name', 'bp_docs_tag' ),
		);

		// Don't show the folder or group/user association terms.
		$utility_taxonomies = array(
			'bp_docs_folder_in_user',
			'bp_docs_folder_in_group',
			apply_filters( 'bp_docs_associated_item_tax_name', 'bp_docs_associated_item' ),
		);

		foreach ( $taxonomies as $key => $value ) {
			// Don't display utility taxonomies, these confuse people so much.
			if ( in_array( $key, $utility_taxonomies, true ) ) {
				continue;
			}

			$checked  = '';
			$disabled = '';

			if ( in_array( $key, $setting, true ) ) {
				$checked = ' checked="checked"';
			}

			// If this is a "built-in to BP Docs taxonomy", show it checked and disable the input.
			if ( in_array( $key, $required_taxonomies, true ) ) {
				$checked = ' checked="checked"';
				$disabled = ' disabled="disabled"';
			}

			?>
			<label for="bpdt-taxonomies-<?php echo $key; ?>"><input type="checkbox" id="bpdt-taxonomies-<?php echo $key; ?>" name="bpdt_use_taxonomies[<?php echo $key; ?>]" value="<?php echo $key; ?>"<?php echo $checked; echo $disabled; ?>>
			<?php echo $value->label; if ( $disabled ) { echo ' <em>'; _e( 'This taxonomy is included with BuddyPress Docs.' , 'bp-docs-taxonomies' ); echo '</em>'; } ?>
			</label><br />
			<?php
		}
	}

	/**
	 * Save settings. This can't be done using the Settings API, because
	 * the API doesn't handle saving settings in network admin. This function
	 * handles saving the plugin's global settings in both the single site and
	 * network admin contexts.
	 *
	 * @since    1.0.0
	 */
	public function settings_save() {
		if ( ! isset( $_POST['option_page'] ) || $this->plugin_slug != $_POST['option_page'] ) {
			return;
		}

		/*
		 * Check nonce.
		 * Nonce name as set in settings_fields(), used to output the form's meta inputs.
		 */
		if ( ! check_admin_referer( $this->plugin_slug . '-options' ) ) {
			return;
		}

		// Check that user has the proper capability.
		if ( ! current_user_can( 'bp_moderate' ) ) {
			return;
		}

		// Clean up the passed values and update the stored values.
		$fields = array(
			'bpdt_use_taxonomies' => 'bpdt_sanitize_taxonomy_selection',
		);
		foreach ( $fields as $key => $sanitize_callback ) {
			$value = isset( $_POST[ $key ] ) ? $_POST[ $key ] : '';
			$value = call_user_func( $sanitize_callback, $value );
			bp_update_option( $key, $value );
		}

		// Redirect back to the form.
		$redirect = bp_get_admin_url( add_query_arg( array( 'page' => $this->plugin_slug, 'updated' => 'true' ), 'admin.php' ) );
		wp_redirect( $redirect );
		die();
	}

	/**
	 * Render the global settings screen for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		// Thickbox is used to display the labels location images in a modal window.
		add_thickbox();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<hr class="wp-header-end">
			<?php
			if ( ! empty( $_REQUEST[ 'updated' ] ) ) {
				?>
				<div id="message" class="updated notice notice-success">
					<p><?php _e( 'Settings updated.', 'bp-docs-taxonomies' ); ?></p>
				</div>
				<?php
			}
			?>
			<form action="<?php echo bp_get_admin_url( add_query_arg( array( 'page' => $this->plugin_slug ), 'admin.php' ) ); ?>" method="post">
				<?php
				settings_fields( $this->plugin_slug );
				do_settings_sections( $this->plugin_slug );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
