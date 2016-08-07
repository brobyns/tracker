<?php

namespace PragmaRX\Tracker\Data\Repositories;

use PragmaRX\Support\Config;

class Route extends Repository {

	public function __construct($model, Config $config)
	{
		parent::__construct($model);

		$this->config = $config;
	}

	public function isTrackable($route)
	{
		$allowed = $this->config->get('track_routes');

		return
			$allowed ||
			! $route->currentRouteName() ||
			in_array_wildcard($route->currentRouteName(), $allowed);
	}

}
