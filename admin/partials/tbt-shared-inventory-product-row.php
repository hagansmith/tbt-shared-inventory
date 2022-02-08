<?php 

// for displaying variations
$loop_val = '';
if ('variation' === $type) {
    $loop_val = '[' . $loop . ']';
}


echo "<div id='tbt_shared_inventory_product_row_{$key}' class='form-row form-row-full tbt_shared_inventory_includes'>";
// product selection
woocommerce_wp_select( array(
    'id'				=> "tbt-shared-inventory-{$type}-includes" . $loop_val . "[{$key}][]",
    'label' 			=> __( 'Product/Variant', 'tbt_shared_inventory' ),
    'description' 		=> __( "Select products/variants to bundle as a part of this {$type}" ),
    'desc_tip' 			=> 'true',
    'options' 			=> ! empty($item['id']) ? array($item['id'] => $product->get_title()) : array('', 'Select a product'),
    'value'				=> ! empty($item['id']) ? $item['id'] : '',
    'class' 			=> "tbt-shared-inventory-{$type}-product",
    'wrapper_class' 	=> "tbt-shared-inventory-{$type}-includes-input",
) );

// price setting
$price = isset($item) && !empty($item['price']) ? $item['price'] : ( !empty($product) ? $product->get_price() : '');

woocommerce_wp_text_input( array(
    'id'				=> "tbt-shared-inventory-{$type}-includes-price" . $loop_val . "[{$key}][]",
    'label' 			=> __( "Price ", 'woocommerce' ) . get_woocommerce_currency_symbol(),
    'description' 		=> __( 'Set the price of this item when it is in this bundle' ),
    'desc_tip' 			=> 'true',
    'value'				=> $price,
    'class' 			=> "tbt-shared-inventory-{$type}-product-price",
    'wrapper_class' 	=> "tbt-shared-inventory-{$type}-includes-price-input",
    'type'				=> 'float',
) );

// quantity setting
woocommerce_wp_text_input( array(
    'id'				=> "tbt-shared-inventory-{$type}-includes-qty" . $loop_val . "[{$key}][]",
    'label' 			=> __( "Quantity ", 'woocommerce' ),
    'description' 		=> __( 'Set the quantity of this item included in the bundle' ),
    'desc_tip' 			=> 'true',
    'value'				=> !empty ($item['qty']) ? $item['qty'] : '1',
    'class' 			=> "tbt-shared-inventory-{$type}-product-qty",
    'wrapper_class' 	=> "tbt-shared-inventory-{$type}-includes-qty-input",
    'type' 				=> 'number',
    'custom_attributes'	=> array(
        'min'	=> '1',
        'step'	=> '1',
    ),
) );

echo '<button type="button" class="tbt-shared-inventory-remove-item">Remove</button></div>';