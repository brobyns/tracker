<?php

namespace PragmaRX\Tracker\Vendor\Laravel\Models;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Log extends Base
{

    protected $table = 'tracker_log';

    protected $fillable = array(
        'session_id',
        'method',
        'user_id',
        'image_id',
        'referer_id',
        'geoip_id',
        'client_ip',
        'is_real',
        'is_adblock',
        'is_proxy',
        'is_confirmed',
        'is_unique',
    );

    public function session()
    {
        return $this->belongsTo($this->getConfig()->get('session_model'));
    }

    public function user()
    {
        return $this->belongsTo($this->getConfig()->get('user_model'));
    }

    public function geoip()
    {
        return $this->belongsTo($this->getConfig()->get('geoip_model'), 'geoip_id');
    }

    public function pageViewsByRouteName($userId, $uniqueOnly)
    {
        return $this
            ->rightjoin('calendar', function ($join) {
                $join->on($this->getConnection()->raw('tracker_log.created_at'), '=', 'calendar.date');
            })
            ->where('user_id', $userId)
            ->where('calendar.date', '>=', Carbon::now()->startOfDay()->subDays(10))
            ->where('calendar.date', '<=', Carbon::now()->endOfDay())
            ->select(
                $this->getConnection()->raw('calendar.date as date, count(distinct(tracker_log.session_id)) as total'))
            ->groupBy(
                Log::getConnection()->raw('date')
            )
            ->orderBy('date', 'asc')
            ->get();
    }

    public function referersForUser($userId, $startDate, $endDate)
    {
        $query = $this
            ->join('tracker_referers', 'tracker_referers.id', '=', 'tracker_log.referer_id')
            ->where('tracker_log.user_id', $userId)
            ->unique()
            ->select(
                $this->getConnection()->raw('count(*) as count, tracker_referers.host as referer'))
            ->groupBy(
                Log::getConnection()->raw('tracker_referers.host')
            )
			->range($startDate, $endDate, 'tracker_log')
            ->orderBy('count', 'desc');

        return $query->get();
    }

    public function countriesForUser($userId, $startDate, $endDate)
    {
        $query = $this
            ->join('tracker_geoip', 'tracker_geoip.id', '=', 'tracker_log.geoip_id')
            ->where('tracker_log.user_id', $userId)
            ->unique()
            ->select(
                $this->getConnection()->raw('tracker_geoip.country_code as code,
					tracker_geoip.country_name as name, count(*) as value'))
            ->groupBy(
                Log::getConnection()->raw('tracker_geoip.country_code, tracker_geoip.country_name')
            )
            ->range($startDate, $endDate, 'tracker_log')
            ->orderBy('value');

        return $query->get();
    }

    public function viewsAndEarningsForUser($userId)
    {
        $query = $this
            ->join('tracker_geoip', 'tracker_geoip.id', '=', 'tracker_log.geoip_id')
            ->join('countries', 'countries.country_code', '=', 'tracker_geoip.country_code')
            ->join('tiers', 'tiers.id', '=', 'countries.tier_id')
            ->where('tracker_log.user_id', $userId)
            ->unique()
            ->select(
                $this->getConnection()->raw('DATE(tracker_log.created_at) as date, tiers.name as tier,
					count(*) as value, SUM(tiers.rate) as earnings'))
            ->groupBy(
                Log::getConnection()->raw('date')
            )
            ->groupBy(
                Log::getConnection()->raw('tiers.id')
            )
            ->last10Days('tracker_log')
            ->orderBy('tiers.id');

        return $query->get();
    }

    public function tiersForUser($userId, $startDate, $endDate)
    {
        $query = $this
            ->join('tracker_geoip', 'tracker_geoip.id', '=', 'tracker_log.geoip_id')
            ->join('countries', 'countries.country_code', '=', 'tracker_geoip.country_code')
            ->join('tiers', 'tiers.id', '=', 'countries.tier_id')
            ->where('tracker_log.user_id', $userId)
            ->unique()
            ->select(
                $this->getConnection()->raw('DATE(tracker_log.created_at) as date, tiers.name as tier,
					count(*) as value'))
            ->groupBy(
                Log::getConnection()->raw('tier, date')
            )
            ->range($startDate, $endDate, 'tracker_log')
            ->orderBy('tiers.id');
        return $query->get();
    }

    public function isIpUnique($userId, $clientIp)
    {
        return $this
            ->where('tracker_log.user_id', $userId)
            ->where('tracker_log.client_ip', $clientIp)
            ->today('tracker_log')
            ->count() === 0;
    }

    public function scopeUnique($query)
    {
        return $query
            ->where('tracker_log.is_confirmed', 1)
            ->where('tracker_log.is_real', 1)
            ->where('tracker_log.is_adblock', 0)
            ->where('tracker_log.is_proxy', 0)
            ->where('tracker_log.is_unique', 1);
    }
}
