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
	 * Create a charge - customer.  https://stripe.com/docs/api#create_charge
	 * 
	 * @param  int           A positive integer representing how much to charge, in the smallest currency unit. e.g. cents
	 * @param  string        The ID of an existing customer that will be charged in this request.
	 * @param  string        An arbitrary string which you can attach to a Charge object. It is displayed when in the web interface alongside the charge.
	 * @param  array  Other options for the charge: capture, metadata, receipt_email etc.
	 */
	public function charge_customer( $amount, $customer_id, $desc = NULL, $options = array() ) {
		$params = array(
			'amount' => $amount,
			'currency' => 'usd',
			'customer' => $customer_id
		);

		if( $desc ) $params['description'] = $desc;

		$sub_options = array(
			'capture', 'metadata', 'receipt_email', 'shipping', 'statement_descriptor', 
			'application_fee', 'destination', 'transfer_group', 'on_behalf_of'
		);

		foreach($options as $key => $value){
			if(in_array($key, $sub_options)) $params[$key] = $value;
		}
		
		return $this->_send_request( 'charges', $params, STRIPE_METHOD_POST );
	}
	
	/**
	 * Create a charge - card.  https://stripe.com/docs/api#create_charge
	 * 
	 * @param  int  A positive integer representing how much to charge, in the smallest currency unit. e.g. cents
	 * @param  string  A payment source to be charged, such as a credit card. If you also pass a customer ID, the source must be the ID of a source belonging to the customer (e.g., a saved card). Otherwise, if you do not pass a customer ID, the source you provide must be either a token, like the ones returned by Stripe.js, or a dictionary containing a user's credit card details. 
	 * @param  string  An arbitrary string which you can attach to a Charge object. It is displayed when in the web interface alongside the charge.
	 * @param  array  Other options for the charge: capture, metadata, receipt_email etc.
	 */
	public function charge_card( $amount, $source, $desc = NULL, $options = array() ) {
		$params = array(
			'amount' => $amount,
			'currency' => 'usd',
			'source' => $source
		);

		if( $desc ) $params['description'] = $desc;

		$sub_options = array(
			'customer', 'capture', 'metadata', 'receipt_email', 'shipping', 'statement_descriptor', 
			'application_fee', 'destination', 'transfer_group', 'on_behalf_of'
		);

		foreach($options as $key => $value){
			if(in_array($key, $sub_options)) $params[$key] = $value;
		}
		
		return $this->_send_request( 'charges', $params, STRIPE_METHOD_POST );
	}
	
	/**
	 * Retrieve a charge.  https://stripe.com/docs/api#retrieve_charge
	 * 
	 * @param string  The identifier of the charge to be retrieved.
	 */
	public function charge_info( $charge_id ) {
		return $this->_send_request( 'charges/'.$charge_id );
	}

	/**
	 * Update a charge. https://stripe.com/docs/api#update_charge
	 * 
	 * @param  string  The identifier of the charge to update.
	 * @param  array  Options for the charge update.
	 */
	public function charge_update( $charge_id, $options = array() ) {
		$params = array();

		$sub_options = array('customer', 'description', 'fraud_details', 'metadata', 'receipt_email', 'shipping', 'transfer_group');

		foreach($options as $key => $value){
			if(in_array($key, $sub_options)) $params[$key] = $value;
		}

		return $this->_send_request( 'charges/'.$charge_id, $params, STRIPE_METHOD_POST );
	}
	
	/**
	 * Refund a charge. - no stripe doc
	 * 
	 * @param  string  The identifier of the charge to refund.
	 * @param  int The amount to refund, defaults to the total amount charged.
	 */
	public function charge_refund( $charge_id, $amount = FALSE ) {
		$amount ? $params = array( 'amount' => $amount ) : $params = array();
		return $this->_send_request( 'charges/'.$charge_id.'/refund', $params, STRIPE_METHOD_POST );
	}

	/**
	 * Capture a charge. https://stripe.com/docs/api#capture_charge
	 * 
	 * @param  string  The identifier of the charge to capture.
	 * @param  array  Options for the charge capture.
	 */
	public function charge_capture( $charge_id, $options = array() ) {
		$params = array();

		$sub_options = array('amount', 'application_fee', 'destination', 'receipt_email', 'statement_descriptor');

		foreach($options as $key => $value){
			if(in_array($key, $sub_options)) $params[$key] = $value;
		}

		return $this->_send_request( 'charges/'.$charge_id.'/capture', $params, STRIPE_METHOD_POST );
	}
	
	/**
	 * List all charges.  https://stripe.com/docs/api#list_charges
	 * 
	 * @param  int  A limit on the number of objects to be returned. Limit can range between 1 and 100, and the default is 10.
	 * @param  string  Only return charges for the customer specified by this customer ID.
	 * @param  array  Other options for the charge: created, ending_before, source, starting_after, transfer_group.
	 */
	public function charge_list( $limit = 10, $customer_id = NULL, $options = array() ) {
		$params['limit'] = $limit;
		if( $customer_id ) $params['customer'] = $customer_id;

		$sub_options = array('created', 'ending_before', 'source', 'starting_after', 'transfer_group');

		foreach($options as $key => $value){
			if(in_array($key, $sub_options)) $params[$key] = $value;
		}

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
	 * Retrieve a subscription.  https://stripe.com/docs/api#retrieve_subscription
	 * 
	 * @param  string   ID of subscription to retrieve.
	 */
	public function subscription_info( $sub_id ) {
		return $this->_send_request( 'subscriptions/'.$sub_id );
	}

	/**
	 * Update a subscription.  https://stripe.com/docs/api#update_subscription
	 * 
	 * @param  string         ID of subscription to be updated.
	 * @param  array  Arguments to change
	 */
	public function subscription_update( $sub_id, $params ) {
		return $this->_send_request( 'subscriptions/'.$sub_id, $params, STRIPE_METHOD_POST );
	}

	/**
	 * List subscriptions
	 * 
	 * @param  array Arguments - https://stripe.com/docs/api#list_subscriptions
	 */
	public function subscription_list( $params = array() ) {
		$url = 'subscriptions';
		if($params){
			$query_string = http_build_query($params);
			$url .=  '?'.$query_string;
		}
		return $this->_send_request( $url );
	}

	/**
	 * Create a subscription item.  https://stripe.com/docs/api#create_subscription_item
	 * 
	 * @param  string        The identifier of the subscription to modify.
	 * @param  string        The identifier of the plan to add to the subscription.
	 * @param  array         Configuration options for the subscription item:metadata, prorate, proration_date and quantity.
	 */
	public function subscription_item_create( $subscription, $plan, $options = array() ) {
		$params = array('subscription' => $subscription, 'plan' => $plan);

		$sub_options = array(
			'metadata', 'prorate', 'proration_date', 'quantity'
		);

		foreach($options as $key => $value){
			if(in_array($key, $sub_options)) $params[$key] = $value;
		}
		
		return $this->_send_request( 'subscription_items', $params, STRIPE_METHOD_POST );
	}

	/**
	 * Retrieve a subscription item.  https://stripe.com/docs/api#retrieve_subscription_item
	 * 
	 * @param  string  The identifier of the subscription item to retrieve.
	 */
	public function subscription_item_info( $item ) {
		return $this->_send_request( 'subscription_items/'.$item );
	}

	/**
	 * Update a subscription item.  https://stripe.com/docs/api#update_subscription_item
	 * 
	 * @param  string  The identifier of the subscription item to modify.
	 * @param  array  Options for the subscription item to update: metadata, plan, prorate, proration_date and quantity.
	 */
	public function subscription_item_update( $item, $options = array() ) {
		$params = array();

		$sub_options = array(
			'metadata', 'plan', 'prorate', 'proration_date', 'quantity'
		);

		foreach($options as $key => $value){
			if(in_array($key, $sub_options)) $params[$key] = $value;
		}

		return $this->_send_request( 'subscription_items/'.$item, $params, STRIPE_METHOD_POST );
	}

	/**
	 * Delete a subscription item.  https://stripe.com/docs/api#delete_subscription_item
	 * 
	 * @param  string  The identifier of the subscription item to delete.
	 */
	public function subscription_item_delete( $item ) {
		return $this->_send_request( 'subscription_items/'.$item, array(), STRIPE_METHOD_DELETE );
	}

	/**
	 *  List all subscription items.  https://stripe.com/docs/api#list_subscription_items
	 * 
	 * @param  string  The ID of the subscription whose items will be retrieved.
	 * @param  int  A limit on the number of objects to be returned. Limit can range between 1 and 100, and the default is 10.
	 * @param string A cursor for use in pagination. An object ID that defines your place in the list.
	 * @param string A cursor for use in pagination. An object ID that defines your place in the list.
	 */
	public function subscription_item_list( $subscription, $limit = 10, $starting_after = NULL, $ending_before = NULL ) {
		$params = array('subscription'=>$subscription, 'limit'=>$limit);
		if($starting_after) $params['starting_after'] = $starting_after;
		if($ending_before) $params['ending_before'] = $ending_before;

		$url = 'subscription_items';
		if($params){
			$query_string = http_build_query($params);
			$url .=  '?'.$query_string;
		}
		return $this->_send_request( $url );
	}

	/**
	 * Create a coupon.  https://stripe.com/docs/api#create_coupon
	 * 
	 * @param  string        Unique string of your choice that will be used to identify this coupon when applying it to a customer. This is often a specific code you’ll give to your customer to use when signing up (e.g., FALL25OFF). If you don’t want to specify a particular code, you can leave the ID blank and we’ll generate a random code for you.
	 * @param  string     Specifies how long the discount will be in effect. Can be forever, once, or repeating.
	 * @param  int           A positive integer representing the amount to subtract from an invoice total (required if percent_off is not passed).
	 * @param  int        Required only if duration is repeating, in which case it must be a positive integer that specifies the number of months the discount will be in effect.
	 * @param  int      A positive integer specifying the number of times the coupon can be redeemed before it’s no longer valid. For example, you might have a 50% off coupon that the first 20 readers of your blog can use.
	 * @param  array    A set of key/value pairs that you can attach to a coupon object. It can be useful for storing additional information about the coupon in a structured format.
	 * @param  int           A positive integer between 1 and 100 that represents the discount the coupon will apply (required if amount_off is not passed).
	 * @param  string    Unix timestamp specifying the last time at which the coupon can be redeemed. After the redeem_by date, the coupon can no longer be applied to new customers.
	 */
	public function coupon_create( $coupon_id = null, $duration, $amount_off = null, $duration_in_months = null, $max_redemptions = null, $metadata = null, $percent_off = null, $redeem_by = null) {
		$params = array(
			'duration' => $duration
		);
		if( $coupon_id ) $params['id'] = $coupon_id;
		if( $amount_off ) {
			$params['amount_off'] = $amount_off;
			$params['currency'] = 'usd';
		} 
		if( $duration_in_months ) $params['duration_in_months'] = $duration_in_months;
		if( $max_redemptions ) $params['max_redemptions'] = $max_redemptions;
		if( $metadata ) $params['metadata'] = $metadata;
		if( !$amount_off && $percent_off ) $params['percent_off'] = $percent_off;
		if( $redeem_by ) $params['redeem_by'] = $redeem_by;
			
		return $this->_send_request( 'coupons', $params, STRIPE_METHOD_POST );
	}

	/**
	 * Retrieve a coupon.  https://stripe.com/docs/api#retrieve_coupon
	 * 
	 * @param  string        The ID of the desired coupon.
	 */
	public function coupon_info( $coupon_id ) {
		return $this->_send_request( 'coupons/'.$coupon_id );
	}

	/**
	 * Update a coupon.  https://stripe.com/docs/api#update_coupon
	 * 
	 * @param  string   The identifier of the coupon to be updated.
	 */
	public function coupon_update( $coupon_id, $metadata = NULL ) {
		$params = array();
		if( $metadata ) $params['metadata'] = $metadata;
		return $this->_send_request( 'coupons/'.$coupon_id, $params, STRIPE_METHOD_POST );
	}

	/**
	 * Delete a coupon.  https://stripe.com/docs/api#delete_coupon
	 * 
	 * @param  string        The identifier of the coupon to be deleted.
	 */
	public function coupon_delete( $coupon_id ) {
		return $this->_send_request( 'coupons/'.$coupon_id, array(), STRIPE_METHOD_DELETE );
	}

	/**
	 * List all coupons.  https://stripe.com/docs/api#list_coupons
	 * 
	 * @param  int   A limit on the number of objects to be returned. Limit can range between 1 and 100, and the default is 10.
	 * @param mixed A filter on the list based on the object created field. The value can be a string with an integer Unix timestamp, or it can be a dictionary .
	 * @param string  A cursor for use in pagination. An object ID that defines your place in the list.
	 * @param string  A cursor for use in pagination. An object ID that defines your place in the list.
	 */
	public function coupon_list( $limit = 10, $created = NULL, $ending_before = NULL, $starting_after = NULL ) {
		$params['limit'] = $limit;
		if( $created ) $params['created'] = $created;
		if( $ending_before ) $params['ending_before'] = $ending_before;
		if( $starting_after ) $params['starting_after'] = $starting_after;
		$vars = http_build_query( $params, NULL, '&' );
		
		return $this->_send_request( 'coupons?'.$vars );
	}

	/**
	 * Delete a customer discount.  https://stripe.com/docs/api#delete_discount
	 * 
	 * @param  string        The customer ID
	 */
	public function customer_discount_delete( $customer_id ) {
		return $this->_send_request( 'customers/'.$customer_id.'/discount', array(), STRIPE_METHOD_DELETE );
	}

	/**
	 * Delete a subscription discount.  https://stripe.com/docs/api#delete_subscription_discount
	 * 
	 * @param  string        The subscription ID
	 */
	public function subscription_discount_delete( $subscription_id ) {
		return $this->_send_request( 'subscriptions/'.$subscription_id.'/discount', array(), STRIPE_METHOD_DELETE );
	}
	
	/**
	 * Create a card token. https://stripe.com/docs/api#create_card_token
	 * 
	 * @param  mixed  The card this token will represent. If you also pass in a customer, the card must be the ID of a card belonging to the customer. Otherwise, if you do not pass in a customer, this is a dictionary containing a user's credit card details, with the options described below.
	 *                       exp_month, exp_year, number, address_city, address_country, address_line1, address_line2, address_state, address_zip, currency, cvc, name
	 * @param  string  The customer (owned by the application's account) for which to create a token.
	 */
	public function card_token_create( $card = NULL, $customer = NULL) {
		$params = array();

		if($card) $params['card'] = $card;
		if($customer) $params['customer'] = $customer;
		
		return $this->_send_request( 'tokens', $params, STRIPE_METHOD_POST );
	}
	
	/**
	 * Retrieve a token. https://stripe.com/docs/api#retrieve_token
	 * 
	 * @param  string        The ID of the desired token.
	 */
	public function card_token_info( $token_id ) {
		return $this->_send_request( 'tokens/'.$token_id );
	}

	/**
	 * Create a card. https://stripe.com/docs/api#create_card
	 * 
	 * @param  string  Customer or recipient on which to create it.
	 * @param  mixed  Either a token, like the ones returned by Stripe.js, or a dictionary containing a user's credit card details.
	 * @param  array A set of key/value pairs that you can attach to a card object. It can be useful for storing additional information about the card in a structured format.
	 */
	public function card_create( $id, $source, $metadata = NULL ) {
		$params = array(
			'source' => $source
		);
		
		if( $metadata ) $params['metadata'] = $metadata;

		return $this->_send_request( 'customers/'.$id.'/sources', $params, STRIPE_METHOD_POST );
	}

	/**
	 * Retrieve a card.  https://stripe.com/docs/api#retrieve_card
	 * 
	 * @param  string  The customer ID.
	 * @param  string  The ID of the card to be retrieved.
	 */
	public function card_info( $customer_id, $card_id ) {
		return $this->_send_request( 'customers/'.$customer_id.'/sources/'.$card_id );
	}

	/**
	 * Update a card.  https://stripe.com/docs/api#update_card
	 * 
	 * @param  string  The customer ID.
	 * @param  string  The ID of the card to be updated.
	 * @param  array  Card details to update: address_city, address_country etc.
	 */
	public function card_update( $customer_id, $card_id, $options = array() ) {
		$params = array();
		
		$sub_options = array(
			'address_city', 'address_country', 'address_line1', 'address_line2', 'address_state', 
			'address_zip', 'exp_month', 'exp_year', 'metadata', 'name'
		);

		foreach($options as $key => $value){
			if(in_array($key, $sub_options)) $params[$key] = $value;
		}

		return $this->_send_request( 'customers/'.$customer_id.'/sources/'.$card_id, $params, STRIPE_METHOD_POST );
	}

	/**
	 * Delete a card.  https://stripe.com/docs/api#delete_card
	 * 
	 * @param  string  The customer ID.
	 * @param  string  The ID of the source to be deleted.
	 */
	public function card_delete( $customer_id, $card_id) {
		return $this->_send_request( 'customers/'.$customer_id.'/sources/'.$card_id, array(), STRIPE_METHOD_DELETE );
	}

	/**
	 * List all cards.  https://stripe.com/docs/api#list_cards
	 * 
	 * @param  string  The ID of the customer whose cards will be retrieved.
	 * @param  int  A limit on the number of objects to be returned. Limit can range between 1 and 100, and the default is 10.
	 * @param string A cursor for use in pagination. An object ID that defines your place in the list.
	 * @param string A cursor for use in pagination. An object ID that defines your place in the list.
	 */
	public function card_list( $customer_id, $limit = 10, $starting_after = NULL, $ending_before = NULL) {
		$params['limit'] = $limit;
		if($ending_before) $params['ending_before'] = $ending_before;
		if($starting_after) $params['starting_after'] = $starting_after;

		$vars = http_build_query( $params, NULL, '&' );
		
		return $this->_send_request( 'customers/'.$customer_id.'/sources?'.$vars );
	}
	
	/**
	 * Create a plan. https://stripe.com/docs/api#create_plan
	 * 
	 * @param  string  An identifier randomly generated by Stripe. Used to identify this plan when subscribing a customer. You can optionally override this ID, but the ID must be unique across all plans in your Stripe account. You can, however, use the same plan ID in both live and test modes.
	 * @param  string  Specifies billing frequency. Either day, week, month or year.
	 * @param  mixed  The product whose pricing the created plan will represent. This can either be the ID of an existing product, or a dictionary containing fields used to create a service product.
	 * @param  int A positive integer in pence (or 0 for a free plan) representing how much to charge on a recurring basis.
	 * @param  int  The number of intervals between subscription billings. For example, interval=month and interval_count=3 bills every 3 months.
	 * @param  array A set of key/value pairs that you can attach to a plan object. It can be useful for storing additional information about the plan in a structured format.
	 * @param string A brief description of the plan, hidden from customers.
	 * @param string  Describes how to compute the price per period. Either per_unit or tiered.
	 * @param array  Each element represents a pricing tier. This parameter requires billing_scheme to be set to tiered. 
	 * @param string Defines if the tiering price should be graduated or volume based.
	 * @param dictionary  Apply a transformation to the reported usage or set quantity before computing the billed price. Cannot be combined with tiers.
	 * @param string  Configures how the quantity per period should be determined, can be either metered or licensed. 
	 */
	public function plan_create( $id = NULL, $interval, $product, $amount = NULL, $interval_count = NULL, $metadata = NULL, $nickname = NULL, $billing_scheme = NULL, $tiers = NULL, $tiers_mode = NULL, $transform_usage = NULL, $usage_type = NULL) {
		$params = array(
			'currency' => 'usd',
			'interval' => $interval,
			'product' => $product
		);
		if($id) $params['id'] = $id;
		if($amount) $params['amount'] = $amount;
		if($interval_count) $params['interval_count'] = $interval_count;
		if($metadata) $params['metadata'] = $metadata;
		if($nickname) $params['nickname'] = $nickname;
		if($billing_scheme) $params['billing_scheme'] = $billing_scheme;
		if($tiers) $params['tiers'] = $tiers;
		if($tiers_mode) $params['tiers_mode'] = $tiers_mode;
		if($transform_usage) $params['transform_usage'] = $transform_usage;
		if($usage_type) $params['usage_type'] = $usage_type;
			
		return $this->_send_request( 'plans', $params, STRIPE_METHOD_POST );
	}
	
	/**
	 * Retrieve a plan. https://stripe.com/docs/api#retrieve_plan
	 * 
	 * @param  string  The ID of the desired plan.
	 */
	public function plan_info( $plan_id ) {
		return $this->_send_request( 'plans/'.$plan_id );
	}

	/**
	 * Update a plan. https://stripe.com/docs/api#update_plan
	 * 
	 * @param  string  The identifier of the plan to be updated.
	 * @param  array  A set of key/value pairs that you can attach to a plan object. It can be useful for storing additional information about the plan in a structured format.
	 * @param  string  A brief description of the plan, hidden from customers. This will be unset if you POST an empty value.
	 * @param  string  The product the plan belongs to. Note that after updating, statement descriptors and line items of the plan in active subscriptions will be affected.
	 */
	public function plan_update( $plan_id, $metadata = NULL, $nickname = NULL, $product = NULL ) {
		$params = array();
		if($metadata) $params['metadata'] = $metadata;
		if($nickname) $params['nickname'] = $nickname;
		if($product) $params['product'] = $product;
		return $this->_send_request( 'plans/'.$plan_id, $params, STRIPE_METHOD_POST );
	}
	
	/**
	 * Delete a plan. https://stripe.com/docs/api#delete_plan
	 * 
	 * @param  string   The identifier of the plan to be deleted.
	 */
	public function plan_delete( $plan_id ) {
		return $this->_send_request( 'plans/'.$plan_id, array(), STRIPE_METHOD_DELETE );
	}
	
	/**
	 * List all plans.  https://stripe.com/docs/api#list_plans
	 *
	 * @param int  A limit on the number of objects to be returned. Limit can range between 1 and 100, and the default is 10.
	 * @param int  Offset that defines your place in the list. 
	 * @param mixed A filter on the list based on the object created field. The value can be a string with an integer Unix timestamp, or it can be a dictionary.
	 * @param string A cursor for use in pagination. An object ID that defines your place in the list.
	 * @param string A cursor for use in pagination. An object ID that defines your place in the list.
	 * @param string  Only return plans for the given product.
	 */
	public function plan_list( $limit = 10, $offset = 0, $created = NULL, $ending_before = NULL, $starting_after = NULL, $product = NULL ) {
		$params['limit'] = $limit;
		$params['offset'] = $offset;
		if($created) $params['created'] = $created;
		if($ending_before) $params['ending_before'] = $ending_before;
		if($starting_after) $params['starting_after'] = $starting_after;
		if($product) $params['product'] = $product;

		$vars = http_build_query( $params, NULL, '&' );
		
		return $this->_send_request( 'plans?'.$vars );
	}

	/**
	 * Create an invoice.  https://stripe.com/docs/api#create_invoice
	 * 
	 * @param  string   The ID of the customer.
	 * @param  array  Options for the invoice: billing, description, metadata, statement_descriptor etc.
	 */
	public function invoice_create( $customer, $options = array() ) {
		$params = array('customer'=>$customer);

		$sub_options = array(
			'application_fee', 'billing', 'days_until_due', 'description', 'due_date', 
			'metadata', 'statement_descriptor', 'subscription', 'tax_percent'
		);

		foreach($options as $key => $value){
			if(in_array($key, $sub_options)) $params[$key] = $value;
		}

		return $this->_send_request( 'invoices', $params, STRIPE_METHOD_POST );
	}
	
	/**
	 * Retrieve an invoice. https://stripe.com/docs/api#retrieve_invoice
	 * 
	 * @param  string   The identifier of the desired invoice.
	 */
	public function invoice_info( $invoice_id ) {
		return $this->_send_request( 'invoices/'.$invoice_id );
	}

	/**
	 * Retrieve an invoice's line items.  https://stripe.com/docs/api#invoice_lines
	 * 
	 * @param  string   The ID of the invoice containing the lines to be retrieved. Use a value of 'upcoming' to retrieve the upcoming invoice.
	 * @param  array  Options for the invoice: coupon, customer, ending_before, starting_after etc.
	 */
	public function invoice_lines_list( $invoice_id, $limit = 10, $options = array() ) {
		$params['limit'] = $limit;
		
		$sub_options = array(
			'coupon', 'customer', 'ending_before', 'starting_after', 'subscription', 'subscription_billing_cycle_anchor', 'subscription_items',
			'subscription_prorate', 'subscription_proration_date', 'subscription_tax_percent', 'subscription_trial_end'
		);

		foreach($options as $key => $value){
			if(in_array($key, $sub_options)) $params[$key] = $value;
		}

		$vars = http_build_query( $params, NULL, '&' );

		return $this->_send_request( 'invoices/'.$invoice_id.'/lines?'.$vars );
	}

	/**
	 * Retrieve an upcoming invoice. https://stripe.com/docs/api#upcoming_invoice
	 * 
	 * @param  string        The identifier of the customer whose upcoming invoice you’d like to retrieve.
	 * @param  array         Configuration options for the upcoming invoice: coupon, subscription, subscription_tax_percent etc.
	 */
	public function customer_upcoming_invoice( $customer_id, $options = array() ) {
		$url = 'invoices/upcoming';
		$params = array('customer'=>$customer_id);

		$sub_options = array(
			'coupon', 'invoice_items', 'subscription', 'subscription_billing_cycle_anchor', 'subscription_items',
			'subscription_prorate', 'subscription_proration_date', 'subscription_tax_percent', 'subscription_trial_end'
		);

		foreach($options as $key => $value){
			if(in_array($key, $sub_options)) $params[$key] = $value;
		}

		$query_string = http_build_query($params);
		$url .=  '?'.$query_string;

		return $this->_send_request( $url );
	}

	/**
	 * Update an invoice.  https://stripe.com/docs/api#update_invoice
	 * 
	 * @param  string   The ID of the invoice to update.
	 * @param  array  Options for the invoice: billing, description, metadata, statement_descriptor etc.
	 */
	public function invoice_update( $invoice_id, $options = array() ) {
		$params = array();

		$sub_options = array(
			'application_fee', 'closed', 'days_until_due', 'description', 'due_date', 'forgiven',
			'metadata', 'paid', 'statement_descriptor', 'tax_percent'
		);

		foreach($options as $key => $value){
			if(in_array($key, $sub_options)) $params[$key] = $value;
		}

		return $this->_send_request( 'invoices/'.$invoice_id, $params, STRIPE_METHOD_POST );
	}

	/**
	 * Pay an invoice.  https://stripe.com/docs/api#pay_invoice
	 * 
	 * @param  string  ID of invoice to pay.
	 * @param  boolean Determines if invoice should be forgiven if source has insufficient funds to fully pay the invoice.
	 * @param  string  A payment source to be charged. The source must be the ID of a source belonging to the customer associated with the invoice being paid.
	 */
	public function invoice_pay( $invoice_id, $forgive = NULL,  $source = NULL) {
		$params = array();

		if( !is_null($forgive) ) $params['forgive'] = $forgive ? 'true':'false';
		if( $source ) $params['source'] = $source;

		return $this->_send_request( 'invoices/'.$invoice_id.'/pay', $params, STRIPE_METHOD_POST );
	}
	
	/**
	 * List all invoices. https://stripe.com/docs/api#list_invoices
	 * 
	 * @param  string   Only return invoices for the customer specified by this customer ID.
	 * @param  int    A limit on the number of objects to be returned. Limit can range between 1 and 100, and the default is 10.
	 * @param  int   Offset to start the list from, default 0.
	 * @param string The billing mode of the invoice to retrieve. Either charge_automatically or send_invoice.
	 * @param mixed A filter on the list based on the object date field. The value can be a string with an integer Unix timestamp, or it can be a dictionary.
	 * @param mixed A filter on the list based on the object due_date field. The value can be a string with an integer Unix timestamp, or it can be a dictionary.
	 * @param string A cursor for use in pagination. An object ID that defines your place in the list.
	 * @param string A cursor for use in pagination. An object ID that defines your place in the list. 
	 * @param string Only return invoices for the subscription specified by this subscription ID.
	 */
	public function invoice_list( $customer_id = NULL, $limit = 10, $offset = 0, $billing = NULL, $date = NULL, $due_date = NULL, $ending_before = NULL, $starting_after = NULL, $subscription = NULL ) {
		$params['limit'] = $limit;
		$params['offset'] = $offset;
		if( $customer_id ) $params['customer'] = $customer_id;
		if( $billing ) $params['billing'] = $billing;
		if( $date ) $params['date'] = $date;
		if( $due_date ) $params['due_date'] = $due_date;
		if( $ending_before ) $params['ending_before'] = $ending_before;
		if( $starting_after ) $params['starting_after'] = $starting_after;
		if( $subscription ) $params['subscription'] = $subscription;

		$vars = http_build_query( $params, NULL, '&' );
		
		return $this->_send_request( 'invoices?'.$vars );
	}
	
	/**
	 * Create an invoice item.  https://stripe.com/docs/api#create_invoiceitem
	 * 
	 * @param  string        The ID of the customer who will be billed when this invoice item is billed.
	 * @param  int           The integer amount in pence of the charge to be applied to the upcoming invoice. If you want to apply a credit to the customer’s account, pass a negative amount.
	 * @param  string        An arbitrary string which you can attach to the invoice item. The description is displayed in the invoice for easy tracking. This will be unset if you POST an empty value.
	 * @param int  Non-negative integer. The quantity of units for the invoice item.
	 * @param int The integer unit amount in pence of the charge to be applied to the upcoming invoice.
	 * @param boolean   Controls whether discounts apply to this invoice item. Defaults to false for prorations or negative invoice items, and true for all other invoice items.
	 * @param string  The ID of an existing invoice to add this invoice item to. 
	 * @param array A set of key/value pairs that you can attach to an invoice item object. It can be useful for storing additional information about the invoice item in a structured format.
	 * @param string The ID of a subscription to add this invoice item to. 
	 */
	public function invoiceitem_create( $customer_id, $amount = NULL, $desc = NULL, $quantity = NULL, $unit_amount = NULL, $discountable = NULL, $invoice = NULL, $metadata = NULL, $subscription = NULL ) {
		$params = array(
			'customer' => $customer_id,
			'currency' => 'usd'
		);

		if( $amount ) $params['amount'] = $amount;
		if( $desc ) $params['description'] = $desc;
		if( $quantity ) $params['quantity'] = $quantity;
		if( $unit_amount ) $params['unit_amount'] = $unit_amount;
		if( $discountable ) $params['discountable'] = $discountable;
		if( $invoice ) $params['invoice'] = $invoice;
		if( $metadata ) $params['metadata'] = $metadata;
		if( $subscription ) $params['subscription'] = $subscription;
		
		return $this->_send_request( 'invoiceitems', $params, STRIPE_METHOD_POST );
	}
	
	/**
	 * Retrieve an invoice item. https://stripe.com/docs/api#retrieve_invoiceitem
	 * 
	 * @param  string  The ID of the desired invoice item.
	 */
	public function invoiceitem_info( $invoiceitem_id ) {
		return $this->_send_request( 'invoiceitems/'.$invoiceitem_id );
	}
	
	/**
	 * Update an invoice item.  https://stripe.com/docs/api#update_invoiceitem
	 * 
	 * @param  string        The ID of the desired invoice item.
	 * @param  int           The integer amount in pence of the charge to be applied to the upcoming invoice. If you want to apply a credit to the customer’s account, pass a negative amount.
	 * @param  string        An arbitrary string which you can attach to the invoice item. The description is displayed in the invoice for easy tracking. This will be unset if you POST an empty value.
	 * @param boolean  Controls whether discounts apply to this invoice item. Defaults to false for prorations or negative invoice items, and true for all other invoice items. Cannot be set to true for prorations.
	 * @param array   A set of key/value pairs that you can attach to an invoice item object. It can be useful for storing additional information about the invoice item in a structured format.
	 * @param int  Non-negative integer. The quantity of units for the invoice item.
	 * @param int  The integer unit amount in pence of the charge to be applied to the upcoming invoice. This unit_amount will be multiplied by the quantity to get the full amount. If you want to apply a credit to the customer’s account, pass a negative unit_amount.
	 */
	public function invoiceitem_update( $invoiceitem_id, $amount = NULL, $desc = NULL, $discountable = NULL, $metadata = NULL, $quantity = NULL, $unit_amount = NULL ) {
		$params = array();
		
		if( $amount ) $params['amount'] = $amount;
		if( $desc ) $params['description'] = $desc;
		if( !is_null($discountable) ) $params['discountable'] = $discountable ? 'true':'false';
		if( $metadata ) $params['metadata'] = $metadata;
		if( $quantity ) $params['quantity'] = $quantity;
		if( $unit_amount ) $params['unit_amount'] = $unit_amount;
		
		return $this->_send_request( 'invoiceitems/'.$invoiceitem_id, $params, STRIPE_METHOD_POST );
	}
	
	/**
	 * Delete an invoice item.  https://stripe.com/docs/api#delete_invoiceitem
	 * 
	 * @param  string  The identifier of the invoice item to be deleted.
	 */
	public function invoiceitem_delete( $invoiceitem_id ) {
		return $this->_send_request( 'invoiceitems/'.$invoiceitem_id, array(), STRIPE_METHOD_DELETE );
	}
	
	/**
	 * List all invoice items.  https://stripe.com/docs/api#list_invoiceitems
	 * 
	 * @param  string        The identifier of the customer whose invoice items to return. If none is provided, all invoice items will be returned.
	 * @param  int           A limit on the number of objects to be returned. Limit can range between 1 and 100, and the default is 10.
	 * @param  int           Offset to start the list from, default 0
	 * @param mixed A filter on the list based on the object created field. The value can be a string with an integer Unix timestamp, or it can be a dictionary .
	 * @param string  A cursor for use in pagination. An object ID that defines your place in the list.
	 * @param string  A cursor for use in pagination. An object ID that defines your place in the list.
	 * @param string Only return invoice items belonging to this invoice. If none is provided, all invoice items will be returned. If specifying an invoice, no customer identifier is needed.
	 */
	public function invoiceitem_list( $customer_id = FALSE, $limit = 10, $offset = 0, $created = NULL, $ending_before = NULL, $starting_after = NULL, $invoice = NULL ) {
		$params['limit'] = $limit;
		$params['offset'] = $offset;
		if( $customer_id ) $params['customer'] = $customer_id;
		if( $created ) $params['created'] = $created;
		if( $ending_before ) $params['ending_before'] = $ending_before;
		if( $starting_after ) $params['starting_after'] = $starting_after;
		if( $invoice ) $params['invoice'] = $invoice;
		$vars = http_build_query( $params, NULL, '&' );
		
		return $this->_send_request( 'invoiceitems?'.$vars );
	}

	/**
	 * Create a service product.  https://stripe.com/docs/api#create_service_product
	 * 
	 * @param  string        An identifier will be randomly generated by Stripe. You can optionally override this ID, but the ID must be unique across all products in your Stripe account.
	 * @param  string        The product’s name, meant to be displayable to the customer.
	 * @param  array         A list of up to 5 alphanumeric attributes.
	 * @param  array         A set of key/value pairs that you can attach to a product object. It can be useful for storing additional information about the product in a structured format.
	 * @param  string        An arbitrary string to be displayed on your customer’s credit card statement. This may be up to 22 characters. The statement description may not include <>”’ characters, and will appear on your customer’s statement in capital letters. Non-ASCII characters are automatically stripped. While most banks display this information consistently, some may display it incorrectly or not at all.
	 * @param  string        A label that represents units of this product, such as seat(s), in Stripe and on customers’ receipts and invoices. This may be up to 12 characters.
	 */
	public function service_product_create( $id = null, $name, $attributes = array(), $metadata = array(), $statement_descriptor = null, $unit_label = null) {
		$params = array(
			'name' => $name,
			'type' => 'service'
		);
		if( $id ) $params['id'] = $id;
		if( $attributes ) $params['attributes'] = $attributes;
		if( $metadata ) $params['metadata'] = $metadata;
		if( $statement_descriptor ) $params['statement_descriptor'] = $statement_descriptor;
		if( $unit_label ) $params['unit_label'] = $unit_label;
			
		return $this->_send_request( 'products', $params, STRIPE_METHOD_POST );
	}

	/**
	 * Create a product.  https://stripe.com/docs/api#create_product
	 * 
	 * @param  string        An identifier will be randomly generated by Stripe. You can optionally override this ID, but the ID must be unique across all products in your Stripe account.
	 * @param  string        The product’s name, meant to be displayable to the customer.
	 * @param  string    The product’s description, meant to be displayable to the customer.
	 * @param  string        A short one-line description of the product, meant to be displayable to the customer. 
	 * @param  boolean        Whether the product is currently available for purchase. Defaults to true.
	 * @param  boolean   Whether this product is shipped (i.e., physical goods). Defaults to true.
	 * @param  array         A list of up to 5 alphanumeric attributes.
	 * @param  array         A set of key/value pairs that you can attach to a product object. It can be useful for storing additional information about the product in a structured format.
	 * @param  dictionary  The dimensions of this product for shipping purposes. A SKU associated with this product can override this value by having its own package_dimensions.
	 * @param  array  A list of up to 8 URLs of images for this product, meant to be displayable to the customer. 
	 * @param  string  A URL of a publicly-accessible webpage for this product. 
	 * @param  array   An array of Connect application names or identifiers that should not be able to order the SKUs for this product.
	 */
	public function product_create( $id = null, $name, $description = NULL, $caption = null, $active = null, $shippable = NULL, $attributes = array(), $metadata = array(), $package_dimensions = NULL, $images = NULL, $url = NULL, $deactivate_on = NULL) {
		$params = array(
			'name' => $name,
			'type' => 'good'
		);
		if( $id ) $params['id'] = $id;
		if( $description ) $params['description'] = $description;
		if( $caption ) $params['caption'] = $caption;
		if( !is_null($active) ) $params['active'] = $active ? 'true':'false';
		if( !is_null($shippable) ) $params['shippable'] = $shippable ? 'true':'false';
		if( $attributes ) $params['attributes'] = $attributes;
		if( $metadata ) $params['metadata'] = $metadata;
		if( $package_dimensions ) $params['package_dimensions'] = $package_dimensions;
		if( $images ) $params['images'] = $images;
		if( $url ) $params['url'] = $url;
		if( $deactivate_on ) $params['deactivate_on'] = $deactivate_on;
			
		return $this->_send_request( 'products', $params, STRIPE_METHOD_POST );
	}

	/**
	 * Retrieve a product.  https://stripe.com/docs/api#retrieve_service_product, https://stripe.com/docs/api#retrieve_product
	 * 
	 * @param  string  The identifier of the product to be retrieved.
	 */
	public function product_info( $product_id ) {
		return $this->_send_request( 'products/'.$product_id );
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
