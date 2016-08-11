<?php

namespace PragmaRX\Tracker\Data\Repositories;

use Carbon\Carbon;

class Earnings extends Repository {

    public function __construct($model)
    {
        parent::__construct($model);
    }

    public function updateEarningsForUser($userid, $amount)
    {
        $earnings = $this->newQuery()->where('user_id', $userid)
            ->where('date', Carbon::today())->first();

        if ($earnings) {
            $earnings->views++;
            $earnings->amount += $amount;
        } else {
            $earnings = $this->newModel();
            $earnings->user_id = $userid;
            $earnings->views = 1;
            $earnings->amount = $amount;
            $earnings->date = Carbon::today();
        }
        $earnings->save();
    }
}