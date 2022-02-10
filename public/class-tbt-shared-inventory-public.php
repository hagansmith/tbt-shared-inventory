<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://theboldtype.com
 * @since      1.0.0
 *
 * @package    Tbt_Shared_Inventory
 * @subpackage Tbt_Shared_Inventory/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Tbt_Shared_Inventory
 * @subpackage Tbt_Shared_Inventory/public
 * @author     Adam Smith <adam@theboldtype.com>
 */
class Tbt_Shared_Inventory_Public {

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
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Tbt_Shared_Inventory_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Tbt_Shared_Inventory_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/tbt-shared-inventory-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Tbt_Shared_Inventory_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Tbt_Shared_Inventory_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/tbt-shared-inventory-public.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Check our stock level before adding a product to the cart
	 *
	 * @param [type] $passed
	 * @param integer $product_id
	 * @param integer $quantity
	 * @param integer $variation_id
	 * @return bool
	 */
	function tbt_shared_inventory_check_stock_cart( $passed, $product_id, $quantity, $variation_id = 0 ) {

		$current_id 	 	= isset( $variation_id ) && !empty( $variation_id ) ? $variation_id : $product_id;
		$product		 	= wc_get_product( $current_id );
		$manage_stock	  	= $product->get_manage_stock(); 
		$product_count	 	= intval( $product->get_meta( '_tbt_shared_inventory_count', true ) 
								? $product->get_meta( '_tbt_shared_inventory_count', true ) : 1 );
		$requested_qty 	 	= $product_count * $quantity;
		$is_var_bundle		= !empty( $product->get_meta( 'tbt_shared_inventory_variation_bundle', true ) ) 
								&& $product->get_meta( 'tbt_shared_inventory_variation_bundle', true ) === 'yes' ? true : false;
		$is_prod_bundle		= !empty( $product->get_meta( '_tbt_shared_inventory_product_bundle', true ) ) 
								&&$product->get_meta( '_tbt_shared_inventory_product_bundle', true ) === 'yes' ? true : false;

		// exempt backorder products - bail early bail often
		if( $product->backorders_allowed() ) {

			return true;

		}

		//if the requested quantity is greater than what we have bail - the logic only gets more intense from here
		if ( !$product->has_enough_stock( $requested_qty ) ) {

			wc_add_notice("Sorry, we don't have enough stock to fulfill your request. Please contact us about placing an order for this item.", 'error');
			return false;

		}

		if ( ( $is_var_bundle || $is_prod_bundle ) && !empty( $product->get_meta( 'tbt-shared-inventory-includes', true ) ) ) {
			foreach( $product->get_meta( 'tbt-shared-inventory-includes', true ) as $bundle_item ) {
				$bundle_product = wc_get_product( $bundle_item['id'] );
				if ( !$bundle_product->has_enough_stock( $bundle_item['qty'] * $requested_qty ) ) {
					wc_add_notice( "Sorry, we don't have enough stock to fulfill your request. Please contact us about placing an order for this item.", 'error' );
					return false;
				}
			}
		}

		// check if the item we are adding to the cart is another variation of an item in the cart 
		// make sure we have enough to fulfill this added quantity
		$cart_contents = $this->check_cart_contents( WC()->cart, true );
		$id_managing_stock = $manage_stock === 'parent' ? $product_id : $variation_id;

		if ( array_key_exists( $id_managing_stock, $cart_contents ) ) {

			$cart_contents[ $id_managing_stock ] += $requested_qty;

		} else {

			$cart_contents[ $id_managing_stock ] = $requested_qty;

		}

		if( !$product->has_enough_stock( $cart_contents[ $id_managing_stock ] ) ) {

			wc_add_notice("Sorry, we don't have enough stock to fulfill your request for this item. Please contact us about placing an order for this item.", 'error');
			return false;

		}
	
		return true;

	}

	/**
	 * Check if the contents of the cart can be added to the cart
	 * 
	 * @param mixed $cart 
	 * @param bool $return_cart_totals 
	 * @return array 
	 */
	private function check_cart_contents ( $cart, $return_cart_totals = false ) {

		//will need to check if the item is a bundled item
		$out_of_stock_cart_items = array();
		$products_in_cart = array();

		// Loop for all products in cart
		foreach ( $cart->get_cart() as $key => $value ) { 
			$product_id			= $value['product_id'];
			$variation_id		= $value['variation_id'];
			$current_id 	  	= $variation_id === 0 ? $product_id : $variation_id;
			$product		  	= $value['data'];
			$quantity_in_cart	= $value['quantity'];
			$manage_stock	  	= $product->get_manage_stock();

			//shared inventory settings
			$product_count	  	= intval( $product->get_meta('_tbt_shared_inventory_count', true ) 
									? $product->get_meta( '_tbt_shared_inventory_count', true ) : 1 );						
			$is_bundle		  	= ( !empty( $product->get_meta( 'tbt_shared_inventory_variation_bundle', true ) ) || !empty( $product->get_meta( '_tbt_shared_inventory_product_bundle', true ) ) )
									&& ( $product->get_meta( 'tbt_shared_inventory_variation_bundle', true ) === 'yes' || $product->get_meta( '_tbt_shared_inventory_product_bundle', true ) === 'yes' ) ? true : false;
			$bundled_items 	  	= $is_bundle ? $product->get_meta( 'tbt-shared-inventory-includes' ) : [];

			// exempt backorder products
			// if the product is marked to allow backorders then 
			// assume all of the products in the bundle are ok to be backordered
			if( $product->backorders_allowed() ) {

				continue;

			}

			if ( $is_bundle && !empty( $bundled_items ) ) {
				foreach( $bundled_items as $item ) {
					$bundled_product 					= wc_get_product( $item['id'] );
					$bundled_product_count 				= $item['qty'];
					$bundled_requested_qty 	  			= $product_count * $bundled_product_count * $quantity_in_cart;

					// exempt individual backorder products in a bundle
					if( $bundled_product->backorders_allowed() ) {
					
						continue;
					
					}

					if ( in_array($item['id'], $products_in_cart) ) {

						$products_in_cart[ $item['id'] ] += $bundled_requested_qty;
		
					} else {
		
						$products_in_cart[ $item['id'] ] = $bundled_requested_qty;
		
					}
					
					//set the whole bundle out of stock if 1 of the items in the bundle is not allowed to be backordered 
					// and there isn't enough qty in stock
					if ( !$bundled_product->has_enough_stock( $products_in_cart[ $item['id'] ] ) ) {
						$out_of_stock_cart_items[ $key ] = $current_id;
					}

				}
			} else {

				//bail out if the count isn't set or is set to 1
				if ( empty($product_count) ) {

					continue;

				}

				$requested_qty = $product_count * $quantity_in_cart;
				$id_managing_stock = $manage_stock === 'parent' ? $product_id : $variation_id;

				if ( array_key_exists( $id_managing_stock, $products_in_cart ) ) {

					$products_in_cart[ $id_managing_stock ] += $requested_qty;

				} else {

					$products_in_cart[ $id_managing_stock ] = $requested_qty;

				}

				if ( !$product->has_enough_stock( $products_in_cart[ $id_managing_stock ] ) ) {
					$out_of_stock_cart_items[$key] = $current_id;
				}
			}
		}

		if ( $return_cart_totals ) {

			return $products_in_cart;

		}

		return $out_of_stock_cart_items;

	}

	/**
	 * Verifiy there is still enough stock when the quantity in the cart is updated
	 *
	 * @param integer $cart_item_key
	 * @param integer $quantity
	 * @param integer $old_quantity
	 * @param mixed   $cart
	 * @return void
	 */
	function tbt_shared_inventory_check_stock_cart_change ( $cart_item_key, $quantity, $old_quantity, $cart ) {
		
		$bundled_items 			= !empty($cart->cart_contents[ $cart_item_key ]['tbt_shared_child_keys']) ? $cart->cart_contents[ $cart_item_key ]['tbt_shared_child_keys'] : [];
		$bundle_out_of_stock 	= false;
		if ( empty( $bundled_items ) ) {
			return;
		}

		$out_of_stock_cart_items = $this->check_cart_contents( $cart );		

		if ( ! empty( $out_of_stock_cart_items ) ) {
			foreach( $bundled_items as $cart_key => $child_item ) {
				$bundle_out_of_stock = array_key_exists( $child_item, $out_of_stock_cart_items );
				if ( $bundle_out_of_stock === true ) {
					break;
				}
			}
		}

		if ( $bundle_out_of_stock ) {
			$product_name = $cart->cart_contents[ $cart_item_key ]['data']->get_name();
			$cart->set_quantity( $cart_item_key, $old_quantity, false );
			wc_add_notice( "Sorry, we don't have enough stock to fulfill your request for " . $quantity .  " - " . $product_name . ". We have set the quantity back in your cart and you can continue checking out or remove the item.", 'error' );
		} else {
			foreach( $bundled_items as $item_key ) {
				$qty = $cart->cart_contents[ $item_key ]['tbt_shared_qty'];
				$cart->set_quantity( $item_key, $quantity * $qty, false );
			}

		}

	}

	/**
	 * Verifiy there is still enough stock at checkout
	 *
	 * @param mixed $data
	 * @param mixed $errors
	 * @return void
	 */
	function tbt_shared_inventory_check_stock_checkout( $data, $errors ) {
		
		$cart 	  = WC()->cart;
		$products = '';
		$out_of_stock_cart_items = $this->check_cart_contents( $cart );
		
		if ( !empty( $out_of_stock_cart_items ) ) {
			foreach( $out_of_stock_cart_items as $key => $value ) {
				$cart->remove_cart_item( $key );
				$products .= '<li>' . get_the_title( $value ) . '</li>';
			}
		}

		if ( $products !== '' ) {
			$cart->calculate_totals();
			$errors->add( 'items', "Sorry, some of the items in your cart have become unavalible since being added. We have removed these items from your cart and you can continue checking out. Please contact us about placing an order for these items.<ul>" . $products . "</ul>", 'error', 'tbt_shared_inventory' );
		}
	
		return $errors;

	}

	/**
	 * Do the work of splitting bundled items into separate items in the cart
	 * 
	 * @param integer $cart_item_key 
	 * @param integer $product_id 
	 * @param integer $quantity 
	 * @param integer $variation_id 
	 * @param mixed $variation 
	 * @param mixed $cart_item_data 
	 * @return void 
	 *
	 */
	public function tbt_shared_inventory_split_order_bundle( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
		// loop through the cart items and check if the item is a bundle
		// if it is a bundle break the bundle apart and calcualte taxes on each item in the bundle
		$cart 			= WC()->cart;
		$product 		= wc_get_product($variation_id === 0 ? $product_id : $variation_id);

		$is_bundle 		= !empty( $product->get_meta( 'tbt_shared_inventory_variation_bundle', true ) ) || !empty( $product->get_meta( '_tbt_shared_inventory_product_bundle', true ) )
							&& $product->get_meta( 'tbt_shared_inventory_variation_bundle', true ) || $product->get_meta( '_tbt_shared_inventory_product_bundle', true ) === 'yes' ? true : false;

		if ( $is_bundle ) {
			$parent_id   = $product->get_id();
			$ordered_qty = $quantity;
			$bundled_items = $product->get_meta( 'tbt-shared-inventory-includes' );
			$bundled_items_total = 0;

			foreach( $bundled_items as $bundled_item ) {
				$bundled_product 		= wc_get_product( $bundled_item['id'] );
				$bundled_product_id 	= $bundled_product->get_parent_id() === 0 ? $bundled_product->get_id() : $bundled_product->get_parent_id();
				$bundled_variation_id	= $bundled_product->get_parent_id() === 0 ? 0 : $bundled_product->get_id();
				$bundled_data = array(
					'tbt_shared_parent_id'  => $parent_id,
					'tbt_shared_parent_key' => $cart_item_key,
					'tbt_shared_price'		=> $bundled_item['price'],
					'tbt_shared_qty'		=> $bundled_item['qty'],
				);
				$bundled_cart_item_key 	= $cart->add_to_cart( $bundled_product_id, $bundled_item['qty'] * $ordered_qty, $bundled_variation_id, array(), $bundled_data );
				$bundled_cart_item 	 	= $cart->get_cart_item( $bundled_cart_item_key );

				// update the new unrolled item
				if ($bundled_cart_item) {
					$bundled_items_total += $bundled_item['price'] * $bundled_item['qty'] * $ordered_qty;
					$cart->cart_contents[ $cart_item_key ]['tbt_shared_child_ids'][] = $bundled_item['id'];
					$cart->cart_contents[ $cart_item_key ]['tbt_shared_child_keys'][] = $bundled_cart_item_key; 
				}
			}

			$parent_price = ( $product->get_price() - $bundled_items_total ) / $ordered_qty;
			$cart->cart_contents[ $cart_item_key ]['tbt_shared_price'] = $parent_price < 0 ? 0 : $parent_price;

		}

	}

	/**
	 * Set the quantity of bundled child items in the cart as uneditable
	 *
	 * @param integer $quantity
	 * @param integer $cart_item_key
	 * @param mixed   $cart_item
	 * @return void
	 */
	function tbt_shared_inventory_cart_item_quantity( $quantity, $cart_item_key, $cart_item ) {
		// add qty as text - not input
		if ( isset( $cart_item['tbt_shared_parent_id'] ) ) {
			return $cart_item['quantity'];
		}

		return $quantity;
	}

	/**
	 * Display the contents of a bundle on the product page
	 *
	 * @param mixed $product WC_Product
	 * @return void
	 */
	function tbt_shared_inventory_product_bundle_display( $product = null ) {
		
		if ( ! $product ) {
			global $product;
		}
		
		$products[] = $product; 

		if ( $product->is_type( 'variable' ) ) {
			$products = array();
			foreach( $product->get_available_variations() as $key => $variation ) {

				$products[] = wc_get_product( $variation['variation_id'] );
			}
		}

		foreach( $products as $key => $prod ) {

			$is_bundle 	= ( !empty( $prod->get_meta( 'tbt_shared_inventory_variation_bundle', true ) ) || !empty( $prod->get_meta( '_tbt_shared_inventory_product_bundle', true ) ) )
							&& ( $prod->get_meta( 'tbt_shared_inventory_variation_bundle', true ) === 'yes' || $prod->get_meta( '_tbt_shared_inventory_product_bundle', true ) === 'yes' ) ? true : false;
			
			if ( ! $prod || ! $is_bundle ) {
				continue;
			}

			$bundled_items = $prod->get_meta( 'tbt-shared-inventory-includes' );

			if ( ! empty( $bundled_items ) ) {
				$style = $prod->get_meta( 'tbt_shared_inventory_variation_bundle', true ) === 'yes' ? 'display:none' : '';
				echo '<div id="tbt-shared-' . $key . '" class="tbt-shared-inventory-bundles" style="' . $style . '">';
				echo '<div class="tbt-shared_before_text tbt-shared-before-text tbt-shared-text">' . do_shortcode( stripslashes( "Products in bundle:" ) ) . '</div>';
				echo '<div class="tbt-shared-products">';

				foreach ( $bundled_items as $bundle_item ) {
					$item = wc_get_product( $bundle_item['id'] );
					echo '<div class="tbt-shared-product">';
					echo '<div class="tbt-shared-thumb">' . $item->get_image() . '</div>';
					echo '<div class="tbt-shared-title">'. $bundle_item['qty'] . ' &times; <a href="' . $item->get_permalink() . '" ' . 'target="_blank"' . '>' . $item->get_name() . '</a></div>';
					echo '</div><!-- /tbt-shared-product -->';
				}

				echo '</div><!-- /tbt-shared-products -->';
				wp_reset_postdata();
				echo '</div><!-- /tbt-shared-bundles -->';
			}

		}

	}

	/**
	 * Set the name of cart item for bundled items this will be the product name
	 * with the name and quantities of the child products appended
	 *
	 * @param string $name
	 * @param mixed $cart_item
	 * @return void
	 */
	function tbt_shared_inventory_cart_item_name( $name, $cart_item ) {
		$product = isset($cart_item['data']) ? $cart_item['data'] : wc_get_product($cart_item['product_id']);
		if ( !$product ) {
			return $name;
		}
		$is_bundle 	= !empty( $product->get_meta( 'tbt_shared_inventory_variation_bundle', true ) ) || !empty( $product->get_meta( '_tbt_shared_inventory_product_bundle', true ) )
						&& $product->get_meta( 'tbt_shared_inventory_variation_bundle', true ) || $product->get_meta( '_tbt_shared_inventory_product_bundle', true ) === 'yes' ? true : false;

		if ( $is_bundle ) {

			$name = array();
			$bundled_items = $product->get_meta( 'tbt-shared-inventory-includes' );
			foreach( $bundled_items as $item ) {
				$name[] = $item['qty'] . ' &times; ' . get_the_title($item['id']);
			}

			$combined_name = '<a href="' . get_permalink( $product->get_id() ) . '">' . $product->get_name() . '</a> <ul class="tbt-shared-bundled-list">'; 
			
			foreach( $bundled_items as $item ) {
			
				$combined_name .= "<li>" . $item['qty'] . ' &times; ' . get_the_title($item['id']) . "</li>";
			
			}
			
			$combined_name .= '</ul>';

			return $combined_name;

		}

		return $name;
	}

	/**
	 * Set the display price of the item in the cart
	 *
	 * @param float $price
	 * @param mixed $cart_item
	 * @return string $price
	 */
	function tbt_shared_inventory_cart_item_price( $price, $cart_item ) {
		if ( isset( $cart_item['tbt_shared_child_ids'], $cart_item['tbt_shared_price']) ) {
			$product = wc_get_product($cart_item['variation_id'] === 0 ? $cart_item['product_id'] : $cart_item['variation_id']);
			return wc_price( $product->get_price() );
		}

		if ( isset( $cart_item['tbt_shared_price'] ) ) {
			return wc_price( $cart_item['tbt_shared_price'] );
		}

		return $price;
	}

	/**
	 * Set the display subtotal of the item in the cart
	 *
	 * @param float $subtotal
	 * @param mixed $cart_item
	 * @return string $subtotal
	 */
	function tbt_shared_inventory_cart_item_subtotal( $subtotal, $cart_item = null ) {
		$new_subtotal = false;
		
		if ( isset( $cart_item['tbt_shared_child_ids'], $cart_item['tbt_shared_price']) ) {
			$new_subtotal = true;
			$product = wc_get_product($cart_item['variation_id'] === 0 ? $cart_item['product_id'] : $cart_item['variation_id']);
			return wc_price( $product->get_price() * $cart_item['quantity']);
		}

		if ( isset($cart_item['tbt_shared_parent_id'], $cart_item['tbt_shared_price'] ) ) {
			$new_subtotal = true;
			$subtotal     = wc_price( $cart_item['tbt_shared_price'] * $cart_item['quantity'] );
		}

		if ( $new_subtotal && ( $cart_product = $cart_item['data'] ) ) {
			if ( $cart_product->is_taxable() ) {
				if ( WC()->cart->display_prices_including_tax() ) {
					if ( ! wc_prices_include_tax() && WC()->cart->get_subtotal_tax() > 0 ) {
						$subtotal .= ' <small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>';
					}
				} else {
					if ( wc_prices_include_tax() && WC()->cart->get_subtotal_tax() > 0 ) {
						$subtotal .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
					}
				}
			}
		}

		return $subtotal;
	}

	/**
	 * Set the price of the cart item to the set price in the bundle
	 *
	 * @param mixed $cart_object
	 * @return void
	 */
	function tbt_shared_inventory_before_calculate_totals( $cart_object ) {
		if ( ! defined( 'DOING_AJAX' ) && is_admin() ) {
			// This is necessary for WC 3.0+
			return;
		}

		foreach ( $cart_object->cart_contents as $cart_item_key => $cart_item ) {
			// bundled products
			if ( isset( $cart_item['tbt_shared_price'] ) ) {
				// set price
				$cart_item['data']->set_price( $cart_item['tbt_shared_price'] );
			}
		}
	}

	/**
	 * Set the visibility of the child products to the user
	 *
	 * @param boolean $visible
	 * @param mixed   $cart_item
	 * @return void
	 */
	function tbt_shared_inventory_item_visible( $visible, $cart_item ) {
		return $visible;
		if ( isset( $cart_item['tbt_shared_parent_id'] ) ) {
			return false;
		}

		return $visible;
	}

	/**
	 * Set the count of items in the cart to only include the quantity of the parent bundle
	 * and any other products in the cart (exclude bundle children from the count)
	 *
	 * @param integer $count
	 * @return integer $count
	 */
	function tbt_shared_inventory_cart_contents_count( $count ) {
		// count for cart contents
		foreach ( WC()->cart->get_cart() as $cart_item ) {

			if ( ! empty( $cart_item['tbt_shared_parent_id'] ) ) {
				$count -= $cart_item['quantity'];
			}

		}
		return $count;
	}

	/**
	 * Remove all bundle contents from the cart
	 *
	 * @param integer $cart_item_key
	 * @param mixed   $cart
	 * @return void
	 */
	function tbt_shared_inventory_cart_item_removed( $cart_item_key, $cart ) {

		foreach( $cart->cart_contents as $cart_key => $item ) {
			if ( !empty( $item['tbt_shared_parent_key'] ) && $item['tbt_shared_parent_key'] === $cart_item_key ) {
				$cart->remove_cart_item( $cart_key );
			}
		}

	}

	/**
	 * Undo remove from cart
	 *
	 * @param integer $cart_item_key
	 * @return void
	 */
	function tbt_shared_inventory_restore_cart_item( $cart_item_key ) {
		if ( isset( WC()->cart->cart_contents[ $cart_item_key ]['tbt_shared_child_ids'] ) ) {
			$this->tbt_shared_inventory_split_order_bundle( 
				$cart_item_key, 
				WC()->cart->cart_contents[ $cart_item_key ]['product_id'], 
				WC()->cart->cart_contents[ $cart_item_key ]['quantity'], 
				WC()->cart->cart_contents[ $cart_item_key ]['variation_id'], 
				null, 
				null 
			);
		}

	}

	/**
	 * Set the order item meta
	 *
	 * @param mixed   $order_item
	 * @param integer $cart_item_key
	 * @param mixed   $values
	 * @return void
	 */
	function tbt_shared_inventory_add_order_item_meta( $order_item, $cart_item_key, $values ) {
		if ( isset( $values['tbt_shared_parent_id'] ) ) {
			// use _ to hide the data
			$order_item->update_meta_data( '_tbt_shared_parent_id', $values['tbt_shared_parent_id'] );
		}

		if ( isset( $values['tbt_shared_child_ids'] ) ) {
			// use _ to hide the data
			$order_item->update_meta_data( '_tbt_shared_child_ids', $values['tbt_shared_child_ids'] );
		}

		if ( isset( $values['tbt_shared_price'] ) ) {
			// use _ to hide the data
			$order_item->update_meta_data( '_tbt_shared_price', $values['tbt_shared_price'] );
		}
	}

	/**
	 * Get the count of items for the order to only include the quantity of the parent bundle
	 * and any other products in the cart (exclude bundle children from the count)
	 *
	 * @param intger $count
	 * @param string $type
	 * @param mixed $order WC_Order
	 * @return void
	 */
	function tbt_shared_order_get_item_count( $count, $type, $order ) {

		$order_items = $order->get_items( 'line_item' );

		foreach ( $order_items as $order_item ) {

			if ( $order_item->get_meta( '_tbt_shared_parent_id' ) ) {
				$count -= $order_item->get_quantity();
			}

		}

		return $count;

	}

	/**
	 * Set the subtotal of the item on the order
	 *
	 * @param float $subtotal
	 * @param mixed $order_item
	 * @return void
	 */
	function tbt_shared_order_formatted_line_subtotal( $subtotal, $order_item ) {

		if ( isset( $order_item['_tbt_shared_child_ids'], $order_item['_tbt_shared_price'] ) || !empty( $order_item->get_meta( '_tbt_shared_child_ids', TRUE ) ) ) {
			$product = wc_get_product( $order_item['variation_id'] === 0 ? $order_item['product_id'] : $order_item['variation_id'] );
			return wc_price( $product->get_price() * $order_item['quantity'] );
		}

		return $subtotal;
	}

	/**
	 * Hide order item meta
	 *
	 * @param mixed $hidden
	 * @return void
	 */
	function tbt_shared_hidden_order_item_meta( $hidden ) {
		return array_merge( $hidden, array(
			'_tbt_shared_parent_id',
			'_tbt_shared_child_ids',
			'_tbt_shared_price',
			'tbt_shared_parent_id',
			'tbt_shared_child_ids',
			'tbt_shared_price'
		) );
	}

}
