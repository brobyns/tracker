<?php

namespace PragmaRX\Tracker\Data\Repositories;

class GeoIp extends Repository {

    public function getRateForGeoipId($geoipId) {
        return $this->getModel()->getRateForGeoipId($geoipId);
    }
}
