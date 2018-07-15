<?php
/**
 * Created by PhpStorm.
 * User: bramr
 * Date: 26/12/2016
 * Time: 15:21
 */

namespace PragmaRX\Tracker\Data\Repositories;


class Tier extends Repository {

    public function __construct($model)
    {
        parent::__construct($model);
    }

    public function getTier($geoipId)
    {
        return $this->newQuery()
            ->join('countries', 'tiers.id', '=', 'countries.tier_id')
            ->join('tracker_geoip', 'countries.country_code', '=', 'tracker_geoip.country_code')
            ->where('tracker_geoip.id', '=', $geoipId)
            ->select('tiers.*')
            ->first();
    }

    public function getTierByName($name)
    {
        return $this->model()
            ->where('name', $name)
            ->first();
    }
}