<?php

namespace PragmaRX\Tracker\Data\Repositories;

class Path extends Repository {

    public function getPath($pathid) {
        return $this->find($pathid);
    }
}
