<?php

namespace PragmaRX\Tracker\Data\Repositories;


class Balance extends Repository {

    public function __construct($model)
    {
        parent::__construct($model);
    }

    public function updateBalanceForUser($userid, $amount)
    {
        $balance = $this->newQuery()->where('user_id', $userid)->first();

        if ($balance) {
            $balance->amount += $amount;
        } else {
            $balance = new \App\Balance();
            $balance->amount = $amount;
            $balance->user_id = $userid;
        }
        $balance->save();
    }
}