(function( $ ) {
	'use strict';

		$( document ).ready( function() {
			load_woocommerce_products();
			$('#_tbt_shared_inventory_product_bundle').trigger('change');
		});

		$( document ).on( 'woocommerce_variations_loaded', function() {
			load_woocommerce_products();
			$('.variable_is_bundle').trigger('change');
		});
	
		function load_woocommerce_products(){

			// multiple select with AJAX search
			$('.tbt-shared-inventory-product-product, .tbt-shared-inventory-variation-product').select2({
				ajax: {
					url: ajaxurl,
					dataType: 'json',
					delay: 250, // delay in ms while typing when to perform a AJAX search
					data: function ( params ) {
						return {
							q: params.term, // search query
							action: 'get_wc_products' // AJAX action for admin-ajax.php
						};
					},
					processResults: function( data ) {

						var options = [];
						if ( data ) {

							// data is the array of arrays, and each of them contains ID and the Label of the option
							$.each( data, function( index, text ) { // do not forget that "index" is just auto incremented value
								options.push( { 'id': text[0], 'text': text[1] } );
							});

						}
						return {
							results: options
						};
					},
					cache: true
				},
				minimumInputLength: 3
			});
		}

		$(document).on('change', '.variable_manage_stock', function() {
			$(this).closest('.woocommerce_variation').find('.tbt_shared_inventory_settings').hide();

			if (! $(this).is(":checked")) {
				$(this).closest('.woocommerce_variation').find('.tbt_shared_inventory_settings').show();
			}

		});

		$(document).on('change', '.variable_is_bundle', function(){
			$(this).closest('.woocommerce_variation').find('.show_if_tbt_bundle').hide();
			
			if ($(this).is(":checked")) {
				$(this).closest('.woocommerce_variation').find('.show_if_tbt_bundle').show();
				// $(this).closest('.woocommerce_variation').find('.wc_input_price').prop("disabled", true);
			} else {
				$(this).closest('.woocommerce_variation').find('.show_if_tbt_bundle').hide();
				// $(this).closest('.woocommerce_variation').find('.wc_input_price').prop("disabled", false);
			}

		});

		$(document).on('change', '#_tbt_shared_inventory_product_bundle', function() {
			$('.show_if_tbt_bundle').hide();
			// $('.wc_input_price').removeAttr("disabled");

			if ($(this).is(":checked")) {
				$('.show_if_tbt_bundle').show();
				// $('.wc_input_price').attr("disabled", 'disabled');
			} else {

			}

		});

		$(document).on('click', '.tbt-shared-inventory-add-item', function(e) {
			e.preventDefault();
			$(this).prop("disabled",true);

			var rowId = $(this).prev('.tbt_shared_inventory_includes').attr('id'),
				rowPieces = rowId ? rowId.split('_') : [],
				key = rowPieces.length > 2 ? parseInt(rowPieces[5]) + 1 : 0,
				type = $(this).hasClass('variation') ? 'variation' : 'product',
				loop = type === 'variation' ? $(this).data()['loop'] : '',
				$this = $(this);

			$.ajax({
                url: ajaxurl,
                type: 'get',
                data: {
                    action: 'get_new_product_row',
                    key: key,
                    type: type,
					loop: loop,
					post: $('#post_ID').val(),
                },

                success: function(response, status, xhr) {

					$($this).before(response);
					load_woocommerce_products();
					$($this).prop("disabled",false);

                }

            });
		});

		$(document).on('click', '.tbt-shared-inventory-remove-item', function(e) {
			e.preventDefault();
			$(this).closest('.tbt_shared_inventory_includes').remove();
		})


		//dynamically calculate the product price as a total of the bundled items
		$(document).on('blur', '.pricing .wc_input_price', function(e) {
			var total = 0;

			$('#tbt_shared_inventory_product_settings_wrapper .tbt_shared_inventory_includes').each(function() {
				var price = $(this).children('.tbt-shared-inventory-product-includes-price-input').find('.tbt-shared-inventory-product-product-price').val();
				var qty = $(this).children('.tbt-shared-inventory-product-includes-qty-input').find('.tbt-shared-inventory-product-product-qty').val();
				total += parseFloat(price * qty);
			})

			if ($(this).val() < total) {
				alert(`You have entered a price that is less than the items in the bundle. If there is not a sale price set the regular price should be $${total} or more. If there is a sale price it should be $${total} or more. You will be able to save the product but THE RESULTING ORDERS MAY HAVE PROBLEMS.`);
			}

		})

		//dynamically calculate the variant price as a total of the bundled items
		$(document).on('blur', '.variable_pricing .wc_input_price', function(e) {
			var total = 0;

			$(this).closest('.variable_pricing').children('.variation-bundle').find('.tbt_shared_inventory_includes').each(function() {
				var price = $(this).children('.tbt-shared-inventory-variation-includes-price-input').find('.tbt-shared-inventory-variation-product-price').val();
				var qty = $(this).children('.tbt-shared-inventory-variation-includes-qty-input').find('.tbt-shared-inventory-variation-product-qty').val();
				total += parseFloat(price * qty);
			})

			if ($(this).val() < total) {
				alert(`You have entered a price that is less than the items in the bundle. If there is not a sale price set the regular price should be $${total} or more. If there is a sale price it should be $${total} or more. You will be able to save the product but THE RESULTING ORDERS MAY HAVE PROBLEMS.`);
			}

		});

})( jQuery );