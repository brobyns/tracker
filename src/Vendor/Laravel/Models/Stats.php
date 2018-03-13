<?php

namespace PragmaRX\Tracker\Vendor\Laravel\Models;

use Carbon\Carbon;

class Stats extends Base
{
    protected $table = 'stats';

    protected $fillable = array(
        'image_id',
        'user_id',
        'tier_id',
        'amount',
        'views',
        'date',
    );

    public function image(){
        return $this->belongsTo(Image::class);
    }

    public function tier(){
        return $this->belongsTo(Tier::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function getAmountAttribute($value)
    {
        return $value / 100000;
    }

    public function statsForUser($userId)
    {
        return $this->newQuery()
            ->join('images', 'images.id', '=', 'stats.image_id')
            ->join('users', function ($join) use ($userId) {
                $join->on('users.id', '=', 'images.user_id')
                    ->where('user_id', $userId);
            })
            ->rightjoin('calendar', function ($join) {
                $join->on('stats.date', '=', 'calendar.date');
            })
            ->select($this->getConnection()->raw('calendar.date, tier_id as tier,
					SUM(stats.views) as views, SUM(stats.earnings) / 100000 as earnings'))
            ->groupBy('calendar.date')
            ->groupBy('tier')
            ->where('calendar.date', '>=', Carbon::now()->startOfDay()->subDays(10))
            ->where('calendar.date', '<=', Carbon::now()->endOfDay())
            ->get();
    }
}