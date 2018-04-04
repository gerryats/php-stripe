<?php
// Codeigniter access check, remove it for direct use
if( !defined( 'BASEPATH' ) ) exit( 'No direct script access allowed' );

// Set the server api endpoint and http methods as constants
define( 'STRIPE_API_ENDPOINT', 'https://api.stripe.com/v1/' );
define( 'STRIPE_METHOD_POST', 'post' );
define( 'STRIPE_METHOD_DELETE', 'delete' );

/**
 * A simple to use library to access the stripe.com services
 * 
 * @copyright   Copyright (c) 2011 Pixative Solutions
 * @author      Ben Cessa <ben@pixative.com>
 * @author_url  http://www.pixative.com
 */
class Stripe {
	/**
	 * Holder for the initial configuration parameters 
	 * 
	 * @var     resource
	 * @access  private
	 */
	private $_conf = NULL;
	
	/**
	 * Constructor method
	 * 
	 * @param  array         Configuration parameters for the library
	 */
	public function __construct( $params ) {
		// Store the config values
		$this->_conf = $params;
	}
	
	/**
	 * Create and apply a charge to an existent user based on it's customer_id
	 * 
	 * @param  int           The amount to charge in cents ( USD ) 
	 * @param  string        The customer id of the charge subject
	 * @param  string        A free form reference for the charge
	 */
	public function charge_customer( $amount, $customer_id, $desc ) {
		$params = array(
			'amount' => $amount,
			'currency' => 'usd',
			'customer' => $customer_id,
			'description' => $desc
		);
		
		return $this->_send_request( 'charges', $params, STRIPE_METHOD_POST );
	}
	
	/**
	 * Create and apply a charge based on credit card information
	 * 
	 * @param  int           The amount to charge in cents ( USD )
	 * @param  mixed         This can be a card token generated with stripe.js ( recommended ) or
	 *                       an array with the card information: number, exp_month, exp_year, cvc, name
	 * @param  string        A free form reference for the charge
	 */
	public function charge_card( $amount, $card, $desc ) {
		$params = array(
			'amount' => $amount,
			'currency' => 'usd',
			'card' => $card,
			'description' => $desc
		);
		
		return $this->_send_request( 'charges', $params, STRIPE_METHOD_POST );
	}
	
	/**
	 * Retrieve information about a specific charge
	 * 
	 * @param string         The charge ID to query
	 */
	public function charge_info( $charge_id ) {
		return $this->_send_request( 'charges/'.$charge_id );
	}
	
	/**
	 * Refund a charge
	 * 
	 * @param  string        The charge ID to refund
	 * @param  int           The amount to refund, defaults to the total amount charged
	 */
	public function charge_refund( $charge_id, $amount = FALSE ) {
		$amount ? $params = array( 'amount' => $amount ) : $params = array();
		return $this->_send_request( 'charges/'.$charge_id.'/refund', $params, STRIPE_METHOD_POST );
	}
	
	/**
	 * Get a list of charges, either general or for a certain customer
	 * 
	 * @param  int           The number of charges to return, default 10, max 100
	 * @param  int           Offset to apply to the list, default 0
	 * @param  string        A customer ID to return only charges for that customer
	 */
	public function charge_list( $count = 10, $offset = 0, $customer_id = FALSE ) {
		$params['count'] = $count;
		$params['offset'] = $offset;
		if( $customer_id )
			$params['customer'] = $customer_id;
		$vars = http_build_query( $params, NULL, '&' );
		
		return $this->_send_request( 'charges?'.$vars );
	}
	
	/**
	 * Creates a new customer object. https://stripe.com/docs/api#create_customer
	 * 
	 * @param  mixed        The source can either be a Token’s or a Source’s ID, as returned by Elements,
	 *                        or a dictionary containing a user’s credit card details (object (The type of payment source. Should be "card".), exp_month, exp_year, number, address_city .etc ).
	 * @param  string        Customer’s email address. It’s displayed alongside the customer in your dashboard and can be useful for searching and tracking. This may be up to 512 characters. This will be unset if you POST an empty value.
	 * @param  string        An arbitrary string that you can attach to a customer object. It is displayed alongside the customer in the dashboard.
	 * @param  int        An integer amount in pence that is the starting account balance for your customer. A negative amount represents a credit that will be used before attempting any charges to the customer’s card; a positive amount will be added to the next invoice.
	 * @param  string  The customer’s VAT identification number. If you are using Relay, this field gets passed to tax provider you are using for your orders.
	 * @param  string If you provide a coupon code, the customer will have a discount applied on all recurring charges. Charges you create through the API will not have the discount.
	 * @param  string  Default source
	 * @param  array A set of key/value pairs that you can attach to a customer object. It can be useful for storing additional information about the customer in a structured format.
	 * @param  dictionary (address, name, phone)
	 */
	public function customer_create( $source, $email, $desc = NULL, $account_balance = NULL, $business_vat_id = NULL, $coupon = NULL, $default_source = NULL, $metadata = NULL, $shipping = NULL ) {
		$params = array(
			'source' => $source,
			'email' => $email
		);
		if( $desc ) $params['description'] = $desc;
		if( $account_balance ) $params['account_balance'] = $account_balance;
		if( $business_vat_id ) $params['business_vat_id'] = $business_vat_id;
		if( $coupon ) $params['coupon'] = $coupon;
		if( $default_source ) $params['default_source'] = $default_source;
		if( $metadata ) $params['metadata'] = $metadata;
		if( $shipping ) $params['shipping'] = $shipping;

		return $this->_send_request( 'customers', $params, STRIPE_METHOD_POST );
	}
	
	/**
	 * Retrieve a customer. https://stripe.com/docs/api#retrieve_customer
	 * 
	 * @param  string  The identifier of the customer to be retrieved.
	 */
	public function customer_info( $customer_id ) {
		return $this->_send_request( 'customers/'.$customer_id );
	}
	
	/**
	 * Update a customer https://stripe.com/docs/api#update_customer
	 * 
	 * @param  string        The customer ID for the record to update
	 * @param  array         An array containing the new data for the user, you may use the
	 *                       following keys: account_balance, business_vat_id, coupon, default_source, description, email, metadata, shipping, source, 
	 */
	public function customer_update( $customer_id, $newdata ) {
		return $this->_send_request( 'customers/'.$customer_id, $newdata, STRIPE_METHOD_POST );
	}
	
	/**
	 * Delete a customer. https://stripe.com/docs/api#delete_customer
	 * 
	 * @param  string  The identifier of the customer to be deleted.
	 */
	public function customer_delete( $customer_id ) {
		return $this->_send_request( 'customers/'.$customer_id, array(), STRIPE_METHOD_DELETE );
	}
	
	/**
	 * List all customers. https://stripe.com/docs/api#list_customers
	 * 
	 * @param  int           The number of customers to return, default 10, max 100
	 * @param  int           Offset to apply to the list, default 0
	 * @param  dictionary  A filter on the list based on the object created field.
	 * @param  string  A filter on the list based on the customer’s email field. The value must be a string. This will be unset if you POST an empty value.
	 * @param  string A cursor for use in pagination. An object ID that defines your place in the list. 
	 * @param  string A cursor for use in pagination. An object ID that defines your place in the list. 
	 */
	public function customer_list( $limit = 10, $offset = 0, $created = NULL, $email = NULL, $ending_before = NULL, $starting_after = NULL ) {
		$params['limit'] = $limit;
		$params['offset'] = $offset;
		if($created) $params['created'] = $created;
		if($email) $params['email'] = $email;
		if($ending_before) $params['ending_before'] = $ending_before;
		if($starting_after) $params['starting_after'] = $starting_after;
		$vars = http_build_query( $params, NULL, '&' );
		
		return $this->_send_request( 'customers?'.$vars );
	}
	
	/**
	 * Create a subscription.  https://stripe.com/docs/api#create_subscription
	 * 
	 * @param  string        The identifier of the customer to subscribe.
	 * @param  string        Hash describing the plan the customer is subscribed to.
	 * @param  array         Configuration options for the subscription:items, billing, prorate, coupon, trial_end etc.
	 */
	public function customer_subscribe( $customer_id, $plan_id, $options = array() ) {
		$params = array('customer' => $customer_id, 'plan' => $plan_id);

		$sub_options = array(
			'application_fee_percent', 'billing', 'billing_cycle_anchor', 'coupon', 'days_until_due', 'items',
			'metadata', 'prorate', 'source', 'tax_percent', 'trial_end', 'trial_period_days'
		);

		foreach($options as $key => $value){
			if(in_array($key, $sub_options)) $params[$key] = $value;
		}
		
		return $this->_send_request( 'subscriptions', $params, STRIPE_METHOD_POST );
	}
	
	/**
	 * Cancel a subscription.  https://stripe.com/docs/api#cancel_subscription
	 * 
	 * @param  string        The subscription ID
	 * @param  boolean   A flag that, if set to true, will delay the subscription’s cancellation until the end of the current period.
	 */
	public function customer_unsubscribe( $subscription_id, $at_period_end = FALSE ) {
		$url = 'subscriptions/'.$subscription_id;
		if($at_period_end) $url .= '?at_period_end=true';
		 
		return $this->_send_request( $url, array(), STRIPE_METHOD_DELETE );
	}
	
	/**
	 * Get the next upcoming invoice for a given customer
	 * 
	 * @param  string        Customer ID to get the invoice from
	 */
	public function customer_upcoming_invoice( $customer_id ) {
		return $this->_send_request( 'invoices/upcoming?customer='.$customer_id );
	}
	
	/**
	 * Generate a new single-use stripe card token
	 * 
	 * @param  array         An array containing the credit card data, with the following keys:
	 *                       number, cvc, exp_month, exp_year, name
	 * @param  int           If the token will be used on a charge, this is the amount to charge for
	 */
	public function card_token_create( $card_data, $amount ) {
		$params = array(
			'card' => $card_data,
			'amount' => $amount,
			'currency' => 'usd'
		);
		
		return $this->_send_request( 'tokens', $params, STRIPE_METHOD_POST );
	}
	
	/**
	 * Get information about a card token
	 * 
	 * @param  string        The card token ID to get the information
	 */
	public function card_token_info( $token_id ) {
		return $this->_send_request( 'tokens/'.$token_id );
	}
	
	/**
	 * Create a new subscription plan on the system
	 * 
	 * @param  string        The plan identifier, this will be used when subscribing customers to it
	 * @param  int           The amount in cents to charge for each period
	 * @param  string        The plan name, will be displayed in invoices and the web interface
	 * @param  string        The interval to apply on the plan, could be 'month' or 'year'
	 * @param  int           Number of days for the trial period, if any
	 */
	public function plan_create( $plan_id, $amount, $name, $interval, $trial_days  = FALSE ) {
		$params = array(
			'id' => $plan_id,
			'amount' => $amount,
			'name' => $name,
			'currency' => 'usd',
			'interval' => $interval
		);
		if( $trial_days )
			$params['trial_period_days'] = $trial_days;
			
		return $this->_send_request( 'plans', $params, STRIPE_METHOD_POST );
	}
	
	/**
	 * Retrieve information about a given plan
	 * 
	 * @param  string        The plan identifier you wish to get info about
	 */
	public function plan_info( $plan_id ) {
		return $this->_send_request( 'plans/'.$plan_id );
	}
	
	/**
	 * Delete a plan from the system
	 * 
	 * @param  string        The identifier of the plan you want to delete
	 */
	public function plan_delete( $plan_id ) {
		return $this->_send_request( 'plans/'.$plan_id, array(), STRIPE_METHOD_DELETE );
	}
	
	/**
	 * Retrieve a list of the plans in the system
	 */
	public function plan_list( $count = 10, $offset = 0 ) {
		$params['count'] = $count;
		$params['offset'] = $offset;
		$vars = http_build_query( $params, NULL, '&' );
		
		return $this->_send_request( 'plans?'.$vars );
	}
	
	/**
	 * Get infomation about a specific invoice
	 * 
	 * @param  string        The invoice ID
	 */
	public function invoice_info( $invoice_id ) {
		return $this->_send_request( 'invoices/'.$invoice_id );
	}
	
	/**
	 * Get a list of invoices on the system
	 * 
	 * @param  string        Customer ID to retrieve invoices only for a given customer
	 * @param  int           Number of invoices to retrieve, default 10, max 100
	 * @param  int           Offset to start the list from, default 0
	 */
	public function invoice_list( $customer_id = NULL, $count = 10, $offset = 0 ) {
		$params['count'] = $count;
		$params['offset'] = $offset;
		if( $customer_id )
			$params['customer'] = $customer_id;
		$vars = http_build_query( $params, NULL, '&' );
		
		return $this->_send_request( 'invoices?'.$vars );
	}
	
	/**
	 * Register a new invoice item to the upcoming invoice for a given customer
	 * 
	 * @param  string        The customer ID
	 * @param  int           The amount to charge in cents
	 * @param  string        A free form description explaining the charge
	 */
	public function invoiceitem_create( $customer_id, $amount, $desc ) {
		$params = array(
			'customer' => $customer_id,
			'amount' => $amount,
			'currency' => 'usd',
			'description' => $desc
		);
		
		return $this->_send_request( 'invoiceitems', $params, STRIPE_METHOD_POST );
	}
	
	/**
	 * Get information about a specific invoice item
	 * 
	 * @param  string        The invoice item ID
	 */
	public function invoiceitem_info( $invoiceitem_id ) {
		return $this->_send_request( 'invoiceitems/'.$invoiceitem_id );
	}
	
	/**
	 * Update an invoice item before is actually charged
	 * 
	 * @param  string        The invoice item ID
	 * @param  int           The amount for the item in cents
	 * @param  string        A free form string describing the charge
	 */
	public function invoiceitem_update( $invoiceitem_id, $amount, $desc = FALSE ) {
		$params['amount'] = $amount;
		$params['currency'] = 'usd';
		if( $desc ) $params['description'] = $desc;
		
		return $this->_send_request( 'invoiceitems/'.$invoiceitem_id, $params, STRIPE_METHOD_POST );
	}
	
	/**
	 * Delete a specific invoice item
	 * 
	 * @param  string        The invoice item identifier
	 */
	public function invoiceitem_delete( $invoiceitem_id ) {
		return $this->_send_request( 'invoiceitems/'.$invoiceitem_id, array(), STRIPE_METHOD_DELETE );
	}
	
	/**
	 * Get a list of invoice items
	 * 
	 * @param  string        Customer ID to retrieve invoices only for a given customer
	 * @param  int           Number of invoices to retrieve, default 10, max 100
	 * @param  int           Offset to start the list from, default 0
	 */
	public function invoiceitem_list( $customer_id = FALSE, $count = 10, $offset = 0 ) {
		$params['count'] = $count;
		$params['offset'] = $offset;
		if( $customer_id )
			$params['customer'] = $customer_id;
		$vars = http_build_query( $params, NULL, '&' );
		
		return $this->_send_request( 'invoiceitems?'.$vars );
	}
	
	/**
	 * Private utility function that prepare and send the request to the API servers
	 * 
	 * @param  string        The URL segments to use to complete the http request
	 * @param  array         The parameters for the request, if any
	 * @param  srting        Either 'post','get' or 'delete' to determine the request method, 'get' is default
	 */
	private function _send_request( $url_segs, $params = array(), $http_method = 'get' ) {
		if( $this->_conf['stripe_test_mode'] )
			$key = $this->_conf['stripe_key_test_secret'];
		else
			$key = $this->_conf['stripe_key_live_secret'];
			
		// Initializ and configure the request
		$req = curl_init( 'https://api.stripe.com/v1/'.$url_segs );
		curl_setopt( $req, CURLOPT_SSL_VERIFYPEER, $this->_conf['stripe_verify_ssl'] );
		curl_setopt( $req, CURLOPT_HTTPAUTH, CURLAUTH_ANY );
		curl_setopt( $req, CURLOPT_USERPWD, $key.':' );
		curl_setopt( $req, CURLOPT_RETURNTRANSFER, TRUE );
		
		// Are we using POST? Adjust the request properly
		if( $http_method == STRIPE_METHOD_POST ) {
			curl_setopt( $req, CURLOPT_POST, TRUE );
			curl_setopt( $req, CURLOPT_POSTFIELDS, http_build_query( $params, NULL, '&' ) );
		}
		
		if( $http_method == STRIPE_METHOD_DELETE ) {
			curl_setopt( $req, CURLOPT_CUSTOMREQUEST, "DELETE" );
			curl_setopt( $req, CURLOPT_POSTFIELDS, http_build_query( $params, NULL, '&' ) );
		}
		
		// Get the response, clean the request and return the data
		$response = curl_exec( $req );
		curl_close( $req );
		return $response;
	}
}
// END Stripe Class

/* End of file Stripe.php */
/* Location: ./{APPLICATION}/libraries/Stripe.php */
