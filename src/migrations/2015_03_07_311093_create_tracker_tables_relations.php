<?php

use PragmaRX\Tracker\Support\Migration;

class CreateTrackerTablesRelations extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function migrateUp()
	{

		$this->builder->table('tracker_route_paths', function($table)
		{
			$table->foreign('route_id')
				->references('id')
				->on('tracker_routes')
				->onUpdate('cascade')
				->onDelete('cascade');
		});

		$this->builder->table('tracker_referers', function($table)
		{
			$table->foreign('domain_id')
				->references('id')
				->on('tracker_domains')
				->onUpdate('cascade')
				->onDelete('cascade');
		});

		$this->builder->table('tracker_sessions', function($table)
		{
			$table->foreign('device_id')
				->references('id')
				->on('tracker_devices')
				->onUpdate('cascade')
				->onDelete('cascade');
		});

		$this->builder->table('tracker_sessions', function($table)
		{
			$table->foreign('agent_id')
				->references('id')
				->on('tracker_agents')
				->onUpdate('cascade')
				->onDelete('cascade');
		});

		$this->builder->table('tracker_sessions', function($table)
		{
			$table->foreign('referer_id')
				->references('id')
				->on('tracker_referers')
				->onUpdate('cascade')
				->onDelete('cascade');
		});

		$this->builder->table('tracker_sessions', function($table)
		{
			$table->foreign('cookie_id')
				->references('id')
				->on('tracker_cookies')
				->onUpdate('cascade')
				->onDelete('cascade');
		});

		$this->builder->table('tracker_sessions', function($table)
		{
			$table->foreign('geoip_id')
				->references('id')
				->on('tracker_geoip')
				->onUpdate('cascade')
				->onDelete('cascade');
		});

		$this->builder->table('tracker_log', function($table)
		{
			$table->foreign('session_id')
				->references('id')
				->on('tracker_sessions')
				->onUpdate('cascade')
				->onDelete('cascade');
		});

		$this->builder->table('tracker_log', function($table)
		{
			$table->foreign('path_id')
				->references('id')
				->on('tracker_paths')
				->onUpdate('cascade')
				->onDelete('cascade');
		});

		$this->builder->table('tracker_log', function($table)
		{
			$table->foreign('route_path_id')
				->references('id')
				->on('tracker_route_paths')
				->onUpdate('cascade')
				->onDelete('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function migrateDown()
	{
		// Tables will be dropped in the correct order... :)
	}

}
