<?php

// jCart v1.3
// http://conceptlogic.com/jcart/

//error_reporting(E_ALL);

// Cart logic based on Webforce Cart: http://www.webforcecart.com/
error_reporting(E_ALL ^ E_NOTICE);
date_default_timezone_set('Asia/Calcutta');
class Jcart {

	public  $config = array();
	private $items = array();
	private $names = array();
	private $prices = array();
	private $qtys = array();
	private $urls = array();
	private $discounts = array();//Item discount -- added by vinod
	private $taxes = array();//Item taxes -- added by vinod
	private $comments = array();//Item Commetns --  added by vinod
	private $flat_discount = 0;//Flat discount on the total amount -- added by vinod
	private $total = 0;//Total is obtained from (subtotal - all discounts + all taxes)
	//added by vinod
	private $subtotal = 0;
	private $discounttotal = 0;//discounttotal is sum of all the item level discounts -- added by vinod
	private $taxtotal = 0;//taxtotal is sum of all the item level taxes -- added by vinod
	private $itemCount = 0;
	private $flat_discount_percent = 0;
	function __construct() {

		// Get $config array
		include_once ('config-loader.php');
		$this -> config = $config;
	}

	/**
	 * Get cart contents
	 *
	 * @return array
	 */
	public function get_contents() {
		$items = array();
		foreach ($this->items as $tmpItem) {
			$item = null;
			$item['id'] = $tmpItem;
			$item['name'] = $this -> names[$tmpItem];
			$item['price'] = $this -> prices[$tmpItem];
			$item['qty'] = $this -> qtys[$tmpItem];
			$item['url'] = $this -> urls[$tmpItem];
			$item['discount'] = $this -> discounts[$tmpItem];//Added by vinod
			$item['tax'] = $this -> taxes[$tmpItem];//Added by vinod
			$item['comment'] = $this -> comments[$tmpItem];	//added by vinod
			$item['subtotal'] = $item['price'] * $item['qty'];
			$item['discounttotal'] = (($item['price'] * $item['discount'])/100) * $item['qty'];//added by vinod
			$item['taxtotal'] = (($item['price'] * $item['tax'])/100) * $item['qty'];//Added by vinod
			$items[] = $item;
		}
		//echo "this is the tempitem array";
		//echo "<pre>";
		//print_r($tmpItem);
		//echo "</pre>";
		return $items;
	}

	/**
	 * Add an item to the cart
	 *
	 * @param string $id
	 * @param string $name
	 * @param float $price
	 * @param mixed $qty
	 * @param string $url
	 *
	 * @return mixed
	 */
	private function add_item($id, $name, $price, $qty = 1, $url, $comment = NULL,$discount,$tax) {

		$validPrice = false;
		$validQty = false;

		// Verify the price is numeric
		if (is_numeric($price)) {
			$validPrice = true;
		}

		// If decimal quantities are enabled, verify the quantity is a positive float
		if ($this -> config['decimalQtys'] === true && filter_var($qty, FILTER_VALIDATE_FLOAT) && $qty > 0) {
			$validQty = true;
		}
		// By default, verify the quantity is a positive integer
		elseif (filter_var($qty, FILTER_VALIDATE_INT) && $qty > 0) {
			$validQty = true;
		}

		// Add the item
		if ($validPrice !== false && $validQty !== false) {

			// If the item is already in the cart, increase its quantity
			if ($this -> qtys[$id] > 0) {

				$this -> qtys[$id] += $qty;
				//echo "If the qty of this id is > 0 then it is the existed item. So add the pervious qty and the new qty and store as qtys[id] => qtys[id] + qtys[id] <br/>";
				//echo "<pre>";
				//print_r($this -> qtys);
				//echo "</pre>";
				$this -> update_subtotal();
				
			}
			// This is a new item
			else {
				$this -> items[] = $id;
				$this -> names[$id] = $name;
				$this -> prices[$id] = $price;
				$this -> qtys[$id] = $qty;
				$this -> urls[$id] = $url;
				$this -> discounts[$id] = $discount;
				$this -> taxes[$id] = $tax;
				$this -> comments[$id] = $comment;
				//added by vinod
				/**echo "If the qty of this id is = 0 then it is the new item that has to be added to the cart. So initialize all arrays <br/>";
				echo "Items array <br/>";
				echo "<pre>";
				print_r($this -> items);
				echo "</pre>";
				echo "Names array <br/>";
				echo "<pre>";
				print_r($this -> names);
				echo "</pre>";
				echo "Prices array <br/>";
				echo "<pre>";
				print_r($this -> prices);
				echo "</pre>";
				echo "Qtys array <br/>";
				echo "<pre>";
				print_r($this -> qtys);
				echo "</pre>";
				echo "Discounts array <br/>";
				echo "<pre>";
				print_r($this -> discounts);
				echo "</pre>";
				echo "taxes array <br/>";
				echo "<pre>";
				print_r($this -> taxes);
				echo "</pre>";
				echo "Comments array <br/>";
				echo "<pre>";
				print_r($this -> comments);
				echo "</pre>";**/
			}
			$this -> update_subtotal();
			
			return true;
		} elseif ($validPrice !== true) {
			$errorType = 'price';
			return $errorType;
		} elseif ($validQty !== true) {
			$errorType = 'qty';
			return $errorType;
		}
	}

	/**
	 * Update an item in the cart
	 *
	 * @param string $id
	 * @param mixed $qty
	 *
	 * @return boolean
	 */
	private function update_item($id, $qty, $comment = NULL,$discount,$tax) {
		
		// If the quantity is zero, no futher validation is required
		if ((int)$qty === 0) {
			$validQty = true;
		}
		// If decimal quantities are enabled, verify it's a float
		elseif ($this -> config['decimalQtys'] === true && filter_var($qty, FILTER_VALIDATE_FLOAT)) {
			$validQty = true;
		}
		// By default, verify the quantity is an integer
		elseif (filter_var($qty, FILTER_VALIDATE_INT)) {
			$validQty = true;
		}

		// If it's a valid quantity, remove or update as necessary
		if ($validQty === true) {
			if ($qty < 1) {
				$this -> remove_item($id);
			} else {
				$this -> qtys[$id] = $qty;
				$this -> discounts[$id] = $discount;//added by vinod
				$this -> taxes[$id] = $tax;//added by vinod
				$this -> comments[$id] = $comment;//added by vinod
				/**echo "Items array <br/>";
				echo "<pre>";
				print_r($this -> items);
				echo "</pre>";
				echo "Names array <br/>";
				echo "<pre>";
				print_r($this -> names);
				echo "</pre>";
				echo "Prices array <br/>";
				echo "<pre>";
				print_r($this -> prices);
				echo "</pre>";
				echo "Qtys array <br/>";
				echo "<pre>";
				print_r($this -> qtys);
				echo "</pre>";
				echo "Discounts array <br/>";
				echo "<pre>";
				print_r($this -> discounts);
				echo "</pre>";
				echo "taxes array <br/>";
				echo "<pre>";
				print_r($this -> taxes);
				echo "</pre>";
				echo "Comments array <br/>";
				echo "<pre>";
				print_r($this -> comments);
				echo "</pre>";**/
			}
			
			$this -> update_subtotal();
		
			return true;
		}
	}

	/* Using post vars to remove items doesn't work because we have to pass the
	 id of the item to be removed as the value of the button. If using an input
	 with type submit, all browsers display the item id, instead of allowing for
	 user-friendly text. If using an input with type image, IE does not submit
	 the	value, only x and y coordinates where button was clicked. Can't use a
	 hidden input either since the cart form has to encompass all items to
	 recalculate	subtotal when a quantity is changed, which means there are
	 multiple remove	buttons and no way to associate them with the correct
	 hidden input. */

	/**
	 * Reamove an item from the cart
	 *
	 * @param string $id	*
	 */
	private function remove_item($id) {
		//echo "This is the id which we have to remove " . $id;
		$tmpItems = array();
		//echo "Unset names,prices,qtys values for this array";
		unset($this -> names[$id]);
		unset($this -> prices[$id]);
		unset($this -> qtys[$id]);
		unset($this -> urls[$id]);
		unset($this -> discounts[$id]);
		unset($this -> taxes[$id]);
		unset($this -> comments[$id]); //added by vinod
		//echo "Item array before removing the items <br/>";
		//echo "<pre>";
		//print_r($this -> items);
		//echo "</pre>";
		// Rebuild the items array, excluding the id we just removed
		foreach ($this->items as $item) {
			if ($item != $id) {
				$tmpItems[] = $item;
			}
		}
		$this -> items = $tmpItems;
		//echo "Item array after removing the items <br/>";
		//echo "<pre>";
		//print_r($this -> items);
		//echo "</pre>";

		$this -> update_subtotal();
		
	}

	/**
	 * Empty the cart
	 */
	public function empty_cart() {
		$this -> items = array();
		$this -> names = array();
		$this -> prices = array();
		$this -> qtys = array();
		$this -> urls = array();
		$this -> discounts = array();//added by vinod
		$this -> taxes = array();//added by vinod
		$this -> comments = array();//added by vinod
		$this -> subtotal = 0;
		$this -> discounttotal = 0;
		$this -> taxtotal = 0;
		$this -> itemCount = 0;
		$this -> flat_discount = 0;
		$this -> total = 0;
		$this -> flat_discount_percent = 0;
		
	}

	/**
	 * Update the entire cart
	 */
	public function update_cart() {
		
		
		// Post value is an array of all item quantities in the cart
		// Treat array as a string for validation
		if (is_array($_POST['jcartItemQty'])) {
			$qtys = implode($_POST['jcartItemQty']);
		}
		if (is_array($_POST['jcartItemDiscount'])) {
				
			//added by vinod
			$discounts = implode($_POST['jcartItemDiscount']);
		}
		if (is_array($_POST['jcartItemTax'])) {
			
			//added by vinod
			$taxes = implode($_POST['jcartItemTax']);
		}
		if (is_array($_POST['jcartItemComment'])) {//added by vinod
			$comments = implode($_POST['jcartItemComment']);
		}
		// If no item ids, the cart is empty
		if ($_POST['jcartItemId']) {

			$validQtys = false;

			// If decimal quantities are enabled, verify the combined string only contain digits and decimal points
			if ($this -> config['decimalQtys'] === true && preg_match("/^[0-9.]+$/i", $qtys)) {
				$validQtys = true;
			}
			// By default, verify the string only contains integers
			elseif (filter_var($qtys, FILTER_VALIDATE_INT) || $qtys == '') {
				$validQtys = true;
			}

			if ($validQtys === true) {

				// The item index
				$count = 0;
				
				// For each item in the cart, remove or update as necessary
				foreach ($_POST['jcartItemId'] as $id) {

					//echo " the value of the count is". $count . "<br/>";

					$qty = $_POST['jcartItemQty'][$count];
					//echo " this is the value of qty".$_POST['jcartItemQty'][$count]."<br/>";
					$discount = $_POST['jcartItemDiscount'][$count];
					//echo " this is the value of Discount".$_POST['jcartItemDiscount'][$count]."<br/>";
					$tax = $_POST['jcartItemTax'][$count];
					//echo " this is the value of tax".$_POST['jcartItemTax'][$count]."<br/>";
					$comment = $_POST['jcartItemComment'][$count];
					//echo " this is the value of comment".$_POST['jcartItemQty'][$count]."<br/>";

					if ($qty < 1) {
						$this -> remove_item($id);
					} else {
						$this -> update_item($id, $qty, $comment,$discount,$tax);
						$this -> getFlatDiscount();
					}

					// Increment index for the next item
					$count++;
				}
				return true;
			}
		}
		// If no items in the cart, return true to prevent unnecssary error message
		elseif (!$_POST['jcartItemId']) {
			return true;
		}
	}

	/**
	 * Recalculate subtotal
	 */
	private function update_subtotal() {
		$this -> itemCount = 0;
		$this -> subtotal = 0;
		//Update the item_discount and taxes along with the subtotal
		if (sizeof($this -> items > 0)) {
			foreach ($this->items as $item) {
				$this -> subtotal += ($this -> qtys[$item] * $this -> prices[$item]);
				
				// Total number of items
				$this -> itemCount += $this -> qtys[$item];
			}
			$this -> update_discounttotal();
			$this -> getFlatDiscount();
			$this -> update_taxtotal();
			$this -> getTax();
			$this -> getTotal();
			
		}
	}
	/**
	 * Recalculate discounttotal
	 */
	private function update_discounttotal() {
		$this -> itemCount = 0;
		$this -> discounttotal = 0;
		
		if (sizeof($this -> items > 0)) {
			foreach ($this->items as $item) {
				$this -> discounttotal += ($this -> qtys[$item] * (($this -> prices[$item]*$this -> discounts[$item])/100));
				// Total number of items
				$this -> itemCount += $this -> qtys[$item];
			}
			
		}
		
	}
	/**
	 * Recalculate taxtotal
	 */
	private function update_taxtotal() {
		$this -> itemCount = 0;
		$this -> taxtotal = 0;
		
		if (sizeof($this -> items > 0)) {
			foreach ($this->items as $item) {
				$this -> taxtotal += ($this -> qtys[$item] * (($this -> prices[$item]*$this -> taxes[$item])/100));
				
				// Total number of items
				$this -> itemCount += $this -> qtys[$item];
			}
			
		}
		
	}
	/**
	 * Recalculate VAT
	 */
	private function getTax() {
		$tax_amount = (15*($this -> subtotal  - $this -> flat_discount))/100;
		
		//$tax_amount = (15*($this -> subtotal - $this -> discounttotal - $this -> flat_discount))/100;
		return $tax_amount;
	}
	/**
	 * Recalculate Total
	 */
	private function getTotal() {
		
		$tax_amount = $this -> getTax();//Overall tax
		$subtotal_amount = $this -> subtotal;//Value
		//$item_discount_amount = $this -> discounttotal;
		$this -> flat_discount = $this -> getFlatDiscount();
		//$this -> total = $subtotal_amount - $item_discount_amount - $this -> flat_discount + $tax_amount;
		$this -> total = $subtotal_amount - $this -> flat_discount + $tax_amount;
		
		return $this -> total;
	}
	/**
	 * Get the discount value
	 */
	 private function getFlatDiscount(){
	 		
	 	/**
		 * 
		 Get the flat discount % value form the ajax post and check whether the total amount is greater than zero or not
	 	if(isset($_POST['flatdiscount']) && $this -> total > 0){
			$this -> flat_discount_percent = $_POST['flatdiscount'];
			$this -> flat_discount = ($this -> flat_discount_percent * $this -> total)/100;
		}
		//Check whether the flat discount is set previously or not and the total amount is greater than zero or not
		elseif($this -> flat_discount_percent > 0 && $this -> total > 0){
			$this -> flat_discount = ($this -> flat_discount_percent * $this -> total)/100;
		}
		else{
			$this -> flat_discount_percent = 0;
			$this -> flat_discount = 0;
		}
		return $this -> flat_discount;
		**/
		if(isset($_POST['flatdiscount']) && $this -> subtotal > 0){
			//echo "this is the value of the discount";
			$this -> flat_discount_percent = $_POST['flatdiscount'];
			$this -> flat_discount = ($this -> flat_discount_percent * $this -> subtotal)/100;
			//echo "this is the value of discount ".$this -> flat_discount;
		}
		//Check whether the flat discount is set previously or not and the total amount is greater than zero or not
		elseif($this -> flat_discount_percent > 0 && $this -> subtotal > 0){
			$this -> flat_discount = ($this -> flat_discount_percent * $this -> subtotal)/100;
		}
		else{
			$this -> flat_discount_percent = 0;
			$this -> flat_discount = 0;
		}
		return $this -> flat_discount;
	 }
	/**
	 * Process and display cart
	 */
	public function display_cart() {

		$config = $this -> config;
		$errorMessage = null;

		// Simplify some config variables
		$checkout = $config['checkoutPath'];
		
		$priceFormat = $config['priceFormat'];

		$id = $config['item']['id'];
		$name = $config['item']['name'];
		$price = $config['item']['price'];
		$qty = $config['item']['qty'];
		$url = $config['item']['url'];
		$add = $config['item']['add'];
		$discount = $config['item']['discount'];//added by vinod
		$tax = $config['item']['tax'];//added by vinod
		$comment = $config['item']['comment'];//added by vinod
		
		// Use config values as literal indices for incoming POST values
		// Values are the HTML name attributes set in config.json
		$id = $_POST[$id];
		$name = $_POST[$name];
		$price = $_POST[$price];
		$qty = $_POST[$qty];
		$url = $_POST[$url];
		$discount = $_POST[$discount];//added by vinod
		$tax = $_POST[$tax];//added by vinod
		
		$comment = $_POST[$comment];//added by vinod
		// Optional CSRF protection, see: http://conceptlogic.com/jcart/security.php
		$jcartToken = $_POST['jcartToken'];

		// Only generate unique token once per session
		if (!$_SESSION['jcartToken']) {
			$_SESSION['jcartToken'] = md5(session_id() . time() . $_SERVER['HTTP_USER_AGENT']);
		}
		// If enabled, check submitted token against session token for POST requests
		if ($config['csrfToken'] === 'true' && $_POST && $jcartToken != $_SESSION['jcartToken']) {
			$errorMessage = 'Invalid token!' . $jcartToken . ' / ' . $_SESSION['jcartToken'];
		}

		// Sanitize values for output in the browser
		$id = filter_var($id, FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_STRIP_LOW);
		$name = filter_var($name, FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_STRIP_LOW);
		$url = filter_var($url, FILTER_SANITIZE_URL);

		// Round the quantity if necessary
		if ($config['decimalPlaces'] === true) {
			$qty = round($qty, $config['decimalPlaces']);
		}

		// Add an item
		if ($_POST[$add]) {
			$itemAdded = $this -> add_item($id, $name, $price, $qty, $url,$comment,$discount,$tax);
			// If not true the add item function returns the error type
			if ($itemAdded !== true) {
				$errorType = $itemAdded;
				switch($errorType) {
					case 'qty' :
						$errorMessage = $config['text']['quantityError'];
						break;
					case 'price' :
						$errorMessage = $config['text']['priceError'];
						break;
				}
			}
		}

		// Update a single item
		if (isset($_POST['jcartUpdate'])) {
			$itemUpdated = $this -> update_item($_POST['itemId'], $_POST['itemQty'],$_POST['itemComment'],$_POST['itemDiscount'],$_POST['itemTax']);
			if ($itemUpdated !== true) {
				$errorMessage = $config['text']['quantityError'];
			}
		}

		// Update all items in the cart
		if ($_POST['jcartUpdateCart'] || $_POST['jcartCheckout']) {
			$cartUpdated = $this -> update_cart();
			if ($cartUpdated !== true) {
				$errorMessage = $config['text']['quantityError'];
			}
		}

		// Remove an item
		/* After an item is removed, its id stays set in the query string,
		 preventing the same item from being added back to the cart in
		 subsequent POST requests.  As result, it's not enough to check for
		 GET before deleting the item, must also check that this isn't a POST
		 request. */
		if ($_GET['jcartRemove'] && !$_POST) {
			$this -> remove_item($_GET['jcartRemove']);
		}

		// Empty the cart
		if ($_POST['jcartEmpty']) {
			$this -> empty_cart();
		}

		// Determine which text to use for the number of items in the cart
		$itemsText = $config['text']['multipleItems'];
		if ($this -> itemCount == 1) {
			$itemsText = $config['text']['singleItem'];
		}

		// Determine if this is the checkout page
		/* First we check the request uri against the config checkout (set when
		 the visitor first clicks checkout), then check for the hidden input
		 sent with Ajax request (set when visitor has javascript enabled and
		 updates an item quantity). */
		$isCheckout = strpos(request_uri(), $checkout);
		if ($isCheckout !== false || $_REQUEST['jcartIsCheckout'] == 'true') {
			$isCheckout = true;
		} else {
			$isCheckout = false;
		}

		// Overwrite the form action to post to gateway.php instead of posting back to checkout page
		if ($isCheckout === true) {

			// Sanititze config path
			$path = filter_var($config['jcartPath'], FILTER_SANITIZE_URL);

			// Trim trailing slash if necessary
			$path = rtrim($path, '/');

			//$checkout = $path . '/gateway.php';
			$checkout = $path . '/checkout.php';
		}

		// Default input type
		// Overridden if using button images in config.php
		$inputType = 'submit';

		// If this error is true the visitor updated the cart from the checkout page using an invalid price format
		// Passed as a session var since the checkout page uses a header redirect
		// If passed via GET the query string stays set even after subsequent POST requests
		if ($_SESSION['quantityError'] === true) {
			$errorMessage = $config['text']['quantityError'];
			unset($_SESSION['quantityError']);
		}

		// Set currency symbol based on config currency code
		$currencyCode = trim(strtoupper($config['currencyCode']));
		switch($currencyCode) {
			case 'EUR' :
				$currencySymbol = '&#128;';
				break;
			case 'GBP' :
				$currencySymbol = '&#163;';
				break;
			case 'JPY' :
				$currencySymbol = '&#165;';
				break;
			case 'CHF' :
				$currencySymbol = 'CHF&nbsp;';
				break;
			case 'SEK' :
			case 'DKK' :
			case 'NOK' :
				$currencySymbol = 'Kr&nbsp;';
				break;
			case 'PLN' :
				$currencySymbol = 'z&#322;&nbsp;';
				break;
			case 'HUF' :
				$currencySymbol = 'Ft&nbsp;';
				break;
			case 'CZK' :
				$currencySymbol = 'K&#269;&nbsp;';
				break;
			case 'ILS' :
				$currencySymbol = '&#8362;&nbsp;';
				break;
			case 'TWD' :
				$currencySymbol = 'NT$';
				break;
			case 'THB' :
				$currencySymbol = '&#3647;';
				break;
			case 'MYR' :
				$currencySymbol = 'RM';
				break;
			case 'PHP' :
				$currencySymbol = 'Php';
				break;
			case 'BRL' :
				$currencySymbol = 'R$';
				break;
			case 'USD' :
			default :
				$currencySymbol = '$';
				break;
		}

		////////////////////////////////////////////////////////////////////////
		// Output the cart

		// Return specified number of tabs to improve readability of HTML output
		function tab($n) {
			$tabs = null;
			while ($n > 0) {
				$tabs .= "\t";
				--$n;
			}
			return $tabs;
		}

		// If there's an error message wrap it in some HTML
		if ($errorMessage) {
			$errorMessage = "<p id='jcart-error'>$errorMessage</p>";
		}

		// Display the cart header
		echo tab(1) . "$errorMessage\n";
		echo tab(1) . "<form method='post' action='checkout.php'>\n";
		echo tab(2) . "<fieldset>\n";
		echo tab(3) . "<input type='hidden' name='jcartToken' value='{$_SESSION['jcartToken']}' />\n";
		echo tab(3) . "<table border='1'>\n";
		echo tab(4) . "<thead>\n";
		echo tab(5) . "<tr>\n";
		echo tab(7) . "<th colspan='4'>\n";
		echo tab(7) . "<strong id='jcart-title'>{$config['text']['cartTitle']}</strong> ($this->itemCount $itemsText)\n";
		echo tab(6) . "</th>\n";
		echo tab(5) . "</tr>" . "\n";
		echo tab(4) . "</thead>\n";

		// Display the cart footer
		echo tab(4) . "<tfoot>\n";
		echo tab(5) . "<tr>\n";
		echo tab(6) . "<th colspan='2'>\n";
		echo tab(7) . "<span id='jcart-subtotal'>{$config['text']['subtotal']}</span>\n";
		echo tab(6) . "</th>\n";
		echo tab(6) . "<th>\n";
		echo tab(7) . "<span ><strong>$currencySymbol" . number_format($this -> subtotal, $priceFormat['decimals'], $priceFormat['dec_point'], $priceFormat['thousands_sep']) . "</strong></span>\n";
		echo tab(6) . "</th>\n";
		echo tab(5) . "</tr>\n";
		
		//echo tab(5) . "<tr>\n";
		//echo tab(6) . "<th colspan='4'>\n";
		//echo tab(7) . "<span id='jcart-discounttotal'>( - ) {$config['text']['discounttotal']}: <strong>$currencySymbol" . number_format($this -> discounttotal, $priceFormat['decimals'], $priceFormat['dec_point'], $priceFormat['thousands_sep']) . "</strong></span>\n";
		//echo tab(6) . "</th>\n";
		//echo tab(5) . "</tr>\n";
		
		echo tab(5) . "<tr>\n";
		echo tab(6) . "<th colspan='2'>\n";
		echo tab(7) . "<span id='jcart-discount'>( - ) Overall Discount</span>\n";
		echo tab(6) . "</th>\n";
		echo tab(6) . "<th >\n";
		echo tab(7) . "<span><strong>$currencySymbol" . number_format($this -> flat_discount, $priceFormat['decimals'], $priceFormat['dec_point'], $priceFormat['thousands_sep']) . "</strong></span>\n";
		echo tab(6) . "</th>\n";
		echo tab(5) . "</tr>\n";
		
		echo tab(5) . "<tr>\n";
		echo tab(6) . "<th colspan='2'>\n";
		echo tab(7) . "<span id='jcart-total-wo-tax'> Total </span>\n";
		echo tab(6) . "</th>\n";
		echo tab(6) . "<th >\n";
		echo tab(7) . "<span > <strong>$currencySymbol" . number_format($this -> subtotal - $this -> flat_discount, $priceFormat['decimals'], $priceFormat['dec_point'], $priceFormat['thousands_sep']) . "</strong></span>\n";
		echo tab(6) . "</th>\n";
		echo tab(5) . "</tr>\n";
		
		echo tab(5) . "<tr>\n";
		echo tab(6) . "<th colspan='2'>\n";
		echo tab(7) . "<span id='jcart-taxtotal'>( +15% ) {$config['text']['taxtotal']} </span>\n";
		echo tab(6) . "</th>\n";
		echo tab(6) . "<th>\n";
		echo tab(7) . "<span ><strong>$currencySymbol" . number_format($this -> getTax(), $priceFormat['decimals'], $priceFormat['dec_point'], $priceFormat['thousands_sep']) . "</strong></span>\n";
		echo tab(6) . "</th>\n";
		echo tab(5) . "</tr>\n";

		echo tab(5) . "<tr>\n";
		echo tab(6) . "<th colspan='2'>\n";
		echo tab(7) . "<span id='jcart-total'>Grand Total </span>\n";
		echo tab(6) . "</th>\n";
		echo tab(6) . "<th colspan='4'>\n";
		echo tab(7) . "<span><strong>$currencySymbol" . number_format($this -> getTotal(), $priceFormat['decimals'], $priceFormat['dec_point'], $priceFormat['thousands_sep']) . "</strong></span>\n";
		echo tab(6) . "</th>\n";
		echo tab(5) . "</tr>\n";
		
		echo tab(5) . "<tr>\n";
		echo tab(6) . "<th colspan='4'>\n";
		// If this is the checkout hide the cart checkout button
		if ($isCheckout !== true) {
			if ($config['button']['checkout']) {
				$inputType = "image";
				$src = " src='{$config['button']['checkout']}' alt='{$config['text']['checkout']}' title='' ";
			}
			echo tab(7) . "<input type='$inputType' $src id='jcart-checkout' name='jcartCheckout' class='jcart-button' value='{$config['text']['checkout']}' />\n";
		}echo tab(6) . "</th>\n";
		echo tab(5) . "</tr>\n";
		echo tab(5) . "<tr>\n";
		echo tab(4) . "</tfoot>\n";

		echo tab(4) . "<tbody>\n";

		// If any items in the cart
		if ($this -> itemCount > 0) {

			// Display line items
			foreach ($this->get_contents() as $item) {
				echo tab(5) . "<tr>\n";
				echo tab(6) . "<td class='jcart-item-qty'>\n";
				echo tab(7) . "<input name='jcartItemId[]' type='hidden' value='{$item['id']}' />\n";
				echo tab(7) . "<input id='jcartItemQty-{$item['id']}' name='jcartItemQty[]' size='2' type='text' value='{$item['qty']}' />\n";
				echo tab(6) . "</td>\n";
				echo tab(6) . "<td class='jcart-item-name'>\n";

				if ($item['url']) {
					echo tab(7) . "<a href='{$item['url']}'>{$item['name']}</a>\n";
				} else {
					echo tab(7) . $item['name'] . "\n";
				}
				echo tab(7) . "<input name='jcartItemName[]' type='hidden' value='{$item['name']}' />\n";
				echo tab(6) . "</td>\n";
				echo tab(6) . "<td class='jcart-item-price'>\n";
				echo tab(7) . "<span>$currencySymbol" . number_format($item['subtotal'], $priceFormat['decimals'], $priceFormat['dec_point'], $priceFormat['thousands_sep']) . "</span><input name='jcartItemPrice[]' type='hidden' value='{$item['price']}' />\n";
				echo tab(7) . "<a class='jcart-remove' href='?jcartRemove={$item['id']}'>{$config['text']['removeLink']}</a>\n";
				echo tab(6) . "</td>\n";
				echo tab(5) . "</tr>\n";
				//Added by vinod
				echo tab(6) . "<td class='jcart-item-comment'>\n";
				echo tab(7) . "<input id='jcartItemComment-{$item['id']}' name='jcartItemComment[]' size='15' type='text' value='{$item['comment']}' />\n";
				echo tab(6) . "</td>\n";
				echo tab(6) . "<td class='jcart-item-discount'>\n";
				echo tab(7) . "<input type='hidden' id='jcartItemDiscount-{$item['id']}' name='jcartItemDiscount[]' size='15' type='text' value='{$item['discount']}' />\n";
				echo tab(6) . "</td>\n";
				echo tab(6) . "<td class='jcart-item-tax'>\n";
				echo tab(7) . "<input type='hidden' id='jcartItemTax-{$item['id']}' name='jcartItemTax[]' size='15' type='text' value='{$item['tax']}' />\n";
				echo tab(6) . "</td>\n";
				echo tab(5) . "</tr>\n";
			}
		}

		// The cart is empty
		else {
			echo tab(5) . "<tr><td id='jcart-empty' colspan='4'>{$config['text']['emptyMessage']}</td></tr>\n";
		}
		echo tab(4) . "</tbody>\n";
		echo tab(3) . "</table>\n\n";

		echo tab(3) . "<div id='jcart-buttons'>\n";

		if ($config['button']['update']) {
			$inputType = "image";
			$src = " src='{$config['button']['update']}' alt='{$config['text']['update']}' title='' ";
		}

		echo tab(4) . "<input type='$inputType' $src name='jcartUpdateCart' value='{$config['text']['update']}' class='jcart-button' />\n";

		if ($config['button']['empty']) {
			$inputType = "image";
			$src = " src='{$config['button']['empty']}' alt='{$config['text']['emptyButton']}' title='' ";
		}

		echo tab(4) . "<input type='$inputType' $src name='jcartEmpty' value='{$config['text']['emptyButton']}' class='jcart-button' />\n";
		echo tab(3) . "</div>\n";

		// If this is the checkout display the PayPal checkout button
		if ($isCheckout === true) {
			// Hidden input allows us to determine if we're on the checkout page
			// We normally check against request uri but ajax update sets value to relay.php
			echo tab(3) . "<input type='hidden' id='jcart-is-checkout' name='jcartIsCheckout' value='true' />\n";

			// PayPal checkout button
			if ($config['button']['checkout']) {
				$inputType = "image";
				$src = " src='{$config['button']['checkout']}' alt='{$config['text']['checkoutPaypal']}' title='' ";
			}

			if ($this -> itemCount <= 0) {
				$disablePaypalCheckout = " disabled='disabled'";
			}

			echo tab(3) . "<input type='$inputType' $src id='jcart-paypal-checkout' name='jcartPaypalCheckout' value='{$config['text']['checkoutPaypal']}' $disablePaypalCheckout />\n";
		}

		echo tab(2) . "</fieldset>\n";
		echo tab(1) . "</form>\n\n";

		echo tab(1) . "<div id='jcart-tooltip'></div>\n";
	}

}

// Start a new session in case it hasn't already been started on the including page
@session_start();

// Initialize jcart after session start
$jcart = $_SESSION['jcart'];
if (!is_object($jcart)) {
	$jcart = $_SESSION['jcart'] = new Jcart();
}

// Enable request_uri for non-Apache environments
// See: http://api.drupal.org/api/function/request_uri/7
if (!function_exists('request_uri')) {
	function request_uri() {
		if (isset($_SERVER['REQUEST_URI'])) {
			$uri = $_SERVER['REQUEST_URI'];
		} else {
			if (isset($_SERVER['argv'])) {
				$uri = $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['argv'][0];
			} elseif (isset($_SERVER['QUERY_STRING'])) {
				$uri = $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['QUERY_STRING'];
			} else {
				$uri = $_SERVER['SCRIPT_NAME'];
			}
		}
		$uri = '/' . ltrim($uri, '/');
		return $uri;
	}

}
?>