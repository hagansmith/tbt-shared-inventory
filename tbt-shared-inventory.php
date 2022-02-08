<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://theboldtype.com
 * @since             1.0.0
 * @package           Tbt_Shared_Inventory
 *
 * @wordpress-plugin
 * Plugin Name:       Woo Shared Inventory
 * Plugin URI:        https://theboldtype.com/shared-inventory
 * Description:       Create products and variants that are made up of other products or variants
 * Version:           1.0.0
 * Author:            Adam Smith
 * Author URI:        https://theboldtype.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       tbt-shared-inventory
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'TBT_SHARED_INVENTORY_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-tbt-shared-inventory-activator.php
 */
function activate_tbt_shared_inventory() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-tbt-shared-inventory-activator.php';
	Tbt_Shared_Inventory_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-tbt-shared-inventory-deactivator.php
 */
function deactivate_tbt_shared_inventory() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-tbt-shared-inventory-deactivator.php';
	Tbt_Shared_Inventory_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_tbt_shared_inventory' );
register_deactivation_hook( __FILE__, 'deactivate_tbt_shared_inventory' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-tbt-shared-inventory.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_tbt_shared_inventory() {

	$plugin = new Tbt_Shared_Inventory();
	$plugin->run();

}

/**
 * Confirm Woocommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	run_tbt_shared_inventory();

} else {
	add_action( 'admin_notices', 'tbt_shared_inventory_woocommerce_notice' );

}

function tbt_shared_inventory_woocommerce_notice() {
  ?>
	<div class="error">
		<p><?php _e( '<strong>Shared Inventory</strong> requires Woocommerce to be installed and active. Please activate Woocommerce to continue.', 'tbt-shared-inventory' ); ?></p>
	</div>
  <?php
}
