<?php
/**
 * Created by PhpStorm.
 * User: bramr
 * Date: 9/08/2016
 * Time: 18:35
 */

namespace PragmaRX\Tracker\Vendor\Laravel\Models;


class Earnings extends Base
{
    protected $table = 'earnings';

    protected $fillable = array(
        'user_id',
        'amount',
        'views',
        'date',
    );

    public function user(){
        return $this->belongsTo(User::class);
    }
}