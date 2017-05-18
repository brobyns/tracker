<?php

namespace PragmaRX\Tracker\Data\Repositories;


class Image extends Repository {

    public function __construct($model)
    {
        parent::__construct($model);
    }

    public function getImageIdAndUserId($filename) {
        return $this->getModel()
            ->where('file_name', $filename)
            ->select('id', 'user_id')->first();
    }
}