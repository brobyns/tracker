<?php

namespace PragmaRX\Tracker\Vendor\Laravel\Models;

use Carbon\Carbon;
use Symfony\Component\Console\Application;
use Illuminate\Database\Eloquent\Model as Eloquent;

class Base extends Eloquent {

	protected $hidden = ['config'];

	private $config;

	public function __construct(array $attributes = array())
	{
		parent::__construct($attributes);

		$this->setConnection($this->getConfig()->get('connection'));
	}

	public function getConfig()
	{
		if ($this->config)
		{
			return $this->config;
		}
		elseif (isset($GLOBALS["app"]) && $GLOBALS["app"] instanceof Application)
		{
			return $GLOBALS["app"]["tracker.config"];
		}

		return app()->make('tracker.config');
	}

	public function setConfig($config)
	{
		$this->config = $config;
	}

	public function scopePeriod($query, $minutes, $alias = '')
	{
		$alias = $alias ? "$alias." : '';

		return $query
				->where($alias.'updated_at', '>=', $minutes->getStart())
				->where($alias.'updated_at', '<=', $minutes->getEnd());
	}

    public function scopeRange($query, $start, $end, $alias = '')
    {
        $alias = $alias ? "$alias." : '';

        return $query
            ->where($alias.'updated_at', '>=', $start)
            ->where($alias.'updated_at', '<=', $end);
    }

	public function scopeToday($query, $alias = '')
	{
		$alias = $alias ? "$alias." : '';

		return $query
			->where($alias.'updated_at', '>=', Carbon::now()->startOfDay())
			->where($alias.'updated_at', '<=', Carbon::now()->endOfDay());
	}

	public function scopeLast10Days($query, $alias = '')
	{
		$alias = $alias ? "$alias." : '';

		return $query
			->where($alias.'updated_at', '>=', Carbon::now()->startOfDay()->subDays(10))
			->where($alias.'updated_at', '<=', Carbon::now()->endOfDay());
	}

}
