ChargeBee-Component-CakePHP-2.0
===

Functional component to use ChargeBee subscription service with your CakePHP application. 

Presently this component has the following functions.
- Creating, getting, deleting ChargeBee plans
- Creating, getting, upgrading, reactivating & cancelling ChargeBee subscriptions.
- Checking and updating credit card details.

More functions are planned and are on the way. Feel free to add your own or send your requests this way!

* [Installation](#installation)
* [Setup](#setup)
* [Usage](#usage)

---

Installation
---
1. Install the Chargebee PHP libraries from https://apidocs.chargebee.com/docs/api?lang=php#versions

2. Download this [file](./Controller/Components/ChargeBeeComponent.php) and copy to the Controller/Component folder of your CakePHP application.

---
Setup
---
1. Update the file variable in the init function, ensuring the file variable points to the relative path of your ChargeBee vendors files inside the Vendor directory.
```php
/**
 * initialise the component and load the vendor class files
 *
 */
	public function init () {
		App::import('Vendor', 'ChargeBee', array('file' => 'Chargebee/ChargeBee.php'));
        	$this->env = ChargeBee_Environment::configure($this->sitekey, $this->apikey);
	}
```

2. Register/Login to ChargeBee and add your site API settings. By default you will have a "test" API, enter these details in the settings array of the component file.
```php
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
 ```
 
 ---
 Usage
 ---
 1. Load the component as you see fit. Read [how to load components at CakePHP](https://book.cakephp.org/2.0/en/controllers/components.html#using-components).
 - In the AppController (no settings)
 ```php
 public $components = array('ChargeBee');
```
 - or with settings...
```php
public $components = array(
    'ChargeBee' => array(
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
    ),
);
```
- or in the Controller using the same parameters above.
- You can also load the controller on the fly in the action. Read [how to load components on the fly here](https://book.cakephp.org/2.0/en/controllers/components.html#loading-components-on-the-fly).

2. getPlans function may require some tweaking at your end. The get plans function contains some filtering which you can alter to suit your needs. See [retreiving plans at ChargeBee API](https://apidocs.chargebee.com/docs/api/plans#list_plans).

3. createSubsction function may require some tweaking depending on your data arrays being sent. 

4. Most functions will return some form of data, whether it be a single integar or array of data. Most functions will return false on empty data or errors.

