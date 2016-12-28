<?php
/**
 * Created by PhpStorm.
 * User: bramr
 * Date: 9/08/2016
 * Time: 18:35
 */

namespace PragmaRX\Tracker\Vendor\Laravel\Models;

class Tier extends Base
{
    protected $table = 'tiers';

    protected $fillable = array(
        'name',
        'rate',
    );
}