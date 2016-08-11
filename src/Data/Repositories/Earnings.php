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

        $views = ($earnings)? $earnings->getAttribute('views') + 1 : 1;
        $amount = ($earnings)? $earnings->getAttribute('amount') + $amount : $amount;

        $data = array(
            'user_id' => $userid,
            'amount' => $amount,
            'views' => $views,
            'date' => Carbon::today()
        );


        foreach($earnings->getAttributes() as $name => $value)
        {
            if (isset($data[$name]) && $name !== 'id')
            {
                $earnings->{$name} = $data[$name];
            }
        }

        $earnings->save();
    }
}