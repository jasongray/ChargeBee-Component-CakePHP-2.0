<?php
/**
 * ChargeBee component
 *
 * Component to access/post information to ChargeBee API
 *
 * Copyright (c) Webwidget Pty Ltd. (http://webwidget.com.au)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Webwidget Pty Ltd. (http://webwidget.com.au)
 * @link          http://webwidget.com.au 
 * @package       App.Controller.Component
 * @since         1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
App::uses('Component', 'Controller');

/**
 *   ChargeBee Component
 *
 *   @package        App.Controller.Component
 */ 
class ChargeBeeComponent extends Component {

/**
 * authentication settings for the ChargeBee API
 *
 * @var array
 */
	public $settings = array(
		'live' => array(
			'sitekey' => 'your-test-api-sitekey',
			'apikey' => 'yourapikeygeneratedbychargebee',
			'gateway' => 'your-gateway-of-choice',
		),
		'test' => array(
			'sitekey' => 'your-test-api-sitekey',
			'apikey' => 'yourapikeygeneratedbychargebee',
			'gateway' => 'your-gateway-of-choice',
		),
	);

/**
 * Site name variable for ChargeBee API
 *
 */
	protected $sitekey;

/**
 * API key variable for ChargeBee API
 *
 */
	protected $apikey;

/**
 * Gateway for payments
 *
 */
	protected $gateway;

/**
 * Generic environment key
 *
 */
	protected $env;

/**
 * constructor
 *
 * @param ComponentCollection $collection A ComponentCollection this component can use to lazy load its components
 * @param array $settings Array of configuration settings for this component
 */
	public function __construct (ComponentCollection $collection, $settings = array()) {
		$settings = array_merge($this->settings, (array)$settings);
		if (Configure::read('debug') == 0) {
			$keyname = 'live';
		} else {
			$keyname = 'test';
		}
		$this->sitekey = $settings[$keyname]['sitekey'];
		$this->apikey = $settings[$keyname]['apikey'];
		$this->gateway = $settings[$keyname]['gateway'];
		parent::__construct($collection, $settings);
	}

/**
 * initialise the component and load the vendor class files
 *
 */
	public function init () {
		App::import('Vendor', 'ChargeBee', array('file' => 'Chargebee/ChargeBee.php'));
        $this->env = ChargeBee_Environment::configure($this->sitekey, $this->apikey);
	}


/***************************************************
 *   PLANS
 *
 ***************************************************/

/**
 * List current plans
 *
 * @param $params Array of parameters
 * @return mixed Array on success, false on error
 */
	public function getPlans($params = array()) {
		if (!$this->env) {
			$this->init();
		}
		if (empty($params)) {
			$params = array(
				'limit' => 10,
				'status[is]' => 'active',
			);
		}
		$plans = ChargeBee_Plan::all($params);
		if (!$plans) return false;
		$out = array();
		foreach ($plans as $p) {
			$out[] = $this->_returnPlans($p);
		}
		return $out;
	}

/**
 * Retrieve a plan
 *
 * @param $id The id of the plan to retrieve
 * @return mixed Array on success, false on error
 */
	public function getPlan($pid = false) {
		if (empty($pid)) return false;
		if (!$this->env) {
			$this->init();
		}
		$plan = ChargeBee_Plan::retrieve($pid);
		if (!$plan) return false;
		$out = $this->_returnPlans($plan);
		return $out;
	}

/**
 * Create a plan
 *
 * @param $params Array of plan data to save
 * @return mixed Array on success, false on error
 */
	public function createPlan($params = array()) {
		if (!$this->env) {
			$this->init();
		}
		$plan = ChargeBee_Plan::create($params);
		if (!$plan) return false;
		$out = $this->_returnPlans($plan);
		return $out['Plan']['id'];
	}

/**
 * Delete a plan
 * If a plan already has invoices/subscriptions allocated it will be
 * moved to an "archived" state.
 *
 * @param $id The id of the plan to delete
 * @return mixed Array on success, false on error
 */
	public function deletePlan($params = array()) {
		if (!$this->env) {
			$this->init();
		}
		$plan = ChargeBee_Plan::delete($params);
		if (!$plan) return false;
		$out = $this->_returnPlans($plan);
		return $out['Plan']['id'];
	}

/**
 * Retrieve a plan from a customer subscription
 *
 * @param $sid The subscription id of the customer
 * @return mixed Array on success, false on error
 */
	public function getCustomerPlan($sid = false) {
		if (empty($sid)) return false;
		if (!$this->env) {
			$this->init();
		}
		$subs = $this->getSubscription($sid);
		$plan = ChargeBee_Plan::retrieve($subs['Subscription']['plan_id']);
		if (!$plan) return false;
		$out = $this->_returnPlans($plan);
		return $out;
	}

/**
 * Returns values for ChargeBee objects into more CakePHP array styles
 *
 * @param $data The array of data
 * @param $key The key object to return. If none specified then the whole array
 * @return $out The requested returned data or array of data
 */
	private function _returnPlans($data = array(), $key = false) {
		if (empty($data)) return false;
		$out = array();
		$out['Plan'] = $data->plan()->getValues();
		return $out;
	}


/***************************************************
 *   SUBSCRIPTIONS
 *
 ***************************************************/

/**
 * Retreive a subscription
 *
 * @param $id The subscription id
 * @return $out Array of values
 */
	public function getSubscription($sid = false) {
		if (empty($sid)) return false;
		if (!$this->env) {
			$this->init();
		}
		$out = array();
		$result = ChargeBee_Subscription::retrieve($sid);
		return $this->_returnSubscriptions($result);
	}

/**
 * Create a subscription
 *
 * @param $pid The Plan ID 
 * @param $data The array of customer data
 * @return mixed Subscription ID on success
 */
	public function createSubscription($pid = false, $data = array()) {
		if (empty($pid)) return false;
		if (empty($data)) return false;
		if (!$this->env) {
			$this->init();
		}
		try {
			$result = ChargeBee_Subscription::create(
				array(
					'planId' => $pid,
					'customer' => array(
						'email' => $data['User']['email'],
						'firstName' => $data['User']['firstname'],
						'lastName' => $data['User']['lastname'],
						'company' => $data['User']['name'],
						'taxability' => 'taxable',
						'phone' => $data['User']['phone'],
					),
					'card' => array(
						'gateway' => $this->gateway,
						'number' => $data['CC']['number'],
						'expiryMonth' => $data['CC']['exmonth'],
						'expiryYear' => $data['CC']['exyear'],
						'cvv' => $data['CC']['ccv'],
					),
				)
			);
		} catch (Exception $e) {
			return $e->getJsonObject();
		}
		if (empty($result)) return array('http_status_code' => 400, 'message' => __('Unexplained error has occurred creating your subscription. Please try again later.'));
		$out = $this->_returnSubscriptions($result);
		return $out['Subscription']['id'];
	}

/**
 * Up/Down grade a plan on a subscription
 *
 * @param $sid The unique subscription ID
 * @param $pid The new plan ID
 * @return bool
 */
	public function upgradeSubscription($sid = false, $pid = false) {
		if (empty($pid)) return false;
		if (empty($sid)) return false;
		if (!$this->env) {
			$this->init();
		}
		$result = ChargeBee_Subscription::update($sid, 
			array(
				'planId' => $pid,
			)
		);
		if (empty($result)) return false;
		return $this->_returnSubscriptions($result);
	}

/**
 * Reactivate a cancelled subscription
 *
 * @param $sid The unique subscription ID
 * @return bool
 */
	public function reactivateSubscription($sid = false) {
		if (empty($sid)) return false;
		if (!$this->env) {
			$this->init();
		}
		$result = ChargeBee_Subscription::reactivate($sid);
		if (empty($result)) return false;
		return $this->_returnSubscriptions($result);
	}

/**
 * Cancel a subscription
 *
 * @param $sid The unique subscription ID
 * @return bool
 */
	public function cancelSubscription($sid = false) {
		if (empty($sid)) return false;
		if (!$this->env) {
			$this->init();
		}
		$result = ChargeBee_Subscription::cancel($sid);
		if (empty($result)) return false;
		return $this->_returnSubscriptions($result);
	}

/**
 * Check a subscription is active
 *
 * @param $sid The unique subscription ID
 * @return bool
 */
	public function checkSubscription($sid = false) {
		if (empty($sid)) return false;
		if (!$this->env) {
			$this->init();
		}
		$result = $this->getSubscription($sid);
		if ($result['Subscription']['status'] === 'cancelled') {
			return false;
		}
		return true;
	}

/**
 * Returns values for ChargeBee objects into more CakePHP array styles
 *
 * @param $data The array of data
 * @param $key The key object to return. If none specified then the whole array
 * @return $out The requested returned data or array of data
 */
	private function _returnSubscriptions($data = array(), $key = false) {
		if (empty($data)) return false;
		$out = array('Subscription' => array(), 'Customer' => array(), 'Card' => array());
		$out['Subscription'] = $data->subscription()->getValues();
		if (isset($out['Subscription']['customer_id']) && !empty($out['Subscription']['customer_id'])) {
			$out['Customer'] = $data->customer()->getValues();
		}
		if (isset($out['Customer']['payment_method']['type']) && $out['Customer']['payment_method']['type'] == 'card') {
			$out['Card'] = $data->card()->getValues();
		}
		return $out;
	}


/***************************************************
 *   CARDS
 *
 ***************************************************/

/**
 * Update a credit card for a customer
 *
 * @param $cid The customer Id
 * @param $data Array of new credit card details
 * @return bool
 */
	public function updateCreditCard($cid = false, $data = array()) {
		if (empty($cid)) return false;
		if (empty($data)) return false;
		if (!$this->env) {
			$this->init();
		}
		try {
			$result = ChargeBee_Card::updateCardForCustomer($cid, array(
				'gateway' => $this->gateway,
				'number' => $data['CC']['number'],
				'expiryMonth' => $data['CC']['exmonth'],
				'expiryYear' => $data['CC']['exyear'],
				'cvv' => $data['CC']['ccv'],
				)
			);
		} catch (Exception $e) {
			return $e->getJsonObject();
		}
		$out['Customer'] = $result->customer()->getValues();
		if (isset($out['Customer']['payment_method']['type']) && $out['Customer']['payment_method']['type'] == 'card') {
			$out['Card'] = $data->card()->getValues();
		} else {
			return array('http_status_code' => 400, 'message' => __('Unexplained error has occurred updating your credit card. Please try again later.'));
		}
		if ($out['Card']['status'] === 'expired') {
			return array('http_status_code' => 400, 'message' => __('Your credit card has expired. Please enter another card.'));
		}
		return true;
	}

/**
 * Check a credit card for a customer
 *
 * @param $cid The customer Id
 * @return bool
 */
	public function checkCreditCard($cid = false) {
		if (empty($cid)) return false;
		if (!$this->env) {
			$this->init();
		}
		$result = ChargeBee_Card::retrieve($cid);
		$out['Card'] = $result->card()->getValues();
		if ($out['Card']['status'] === 'expired') {
			return false;
		}
		return true;
	}


}