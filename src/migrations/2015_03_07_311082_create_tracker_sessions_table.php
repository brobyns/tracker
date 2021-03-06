<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTrackerSessionsTable extends Migration {

	private $table = 'tracker_sessions';

	public function up()
	{
        Schema::create($this->table, function (Blueprint $table)
        {
            $table->bigIncrements('id');

            $table->string('uuid')->unique()->index();
            $table->bigInteger('user_id')->unsigned()->nullable()->index();
            $table->bigInteger('device_id')->unsigned()->nullable()->index();
            $table->bigInteger('agent_id')->unsigned()->nullable()->index();
            $table->string('client_ip')->index();
            $table->bigInteger('referer_id')->unsigned()->nullable()->index();
            $table->bigInteger('cookie_id')->unsigned()->nullable()->index();
            $table->bigInteger('geoip_id')->unsigned()->nullable()->index();
            $table->string('user_agent')->nullable();
            $table->boolean('is_robot');

            $table->timestamp('created_at')->index();
            $table->timestamp('updated_at')->index();
        });
	}

	public function down()
	{
		Schema::drop($this->table);
	}

}
