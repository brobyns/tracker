<?php

namespace PragmaRX\Tracker\Vendor\Laravel\Models;

use Carbon\Carbon;

class Log extends Base {

	protected $table = 'tracker_log';

	protected $fillable = array(
		'session_id',
		'method',
		'path_id',
        'referer_id',
		'geoip_id',
        'is_real',
        'is_adblock'
	);

	public function session()
	{
		return $this->belongsTo($this->getConfig()->get('session_model'));
	}

	public function path()
	{
		return $this->belongsTo($this->getConfig()->get('path_model'));
	}

	public function geoip() {
		return $this->belongsTo($this->getConfig()->get('geoip_model'), 'geoip_id');
	}

	public function pageViews($minutes, $results)
	{
		$query = $this->select(
				$this->getConnection()->raw('DATE(created_at) as date, count(*) as total')
			)->groupBy(
				$this->getConnection()->raw('DATE(created_at)')
			)
			->period($minutes)
			->orderBy('date');

		if ($results)
		{
			return $query->get();
		}

		return $query;
	}

	public function pageViewsByCountry($minutes, $results)
	{
		$query =
			$this
			->select(
				'tracker_geoip.country_name as label'
				, $this->getConnection()->raw('count(tracker_log.id) as value')
			)
			->join('tracker_sessions', 'tracker_log.session_id', '=', 'tracker_sessions.id')
			->join('tracker_geoip', 'tracker_sessions.geoip_id', '=', 'tracker_geoip.id')
			->groupBy('tracker_geoip.country_name')
			->period($minutes, 'tracker_log')
			->whereNotNull('tracker_sessions.geoip_id')
			->orderBy('value', 'desc');

		if ($results)
		{
			return $query->get();
		}

		return $query;
	}

	public function pageViewsByRouteName($userid, $uniqueOnly) {
		return $this
			->join('tracker_paths', function($join) use ($userid) {
				$join->on('tracker_paths.id', '=', 'tracker_log.path_id')
					->where('tracker_paths.user_id', $userid);
			})
			->rightjoin('calendar', function($join) {
				$join->on($this->getConnection()->raw('tracker_log.created_at'), '=', 'calendar.date');
			})
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

	public function referersForUser($userid) {
		$query = $this
            ->join('tracker_referers', 'tracker_referers.id', '=', 'tracker_log.referer_id')
            ->join('tracker_paths', 'tracker_paths.id', '=', 'tracker_log.path_id')
			->where('tracker_paths.user_id', $userid)
            ->select(
                $this->getConnection()->raw('count(*) as count, tracker_referers.host as referer'))
            ->groupBy(
                Log::getConnection()->raw('tracker_referers.host')
            )
			->last10Days('tracker_log')
            ->orderBy('count', 'desc');

        return $query->get();
	}

	public function countriesForUser($userid) {
		$query = $this
			->join('tracker_geoip', 'tracker_geoip.id', '=', 'tracker_log.geoip_id')
			->join('tracker_paths', 'tracker_paths.id', '=', 'tracker_log.path_id')
			->where('tracker_paths.user_id', $userid)
			->select(
				$this->getConnection()->raw('tracker_geoip.country_code as code,
					tracker_geoip.country_name as name, count(*) as value'))
			->groupBy(
				Log::getConnection()->raw('tracker_geoip.country_code')
			)
			->last10Days('tracker_log')
            ->orderBy('value');

		return $query->get();
	}

	public function viewsAndEarningsForUser($userid) {
		$query = $this
			->join('tracker_geoip', 'tracker_geoip.id', '=', 'tracker_log.geoip_id')
			->join('tracker_paths', 'tracker_paths.id', '=', 'tracker_log.path_id')
			->join('countries', 'countries.country_code', '=', 'tracker_geoip.country_code')
			->join('tiers', 'tiers.id', '=', 'countries.tier_id')
			->where('tracker_paths.user_id', $userid)
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
			$sql = $query->toSql();

		return $query->get();
	}

	public function tiersForUser($userid) {
		$query = $this
			->join('tracker_geoip', 'tracker_geoip.id', '=', 'tracker_log.geoip_id')
			->join('tracker_paths', 'tracker_paths.id', '=', 'tracker_log.path_id')
			->join('countries', 'countries.country_code', '=', 'tracker_geoip.country_code')
			->join('tiers', 'tiers.id', '=', 'countries.tier_id')
			->where('tracker_paths.user_id', $userid)
			->select(
				$this->getConnection()->raw('DATE(tracker_log.created_at) as date, tiers.name as tier,
					count(*) as value'))
			->groupBy(
				Log::getConnection()->raw('tier')
			)
			->last10Days('tracker_log')
			->orderBy('tiers.id');

		return $query->get();
	}

	public function isIpUnique($userid, $clientIp) {
		$query = $this
			->join('tracker_paths', 'tracker_paths.id', '=', 'tracker_log.path_id')
			->join('tracker_sessions', 'tracker_sessions.id', '=', 'tracker_log.session_id')
			->where('tracker_paths.user_id', $userid)
            ->where('tracker_sessions.client_ip', $clientIp)
			->today('tracker_sessions');
		return $query->count() < 2;
	}
}
