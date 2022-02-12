<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://theboldtype.com
 * @since      1.0.0
 *
 * @package    Tbt_Shared_Inventory
 * @subpackage Tbt_Shared_Inventory/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Tbt_Shared_Inventory
 * @subpackage Tbt_Shared_Inventory/includes
 * @author     Adam Smith <adam@theboldtype.com>
 */
class Tbt_Shared_Inventory {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Tbt_Shared_Inventory_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'TBT_SHARED_INVENTORY_VERSION' ) ) {
			$this->version = TBT_SHARED_INVENTORY_VERSION;
		} else {
			$this->version = '1.0.1';
		}
		$this->plugin_name = 'tbt-shared-inventory';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Tbt_Shared_Inventory_Loader. Orchestrates the hooks of the plugin.
	 * - Tbt_Shared_Inventory_i18n. Defines internationalization functionality.
	 * - Tbt_Shared_Inventory_Admin. Defines all hooks for the admin area.
	 * - Tbt_Shared_Inventory_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-tbt-shared-inventory-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-tbt-shared-inventory-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-tbt-shared-inventory-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-tbt-shared-inventory-public.php';
		
		$this->loader = new Tbt_Shared_Inventory_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Tbt_Shared_Inventory_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Tbt_Shared_Inventory_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Tbt_Shared_Inventory_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		// $this->loader->add_action( 'init', $plugin_admin, 'tbt_share_inventory_register_acf_fields', 15 );

		//add option to product
		$this->loader->add_action( 'woocommerce_product_options_related', $plugin_admin, 'tbt_shared_inventory_add_product_related_option');
		$this->loader->add_action( 'woocommerce_process_product_meta', $plugin_admin, 'tbt_shared_inventory_save_product_related_setting', 10, 2 );
		$this->loader->add_filter( 'product_type_options', $plugin_admin, 'tbt_shared_inventory_add_is_bundle_option');
		$this->loader->add_action( 'save_post_product', $plugin_admin, 'tbt_shared_inventory_save_is_bundle', 10, 3);

		//add options to product variations
		$this->loader->add_action( 'woocommerce_variation_options_pricing', $plugin_admin, 'tbt_shared_inventory_add_variation_stock_options', 20, 3 );
		$this->loader->add_action( 'woocommerce_save_product_variation', $plugin_admin, 'tbt_shared_inventory_save_variation_reduction_setting', 10, 2 );
		$this->loader->add_action( 'woocommerce_order_item_quantity', $plugin_admin, 'tbt_shared_inventory_stock_adjustment', 10, 3 );
		$this->loader->add_action( 'wp_ajax_get_wc_products', $plugin_admin, 'get_wc_products');
		$this->loader->add_action( 'woocommerce_variation_options', $plugin_admin, 'tbt_shared_inventory_variation_add_is_bundle_option', 20, 3 );
		$this->loader->add_action( 'wp_ajax_get_new_product_row', $plugin_admin, 'tbt_shared_inventory_ajax_get_product_row');
		
		// adjust stock accordingly after checkout
		$this->loader->add_action('woocommerce_order_status_completed', $plugin_admin, 'tbt_shared_inventory_order_reduce_stock', 10, 1);
		$this->loader->add_action('woocommerce_order_status_on-hold', $plugin_admin, 'tbt_shared_inventory_order_reduce_stock', 10, 1);
		$this->loader->add_action('woocommerce_order_status_processing', $plugin_admin, 'tbt_shared_inventory_order_reduce_stock', 10, 1);
		// $this->loader->add_action( 'woocommerce_order_status_cancelled', $plugin_admin, 'tbt_shared_inventory_order_return_to_stock', 10, 1);
		$this->loader->add_action( 'woocommerce_order_partially_refunded', $plugin_admin, 'tbt_shared_inventory_order_return_to_stock', 10, 2);
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Tbt_Shared_Inventory_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		$this->loader->add_action( 'woocommerce_single_product_summary', $plugin_public, 'tbt_shared_inventory_product_bundle_display', 11 );
		$this->loader->add_filter( 'woocommerce_cart_item_name', $plugin_public, 'tbt_shared_inventory_cart_item_name', 10, 2 );
		$this->loader->add_filter( 'woocommerce_order_item_name', $plugin_public, 'tbt_shared_inventory_cart_item_name', 10, 2 );

		//process shared inventory settings
		$this->loader->add_action( 'woocommerce_add_to_cart_validation', $plugin_public, 'tbt_shared_inventory_check_stock_cart', 10, 5);
		$this->loader->add_action( 'woocommerce_add_to_cart', $plugin_public, 'tbt_shared_inventory_split_order_bundle', 10, 6);
		$this->loader->add_filter( 'woocommerce_cart_item_visible', $plugin_public, 'tbt_shared_inventory_item_visible', 10, 2 );
		$this->loader->add_filter( 'woocommerce_order_item_visible', $plugin_public, 'tbt_shared_inventory_item_visible', 10, 2 );
		$this->loader->add_filter( 'woocommerce_checkout_cart_item_visible', $plugin_public, 'tbt_shared_inventory_item_visible', 10, 2 );
		$this->loader->add_filter( 'woocommerce_cart_contents_count', $plugin_public, 'tbt_shared_inventory_cart_contents_count' );
		$this->loader->add_action( 'woocommerce_cart_item_removed', $plugin_public, 'tbt_shared_inventory_cart_item_removed', 10, 2 );
		$this->loader->add_action( 'woocommerce_restore_cart_item', $plugin_public, 'tbt_shared_inventory_restore_cart_item', 10, 1 );
		$this->loader->add_filter( 'woocommerce_get_cart_item_from_session', $plugin_public, 'tbt_shared_inventory_get_cart_item_from_session', 10, 2 );
		$this->loader->add_filter( 'woocommerce_cart_item_quantity', $plugin_public, 'tbt_shared_inventory_cart_item_quantity', 10, 3 );
		$this->loader->add_filter( 'woocommerce_cart_item_price', $plugin_public, 'tbt_shared_inventory_cart_item_price', 10, 2 );
		$this->loader->add_filter( 'woocommerce_cart_item_subtotal', $plugin_public, 'tbt_shared_inventory_cart_item_subtotal', 10, 2 );
		$this->loader->add_action( 'woocommerce_before_calculate_totals', $plugin_public, 'tbt_shared_inventory_before_calculate_totals', 10 );
		$this->loader->add_filter( 'woocommerce_cart_item_price', $plugin_public, 'tbt_shared_inventory_cart_item_price', 10, 2 );
		$this->loader->add_filter( 'woocommerce_cart_item_subtotal', $plugin_public, 'tbt_shared_inventory_cart_item_subtotal', 10, 2 );
		$this->loader->add_action( 'woocommerce_after_cart_item_quantity_update', $plugin_public, 'tbt_shared_inventory_after_cart_item_quantity_update', 20, 4 );
		$this->loader->add_action( 'woocommerce_after_checkout_validation', $plugin_public, 'tbt_shared_inventory_check_stock_checkout', 10, 2);

		$this->loader->add_action( 'woocommerce_checkout_create_order_line_item', $plugin_public, 'tbt_shared_inventory_add_order_item_meta', 10, 3 );
		$this->loader->add_filter( 'woocommerce_get_item_count', $plugin_public, 'tbt_shared_order_get_item_count', 10, 3 );
		$this->loader->add_filter( 'woocommerce_hidden_order_itemmeta', $plugin_public, 'tbt_shared_hidden_order_item_meta', 10, 1 );
		$this->loader->add_filter( 'woocommerce_order_formatted_line_subtotal', $plugin_public, 'tbt_shared_order_formatted_line_subtotal', 10, 2 );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Tbt_Shared_Inventory_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
