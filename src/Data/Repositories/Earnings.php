<?php

namespace PragmaRX\Tracker\Data\Repositories;

use Carbon\Carbon;

class Earnings extends Repository {

    public function __construct($model)
    {
        parent::__construct($model);
    }

    public function updateEarningsForUser($userId, $tierId, $amount)
    {
        $earnings = $this->newQuery()
            ->where('user_id', $userId)
            ->where('tier_id', $tierId)
            ->where('date', Carbon::today())->first();

        if ($earnings) {
            $earnings->views++;
            $earnings->amount += $amount;
        } else {
            $earnings = $this->newModel();
            $earnings->user_id = $userId;
            $earnings->tier_id = $tierId;
            $earnings->views = 1;
            $earnings->amount = $amount;
            $earnings->date = Carbon::today();
        }
        $earnings->save();
    }
}