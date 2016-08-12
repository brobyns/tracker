<?php

namespace PragmaRX\Tracker\Vendor\Laravel\Models;

class Log extends Base {

	protected $table = 'tracker_log';

	protected $fillable = array(
		'session_id',
		'method',
		'path_id',
		'query_id',
		'route_path_id',
        'referer_id',
		'is_ajax',
		'is_secure',
		'is_json',
		'wants_json',
		'error_id',
		'geoip_id',
	);

	public function session()
	{
		return $this->belongsTo($this->getConfig()->get('session_model'));
	}

	public function path()
	{
		return $this->belongsTo($this->getConfig()->get('path_model'));
	}

	public function error()
	{
		return $this->belongsTo($this->getConfig()->get('error_model'));
	}

	public function logQuery()
	{
		return $this->belongsTo($this->getConfig()->get('query_model'), 'query_id');
	}

	public function routePath()
	{
		return $this->belongsTo($this->getConfig()->get('route_path_model'), 'route_path_id');
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

	public function errors($minutes, $results)
	{
		$query = $this
					->with('error')
					->with('session')
					->with('path')
					->period($minutes, 'tracker_log')
					->whereNotNull('error_id')
					->orderBy('created_at', 'desc');

		if ($results)
		{
			return $query->get();
		}

		return $query;
	}

	public function allByRouteName($name, $minutes = null)
	{
		$result = $this
					->join('tracker_route_paths', 'tracker_route_paths.id', '=', 'tracker_log.route_path_id')

					->leftJoin(
						'tracker_route_path_parameters',
						'tracker_route_path_parameters.route_path_id',
						'=',
						'tracker_route_paths.id'
					)

					->join('tracker_routes', 'tracker_routes.id', '=', 'tracker_route_paths.route_id')

					->where('tracker_routes.name', $name);

		if ($minutes)
		{
			$result->period($minutes, 'tracker_log');
		}
		return $result;
	}

	/* added for pageviews per route*/

	public function pageViewsByRouteNameWithId($minutes, $name, $id, $uniqueOnly) {

        $selectStatement = $this->createSelectStatementPageViewsByRouteName($uniqueOnly);

		$query = $this
			->join('tracker_route_paths', 'tracker_route_paths.id', '=', 'tracker_log.route_path_id')

			->leftJoin(
				'tracker_route_path_parameters',
				'tracker_route_path_parameters.route_path_id',
				'=',
				'tracker_route_paths.id'
			)

			->join('tracker_routes', 'tracker_routes.id', '=', 'tracker_route_paths.route_id')

			->where('tracker_routes.name', $name)
			->where(function($query2) use ($id, $minutes)
			{
				$query2
					->where('parameter', 'id')
					->where('value', $id);

			})
            ->select(
                    $this->getConnection()->raw($selectStatement))
            ->groupBy(
				Log::getConnection()->raw('DATE(tracker_log.created_at)')
			)
			->period($minutes, 'tracker_log')
			->orderBy('date');

			return $query->get();
	}

    public function pageViewsByRouteName($userid, $minutes, $name, $uniqueOnly) {

        $selectStatement = $this->createSelectStatementPageViewsByRouteName($uniqueOnly);

        $query = $this
            ->join('tracker_paths', 'tracker_paths.id', '=', 'tracker_log.path_id')

            ->where('tracker_paths.user_id', $userid)
            ->select(
                $this->getConnection()->raw($selectStatement))
            ->groupBy(
                Log::getConnection()->raw('DATE(tracker_log.created_at)')
            )
            ->period($minutes, 'tracker_log')
            ->orderBy('date');
		$sql = $query->toSql();

        return $query->get();
    }

	public function referersForUser($userid, $minutes) {
		$query = $this
            ->join('tracker_referers', 'tracker_referers.id', '=', 'tracker_log.referer_id')
            ->join('tracker_paths', 'tracker_paths.id', '=', 'tracker_log.path_id')
			->where('tracker_paths.user_id', $userid)
            ->select(
                $this->getConnection()->raw('count(*) as count, tracker_referers.host as referer'))
            ->groupBy(
                Log::getConnection()->raw('tracker_referers.host')
            )
            ->period($minutes, 'tracker_log')
            ->orderBy('count', 'desc');

        return $query->get();
	}

	public function countriesForUser($userid, $minutes) {
		$query = $this
			->join('tracker_geoip', 'tracker_geoip.id', '=', 'tracker_log.geoip_id')
			->join('tracker_paths', 'tracker_paths.id', '=', 'tracker_log.path_id')
			->where('tracker_paths.user_id', $userid)
			->select(
				$this->getConnection()->raw('tracker_geoip.country_code as code, count(*) as value'))
			->groupBy(
				Log::getConnection()->raw('tracker_geoip.country_code')
			)
			->period($minutes, 'tracker_log')
            ->orderBy('value');

		return $query->get();
	}

	public function viewsAndEarningsForUser($userid, $minutes) {
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
			->period($minutes, 'tracker_log')
			->orderBy('tiers.id');

		return $query->get();
	}

	public function tiersForUser($userid, $minutes) {
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
			->period($minutes, 'tracker_log')
			->orderBy('tiers.id');

		return $query->get();
	}

    private function createSelectStatementPageViewsByRouteName($uniqueOnly) {
        $countColumn = ($uniqueOnly) ? 'distinct(tracker_log.session_id)' : '*';
        return 'DATE(tracker_log.created_at) as date, count('. $countColumn .') as total';
    }

	public function isIpUnique($userid, $clientIp) {
		$query = $this
			->join('tracker_paths', 'tracker_paths.id', '=', 'tracker_log.path_id')
			->join('tracker_sessions', 'tracker_sessions.id', '=', 'tracker_log.session_id')
			->where('tracker_paths.user_id', $userid)
            ->where('tracker_sessions.client_ip', $clientIp)
			->today('tracker_sessions');
		return $query->count();
	}
}
