<?php

namespace PragmaRX\Tracker\Vendor\Laravel\Models;

class Image extends Base
{
    protected $table = 'images';

    public function user(){
        return $this->belongsTo(User::class);
    }
}