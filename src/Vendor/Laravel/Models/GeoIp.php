<?php

namespace PragmaRX\Tracker\Vendor\Laravel\Models;

class GeoIp extends Base {

	protected $table = 'tracker_geoip';

	protected $fillable = array(
		'country_code',
		'country_code3',
		'country_name',
		'region',
		'city',
		'postal_code',
		'latitude',
		'longitude',
		'area_code',
		'dma_code',
		'metro_code',
		'continent_code',
	);

	public function getRateForGeoipId($geoipId) {
		$query = $this
			->join('countries', 'countries.country_code', '=', 'tracker_geoip.country_code')
			->join('tiers', 'tiers.id', '=', 'countries.tier_id')
			->where('tracker_geoip.id', '=', $geoipId)
			->select('tiers.rate');
		return $query->first()->rate;
	}

}
