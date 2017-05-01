<?php
/**
 * Created by PhpStorm.
 * User: bramr
 * Date: 9/08/2016
 * Time: 18:35
 */

namespace PragmaRX\Tracker\Vendor\Laravel\Models;


use App\Image;
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

    public function statsForUser($userId) {
        $query = $this->newQuery()
            ->join('images', 'images.id', '=', 'stats.image_id')
            ->join('users', function($join) use ($userId) {
                $join->on('users.id', '=', 'images.user_id')
                    ->where('user_id', $userId);
            })
            ->rightjoin('calendar', function($join) {
                $join->on('stats.date', '=', 'calendar.date');
            })
            ->select($this->getConnection()->raw('calendar.date, tier_id as tier,
					SUM(stats.views) as views, SUM(stats.amount) / 100000 as earnings'))
            ->groupBy('calendar.date')
            ->groupBy('tier')
            ->where('calendar.date', '>=', Carbon::now()->startOfDay()->subDays(10))
            ->where('calendar.date', '<=', Carbon::now()->endOfDay());

        $result = $query->get();

        $totalViewsA = $result->where('tier', 1)->sum('views');
        $totalViewsB = $result->where('tier', 2)->sum('views');
        $totalViewsC = $result->where('tier', 3)->sum('views');
        $totalViewsD = $result->where('tier', 4)->sum('views');

        $totalEarningsA = $result->where('tier', 1)->sum('earnings');
        $totalEarningsB =  $result->where('tier', 2)->sum('earnings');
        $totalEarningsC = $result->where('tier', 3)->sum('earnings');
        $totalEarningsD = $result->where('tier', 4)->sum('earnings');
        $totalEarnings = $result->sum('earnings');

        $grouped = $result->groupBy('date');
        return array(
            'statsPerDay' => $grouped,
            'totals' => [ 'views' => array($totalViewsA, $totalViewsB, $totalViewsC, $totalViewsD),
                'earnings' => array($totalEarningsA, $totalEarningsB, $totalEarningsC, $totalEarningsD, $totalEarnings)]);
    }
}