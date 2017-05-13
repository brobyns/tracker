<?php

use PragmaRX\Tracker\Support\Migration;

class CreateTrackerLogTable extends Migration {

	/**
	 * Table related to this migration.
	 *
	 * @var string
	 */

	private $table = 'tracker_log';

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function migrateUp()
	{
		$this->builder->create(
			$this->table,
			function ($table)
			{
				$table->bigIncrements('id');

				$table->bigInteger('session_id')->unsigned()->index();
				$table->bigInteger('path_id')->unsigned()->nullable()->index();
				$table->boolean('is_real');
				$table->boolean('is_adblock');
				$table->boolean('is_proxy');

				$table->timestamp('created_at')->index();
				$table->timestamp('updated_at')->index();
			}
		);
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function migrateDown()
	{
		$this->drop($this->table);
	}

}
