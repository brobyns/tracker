<?php

namespace PragmaRX\Tracker\Data\Repositories;

class Path extends Repository {

    public function getUserIdForPath($pathid) {
        $path = $this->find($pathid);
        return $path->user_id;
    }
}
