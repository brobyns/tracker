<?php

namespace PragmaRX\Tracker\Data\Repositories;


class Image extends Repository {

    public function __construct($model)
    {
        parent::__construct($model);
    }

    public function getImageIdAndUserId($uuid) {
        return $this->getModel()
            ->where('uuid', $uuid)
            ->select('id', 'user_id')
            ->first();
    }

    public function updateImageViewsAndEarnings($imageId, $amount) {
        $image = $this->newQuery()->find($imageId);
        if ($image) {
            $image->views++;
            $image->amount += $amount;
        }
        $image->save();
    }
}