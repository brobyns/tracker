<?php
/**
 * Created by PhpStorm.
 * User: bramr
 * Date: 26/12/2016
 * Time: 15:21
 */

namespace PragmaRX\Tracker\Data\Repositories;


use Carbon\Carbon;

class Stats extends Repository {

    public function __construct($model)
    {
        parent::__construct($model);
    }

    public function statsForUser($userId) {
        return $this->getModel()->statsForUser($userId);
    }

    public function updateStatsForImage($imageId, $tierId, $amount)
    {
        $stats = $this->newQuery()
            ->where('image_id', $imageId)
            ->where('tier_id', $tierId)
            ->where('date', Carbon::today())->first();

        if ($stats) {
            $stats->views++;
            $stats->amount += $amount;
        } else {
            $stats = $this->newModel();
            $stats->image_id = $imageId;
            $stats->tier_id = $tierId;
            $stats->views = 1;
            $stats->amount = $amount;
            $stats->date = Carbon::today();
        }
        $stats->save();
    }
}