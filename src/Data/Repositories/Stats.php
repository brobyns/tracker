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

    public function statsForUser($userId, $startDate, $endDate) {
        return $this->getModel()->statsForUser($userId, $startDate, $endDate);
    }

    public function updateStatsForImage($imageId, $userId, $tierId, $amount)
    {
        $stats = $this->newQuery()
            ->where('image_id', $imageId)
            ->where('user_id', $userId)
            ->where('tier_id', $tierId)
            ->where('date', Carbon::today())->first();

        if ($stats) {
            $stats->views++;
            $stats->earnings += $amount;
        } else {
            $stats = $this->newModel();
            $stats->image_id = $imageId;
            $stats->user_id = $userId;
            $stats->tier_id = $tierId;
            $stats->views = 1;
            $stats->earnings = $amount;
            $stats->date = Carbon::today();
        }
        $stats->save();
    }
}