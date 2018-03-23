<?php

return [
    /**
     * Name of the tier that serves as a fallback if no tier is found
     */
    'fallback_tier_name' => 'D',

    'geoip_database_path' => __DIR__.'/geoip',
	/**
	 * What are the names of the id columns on your system?
	 *
	 * 'id' is the most common, but if you have one or more different,
	 * please add them here in your preference order.
	 */
	'id_columns_names' => [
		'id'
	],

	/**
	 * Do you wish to log the user agent?
	 */
	'log_user_agents' => false,

	/**
	 * Do you wish to log your users?
	 */
	'log_users' => false,

	/**
	 * Do you wish to log devices?
	 */
	'log_devices' => false,

	/**
	 * A cookie may be created on your visitor device, so you can have information
	 * on everything made using that device on your site.	 *
	 */
	'store_cookie_tracker' => false,

	/**
	 * If you are storing cookies, you better change it to a name you of your own.
	 */
	'tracker_cookie_name' => 'please_change_this_cookie_name',

	/**
	 * Internal tracker session name.
	 */
    'tracker_session_name' => 'tracker_session',

	/**
	 * ** IMPORTANT **
	 *   Change the user model to your own.
	 */
	'user_model' => 'PragmaRX\Tracker\Vendor\Laravel\Models\User',

	/**
	 * You can use your own model for every single table Tracker has.
	 */

    'session_model' => 'PragmaRX\Tracker\Vendor\Laravel\Models\Session',

    'log_model' => 'PragmaRX\Tracker\Vendor\Laravel\Models\Log',

    'agent_model' => 'PragmaRX\Tracker\Vendor\Laravel\Models\Agent',

    'device_model' => 'PragmaRX\Tracker\Vendor\Laravel\Models\Device',

    'cookie_model' => 'PragmaRX\Tracker\Vendor\Laravel\Models\Cookie',

    'domain_model' => 'PragmaRX\Tracker\Vendor\Laravel\Models\Domain',

    'referer_model' => 'PragmaRX\Tracker\Vendor\Laravel\Models\Referer',

    'referer_search_term_model' => 'PragmaRX\Tracker\Vendor\Laravel\Models\RefererSearchTerm',

    'geoip_model' => 'PragmaRX\Tracker\Vendor\Laravel\Models\GeoIp',

	'balance_model' => 'PragmaRX\Tracker\Vendor\Laravel\Models\Balance',

	'stats_model' => 'PragmaRX\Tracker\Vendor\Laravel\Models\Stats',

	'tier_model' => 'PragmaRX\Tracker\Vendor\Laravel\Models\Tier',

    'image_model' => 'PragmaRX\Tracker\Vendor\Laravel\Models\Image'
];
