<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://theboldtype.com
 * @since      1.0.0
 *
 * @package    Tbt_Shared_Inventory
 * @subpackage Tbt_Shared_Inventory/admin
 */

use function Composer\Autoload\includeFile;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Tbt_Shared_Inventory
 * @subpackage Tbt_Shared_Inventory/admin
 * @author     Adam Smith <adam@theboldtype.com>
 */
class Tbt_Shared_Inventory_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/tbt-shared-inventory-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		global $pagenow;

		wp_localize_script( $this->plugin_name, 'admin_script_vars',
			array(
				'ajaxurl'    => admin_url( 'admin-ajax.php' ),
			)
		);
		
		if ( ( isset( $_GET['post'] ) && get_post_type( $_GET['post'] ) == 'product' ) 
		       || ( isset( $_GET['post_type'] ) && $_GET['post_type'] == 'product' && $pagenow == 'post-new.php' )
		   ) {
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/tbt-shared-inventory-admin.js', array( 'jquery' ), $this->version, false );
		}
	}

	/**
	 * Get an empty product row for adding a bundle
	 *
	 * @return void
	 */
	function tbt_shared_inventory_ajax_get_product_row () {
		global $thepostid;
		if ( ! wp_doing_ajax() ) {
			return;
		}

		if ( ! isset( $_GET ) ) {
			wp_send_json_error();

		}

		$key 		= $_GET['key'];
		$type 		= $_GET['type'];
		$loop 		= $_GET['loop'];
		$thepostid 	= $_GET['post'];

		json_encode( include( dirname(__FILE__) . "/partials/tbt-shared-inventory-product-row.php" ) );
		wp_die();

	}

	/**
	 * Add product rows to product
	 *
	 * @return void
	 */
	function tbt_shared_inventory_add_product_related_option ( ) {

		$post_id = isset( $_GET['post']) ? $_GET['post'] : '';
		$value 	 = !empty( $post_id ) ? get_post_meta( $post_id, 'tbt-shared-inventory-includes' ) : '';

		echo "<div id='tbt_shared_inventory_product_settings_wrapper' class='show_if_tbt_bundle'>" .
				"<h3 id='tbt_shared_inventory_product_settings_title'>" . __( 'Product Bundle - Product Settings', 'tbt_shared_inventory' )  . "</h3>";

					if ( isset( $value ) && !empty( $value ) ) {
						foreach ( $value[0] as $key => $item ) {
							//variables referenced in included file
							$product = wc_get_product( $item['id'] );
							$type 	 = 'product';

							include( dirname(__FILE__) . "/partials/tbt-shared-inventory-product-row.php" );

						}
					}

				echo '<button type="button" id="product-add" class="tbt-shared-inventory-add-item">Add Product to Bundle</button>' 
			. '</div>';

	}

	/**
	 * Save the set bundled items and their data from the product
	 *
	 * @param [type] $post_id
	 * @param [type] $post
	 * @return void
	 */
	function tbt_shared_inventory_save_product_related_setting ( $post_id, $post ) {
		$bundle_array = array();
		// save the bundled items to the product
		if ( isset( $_POST['tbt-shared-inventory-product-includes'] ) && ! empty( $_POST['tbt-shared-inventory-product-includes'] ) ) {
			foreach ( $_POST['tbt-shared-inventory-product-includes'] as $key => $item_id ) {
				$bundle_array[] = array(
					'id'    => $item_id[0],
					'price' => ! empty( $_POST['tbt-shared-inventory-product-includes-price'][ $key ] ) 
								? $_POST['tbt-shared-inventory-product-includes-price'][ $key ][0] 
								: ( wc_get_product( $item_id ) ? wc_get_product( $item_id )->get_price() : '' ),
					'qty'   => ! empty( $_POST['tbt-shared-inventory-product-includes-qty'][ $key ] ) 
								? $_POST['tbt-shared-inventory-product-includes-qty'][ $key ][0] 
								: 1,
				);

			}

			update_post_meta( $post_id, 'tbt-shared-inventory-includes', $bundle_array );
			
		} else {
			delete_post_meta( $post_id, 'tbt-shared-inventory-includes' );

		}

	}

	/**
	 * Add bundle option to product
	 *
	 * @param mixed $options
	 * @return void
	 */
	function tbt_shared_inventory_add_is_bundle_option ( $options ) {

    	$options['tbt_shared_inventory_product_bundle'] = array(
    	    'id'            => '_tbt_shared_inventory_product_bundle',
    	    'wrapper_class' => "tbt_shared_inventory_bundle show_if_simple show_if_variable hide_if_deposit hide_if_subscription hide_if_variable-subscription hide_if_grouped hide_if_external",
    	    'label'         => __( "Product Bundle", 'tbt_shared_inventory' ),
    	    'description'   => __( "Check if this product is a bundle. If the product is a variable product any products or variations that are set at the product level will be included in all the variations.", 'tbt_shared_inventory' ),
    	    'default'       => "no",
    	);
    	return $options;

	}

	/**
	 * Save the product setting
	 *
	 * @param integer $post_ID
	 * @param mixed   $product WC_Product
	 * @param mixed   $update
	 * @return void
	 */
	function tbt_shared_inventory_save_is_bundle ( $post_ID, $product, $update ) {

		update_post_meta( $product->ID, "_tbt_shared_inventory_product_bundle", isset($_POST["_tbt_shared_inventory_product_bundle"] ) ? "yes" : "no");
	
	}

	/**
	 * Add inventory reduction field and bundle product rows to variations
	 *
	 * @param integer $loop
	 * @param mixed   $variation_data
	 * @param mixed   $variation WC_Variation
	 * @return void
	 */
	function tbt_shared_inventory_add_variation_stock_options( $loop, $variation_data, $variation ) {

		$variation = wc_get_product( $variation );

		echo '<div class="form-row form-row-full tbt_shared_inventory_settings">';
		woocommerce_wp_text_input( array(
			'id'				=> "tbt_shared_inventory_count_{$loop}",
			'name'				=> "tbt_shared_inventory_count_[{$loop}][]",
			'wrapper_class'		=> "tbt_shared_inventory_count_input",
			'value'				=> $variation->get_meta( '_tbt_shared_inventory_count' ),
			'label'				=> __( 'Reduce product inventory count by this number for each of this variation sold', 'woocommerce' ),
			'desc_tip'			=> 'true',
			'description'		=> __( 'Enter the desired adjustment to the product inventory when this variation is purchased.', 'woocommerce' ),
			'type' 				=> 'number',
			'custom_attributes'	=> array(
				'min'	=> '1',
				'step'	=> '1',
			),
		) );
		echo '</div>';

		$values = $variation->get_meta( 'tbt-shared-inventory-includes', true );

		echo "<div id='tbt_shared_inventory_variation_settings_wrapper_$loop' class='form-row form-row-full show_if_tbt_bundle variation-bundle'>" .
				"<h3 id='tbt_shared_inventory_variation_settings_title' class='variation-items-title'>" . __( 'Variation Bundle - Product Settings', 'tbt_shared_inventory' )  . "</h3>";
				if ( isset( $values ) && !empty( $values ) ) {
					foreach ( $values as $key => $item ) {
						$product = wc_get_product( $item['id'] );
						$type 	 = 'variation';

						include( dirname(__FILE__) . "/partials/tbt-shared-inventory-product-row.php" );

					}
				}
			echo "<button type='button' id='variation-add' class='tbt-shared-inventory-add-item variation variation-add' data-loop='{$loop}'>Add Product to Bundle</button>" 
		. '</div>';

	}

	/**
	 * Save custom variable fields.
	 *
	 * @param int $variation_id
	 * @param $i
	 */
	function tbt_shared_inventory_save_variation_reduction_setting( $variation_id, $i ) {

	    $variation = wc_get_product( $variation_id );

		if ( ! empty( $_POST['tbt_shared_inventory_count_'] ) && ! empty( $_POST['tbt_shared_inventory_count_'][ $i ] ) ) {
			$variation->update_meta_data( '_tbt_shared_inventory_count', absint( $_POST['tbt_shared_inventory_count_'][ $i ] ) );

		}

		$bundle_array = array();
		
		// save the bundled items to the product
		if ( isset( $_POST['tbt-shared-inventory-variation-includes'] ) && ! empty( $_POST['tbt-shared-inventory-variation-includes'][ $i ] ) ) {
			foreach ( $_POST['tbt-shared-inventory-variation-includes'][ $i ] as $key => $item_id ) {
				$price = floatval( ! empty( $_POST['tbt-shared-inventory-variation-includes-price'][ $i ][ $key ] ) 
							? $_POST['tbt-shared-inventory-variation-includes-price'][ $i ][ $key ][0] 
							: ( wc_get_product( $item_id ) ? wc_get_product( $item_id )->get_price() : '' ) );
				$bundle_array[] = array(
					'id'    => $item_id[0],
					'price' => $price,
					'qty'   => ! empty( $_POST['tbt-shared-inventory-variation-includes-qty'][ $i ][ $key ] ) ? $_POST['tbt-shared-inventory-variation-includes-qty'][ $i ][ $key ][0] : 1,
				);

			}

			$variation->update_meta_data('tbt-shared-inventory-includes', $bundle_array );
			
		} else {
			$variation->delete_meta_data( 'tbt_shared_inventory_includes' );

		}

		if ( isset($_POST['tbt_shared_inventory_variation_bundle'] ) && !empty( $_POST['tbt_shared_inventory_variation_bundle'][ $i ] ) ) {
			$variation->update_meta_data('tbt_shared_inventory_variation_bundle', $_POST['tbt_shared_inventory_variation_bundle'][ $i ] == 'on' ? 'yes' : 'no');

		} else {
			$variation->delete_meta_data('tbt_shared_inventory_variation_bundle');
		}

		$variation->save();

	}

	/**
	 * adjust stock of item (for variation bundles ie 10 of the same product) if there is no multiplier return the original
	 *
	 * @param integer  $quantity
	 * @param mixed    $order WC_Order
	 * @param mixed    $item WC_Order_Item
	 * @return integer $quantity
	 */
	function tbt_shared_inventory_stock_adjustment( $quantity, $order, $item ) {

		/** @var WC_Order_Item_Product $product */
		$multiplier = $item->get_product()->get_meta( '_tbt_shared_inventory_count' );
	
		if ( empty( $multiplier ) && $item->get_product()->is_type( 'variation' ) ) {
			$product = wc_get_product( $item->get_product()->get_parent_id() );
			$multiplier = $product->get_meta( '_tbt_shared_inventory_count' );
		}
	
		if ( ! empty( $multiplier ) ) {
			$quantity = $multiplier * $quantity;
		}
	
		return $quantity;

	}

	/**
	 * Ajax get products from search
	 *
	 * @return mixed $resutls
	 */
	function get_wc_products() {

		$results = array();

		$search_results = new WP_Query( array( 
			's'=> $_GET['q'], // the search query
			'post_type' => ['product', 'product_variation'],
			'post_status' => 'publish', // if you don't want drafts to be returned
			'ignore_sticky_posts' => 1,
			'posts_per_page' => 50 // how much to show at once
		) );

		if( $search_results->have_posts() ) :
			while( $search_results->have_posts() ) : $search_results->the_post();
				$id = $search_results->post->ID;
				// shorten the title a little
				$title = ( mb_strlen( $search_results->post->post_title ) > 100 ) ? mb_substr( $search_results->post->post_title, 0, 99 ) . '...' : $search_results->post->post_title;
				$product = wc_get_product( $id );
				$price = $product->get_regular_price();
				$results[] = array( $id, $title, $price ); // array( Post ID, Post Title, Regular Price )
			endwhile;
		endif;
		
		echo json_encode( $results );
		wp_die();

    }

	/**
	 * Add bundle option to variations
	 *
	 * @param integer $loop
	 * @param mixed   $variation_data
	 * @param mixed   $variation WC_Product_Variation
	 * @return void
	 */
	function tbt_shared_inventory_variation_add_is_bundle_option( $loop, $variation_data, $variation ) {

		$checked = 'no';

		if ( isset( $variation_data['tbt_shared_inventory_variation_bundle'] ) && !empty( $variation_data['tbt_shared_inventory_variation_bundle'] ) ) {
			$checked = $variation_data['tbt_shared_inventory_variation_bundle'][0];

		}

		echo	"<label class='tips' data-tip='Enable this option if this variant is a bundle (containing other products/variants)'>" .
					"Variant Bundle" .
					"<input type='checkbox' class='checkbox variable_is_bundle' name='tbt_shared_inventory_variation_bundle[{$loop}]'" . checked( $checked, 'yes', false ) . "/>" .
				"</label>";

	}

	/**
	 * adjust stock of other items (for multiple product bundles ie a new product consisting of 2 or more different products)
	 *
	 * @param integer $order_id
	 * @return void
	 */
	function tbt_shared_inventory_order_reduce_stock( $order_id ) {
		
		$order_processed = get_post_meta( $order_id, '_tbt_shared_inventory_processed', true );

		if ( $order_processed ) {
			return;
		}

		$order = wc_get_order( $order_id );
		$order_items = $order->get_items();

		foreach( $order_items as $item ) {
			$product_id    = $item->get_product_id();
			$variation_id  = $item->get_variation_id();
			$item_quantity = $item->get_quantity();

			// allow other plugins to modify quantity
			$filtered_qty = apply_filters( 'woocommerce_order_item_quantity', $item->get_quantity(), $order, $item );

			//get variation count settings

			//set product stock count
		}

		update_post_meta( $order_id, '_tbt_shared_inventory_processed', true );

	}

	/**
	 * adjust stock counts back up using the multiplier value
	 * the returned stock value is already adjusted up for the amount returned so we take that into account too.
	 * 
	 * @param integer $order_id
	 * @param integer $refund_id
	 * @return void
	 */
	function tbt_shared_inventory_order_return_to_stock( int $order_id, int $refund_id ) {
		
		$restock_items = $_POST['restock_refunded_items'];

		if ( !$restock_items ) {
			return;
		}

		$order_processed = get_post_meta( $order_id, '_tbt_shared_inventory_processed', true );

		if ( $order_processed ) {
			$order 		 = wc_get_order( $refund_id );
			$order_items = $order->get_items();

			foreach( $order_items as $item ) {
				$product_id 				  = $item->get_product_id();
				$variation_id 				  = $item->get_variation_id();
				$item_quantity_returned 	  = abs( $item->get_quantity());
				$multiplier 				  = !empty( $item->get_product()->get_meta( '_tbt_shared_inventory_count' ) ) ? $item->get_product()->get_meta( '_tbt_shared_inventory_count' ) : 1;
				$item_stock_quantity_returned = $item_quantity_returned * $multiplier - $item_quantity_returned;
				
				if ( 1 == $multiplier) {
					continue;
				}

				$product = wc_get_product( $product_id );

				if ( ! $product->managing_stock() ) {
					continue;
				}
		
				// adjust the product stock for the total number of items returned
				wc_update_product_stock( $product, $item_stock_quantity_returned, 'increase' );
				
			}

		}

	}

}
