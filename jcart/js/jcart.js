
// jCart v1.3
// http://conceptlogic.com/jcart/

$(function() {

	var JCART = (function() {

		// This script sends Ajax requests to config-loader.php and relay.php using the path below
		// We assume these files are in the 'jcart' directory, one level above this script
		// Edit as needed if using a different directory structure
		var path = 'jcart',
			container = $('#jcart'),
			token = $('[name=jcartToken]').val(),
			tip = $('#jcart-tooltip');

		var config = (function() {
			var config = null;
			$.ajax({
				url: path + '/config-loader.php',
				data: {
					"json": "true"
				},
				dataType: 'json',
				async: false,
				success: function(response) {
					config = response;
				},
				error: function() {
					alert('Ajax error: Edit the path in jcart.js to fix.');
				}
			});
			return config;
		}());

		var setup = (function() {
			if(config.tooltip === true) {
				tip.text(config.text.itemAdded);
	
				// Tooltip is added to the DOM on mouseenter, but displayed only after a successful Ajax request
				$('.jcart [type=submit]').mouseenter(
					function(e) {
						var x = e.pageY + 25,
							y = e.pageX + -10;
						$('body').append(tip);
						tip.css({top: y + 'px', left: x + 'px'});
					}
				)
				.mousemove(
					function(e) {
						var y = e.pageY + 25,
						x = e.pageX + -10;
						tip.css({top: y + 'px', left: x + 'px'});
					}
				)
				.mouseleave(
					function() {
						tip.hide();
					}
				);
			}

			// Remove the update and empty buttons since they're only used when javascript is disabled
			$('#jcart-buttons').remove();

			// Default settings for Ajax requests
			$.ajaxSetup({
				type: 'POST',
				url: path + '/relay.php',
				success: function(response) {
					// Refresh the cart display after a successful Ajax request
					container.html(response);
					$('#jcart-buttons').remove();
				},
				// See: http://www.maheshchari.com/jquery-ajax-error-handling/
				error: function(x, e) {
					var s = x.status, 
						m = 'Ajax error: ' ; 
					if (s === 0) {
						m += 'Check your network connection.';
					}
					if (s === 404 || s === 500) {
						m += s;
					}
					if (e === 'parsererror' || e === 'timeout') {
						m += e;
					}
					alert(m);
				}
			});
		}());

		// Check hidden input value
		// Sent via Ajax request to jcart.php which decides whether to display the cart checkout button or the PayPal checkout button based on its value
		// We normally check against request uri but Ajax update sets value to relay.php

		// If this is not the checkout the hidden input doesn't exist and no value is set
		var isCheckout = $('#jcart-is-checkout').val();

		function add(form) {
			//alert("Step 2 : The add function is called");
			
			// Input values for use in Ajax post
			var itemQty = form.find('[name=' + config.item.qty + ']'),
				itemAdd = form.find('[name=' + config.item.add + ']');
			//alert("Step 3 : In add function, serializing the values");
			//alert("This is serialized form before refresh :"+"<br/>"+form);
			// Add the item and refresh cart display
			$.ajax({
				data: form.serialize() + '&' + config.item.add + '=' + itemAdd.val(),
				success: function(response) {
						//alert(response);
					// Momentarily display tooltip over the add-to-cart button
					if (itemQty.val() > 0 && tip.css('display') === 'none') {
						tip.fadeIn('100').delay('400').fadeOut('100');
					}
					//alert("This is serialized form after refresh :"+"<br/>"+response);
					container.html(response);
					
					
					$('#jcart-buttons').remove();
				}
			});
		}
		
		function discount(input){
			//alert(input);
			var discount_percent = input.parent().find('[name=flatdiscount]').val();
			//alert(discount_percent);
			// Update the discount and refresh cart display
					$.ajax({
						data: {
							"flatdiscount": discount_percent
						}
					});
			
		}
		
		function update(input) {
			var htmlname = input.attr('name');
			//alert(htmlname);
		if(htmlname == "jcartItemQty[]"){
			//alert("Thie item qty is updated");
			// The id of the item to update
			var updateId = input.parent().find('[name="jcartItemId[]"]').val();
			//alert(updateId);
			// The new quantity
			var newQty = input.val();
			
			// The comment value
			var commentId = '#jcartItemComment-'+updateId;
			var newComment = $(commentId).val();
			
			// The discount value
			var discountId = '#jcartItemDiscount-'+updateId;
			var newDiscount = $(discountId).val();
			
			// The taxe value
			var taxId = '#jcartItemTax-'+updateId;
			var newTax = $(taxId).val();
				
				//alert(newComment);
				}
			if(htmlname == "jcartItemComment[]"){
				//alert("Thie item comment is updated");
				// The id of the item to update
				var updateId = input.attr('id');
				updateId = updateId.split('-');
				updateId = updateId[1];
				
				//alert(updateId);

				//vinod Get new comment
				var newComment = input.val();
				
				//Get quantity to update
				var newQty = $('#jcartItemQty-'+updateId).val();
				
				// The discount value
				var discountId = '#jcartItemDiscount-'+updateId;
				var newDiscount = $(discountId).val();
				
				// The taxe value
				var taxId = '#jcartItemTax-'+updateId;
				var newTax = $(taxId).val();
				
			}
			//alert(newQty);
			//alert(updateId);
			// As long as the visitor has entered a quantity
			if (newQty) {

				// Update the cart one second after keyup
				var updateTimer = window.setTimeout(function() {

					// Update the item and refresh cart display
					$.ajax({
						data: {
							"jcartUpdate": 1, // Only the name in this pair is used in jcart.php, but IE chokes on empty values
							"itemId": updateId,
							"itemQty": newQty,
							"itemComment": newComment,
							"itemDiscount": newDiscount,
							"itemTax": newTax,
							"itemComment": newComment,
							"jcartIsCheckout": isCheckout,
							"jcartToken": token
						}
					});
				}, 1000);
			}
		}
			
		function remove(link) {
			// Get the query string of the link that was clicked
			
			var queryString = link.attr('href');
			queryString = queryString.split('=');

			// The id of the item to remove
			var removeId = queryString[1];
	
			// Remove the item and refresh cart display
			$.ajax({
				type: 'GET',
				data: {
					"jcartRemove": removeId,
					"jcartIsCheckout": isCheckout
				}
			});
		}

		// Add an item to the cart
		$('.jcart').submit(function(e) {
			//alert("Step1: The jcart submit function is called");
			add($(this));
			
			e.preventDefault();
		});
		
		// Add discount to the cart
		$('.discount').submit(function(e) {
			//alert("Step1: The jcart submit function is called");
			discount($(this));
			
			e.preventDefault();
		});
		

		// Prevent enter key from submitting the cart
		container.keydown(function(e) {
			if(e.which === 13) {
				e.preventDefault();
			}
		});

		// Update an item in the cart
		container.delegate('[name="jcartItemQty[]"]', 'keyup', function(){
			//alert(this);
			update($(this));
		});
		// Update an item in the cart
		container.delegate('[name="jcartItemComment[]"]', 'blur', function(){
			//alert("The update method is called to update item qtys");
			update($(this));
		});
		

		// Remove an item from the cart
		container.delegate('.jcart-remove', 'click', function(e){
			remove($(this));
			e.preventDefault();
		});

	}()); // End JCART namespace

}); // End the document ready function