<?php

namespace PragmaRX\Tracker\Data\Repositories;

class GeoIpRepository extends Repository {

    public function getRateForGeoipId($geoipId) {
        return $this->getModel()->getRateForGeoipId($geoipId);
    }
}
