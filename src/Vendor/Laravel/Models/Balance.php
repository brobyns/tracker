<?php
/**
 * Created by PhpStorm.
 * User: bramr
 * Date: 12/09/2016
 * Time: 18:24
 */

namespace PragmaRX\Tracker\Vendor\Laravel\Models;


class Balance extends Base
{
    protected $table = 'balances';

    protected $fillable = array(
        'user_id',
        'amount'
    );

    public function user(){
        return $this->belongsTo(User::class);
    }

}