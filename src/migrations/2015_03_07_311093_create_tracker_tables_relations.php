<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTrackerTablesRelations extends Migration {

	public function up()
	{

        Schema::table('tracker_referers', function (Blueprint $table)
		{
			$table->foreign('domain_id')
				->references('id')
				->on('tracker_domains')
				->onUpdate('cascade')
				->onDelete('cascade');
		});

        Schema::table('tracker_sessions', function (Blueprint $table)
        {
			$table->foreign('device_id')
				->references('id')
				->on('tracker_devices')
				->onUpdate('cascade')
				->onDelete('cascade');
		});

        Schema::table('tracker_sessions', function (Blueprint $table)
        {
			$table->foreign('agent_id')
				->references('id')
				->on('tracker_agents')
				->onUpdate('cascade')
				->onDelete('cascade');
		});

        Schema::table('tracker_sessions', function (Blueprint $table)
        {
			$table->foreign('referer_id')
				->references('id')
				->on('tracker_referers')
				->onUpdate('cascade')
				->onDelete('cascade');
		});

        Schema::table('tracker_sessions', function (Blueprint $table)
        {
			$table->foreign('cookie_id')
				->references('id')
				->on('tracker_cookies')
				->onUpdate('cascade')
				->onDelete('cascade');
		});

        Schema::table('tracker_sessions', function (Blueprint $table)
        {
			$table->foreign('geoip_id')
				->references('id')
				->on('tracker_geoip')
				->onUpdate('cascade')
				->onDelete('cascade');
		});

        Schema::table('tracker_log', function (Blueprint $table)
        {
			$table->foreign('session_id')
				->references('id')
				->on('tracker_sessions')
				->onUpdate('cascade')
				->onDelete('cascade');
		});

        Schema::table('tracker_log', function (Blueprint $table)
        {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('tracker_log', function (Blueprint $table)
        {
            $table->foreign('image_id')
                ->references('id')
                ->on('images')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
	}

	public function down()
	{
		// Tables will be dropped in the correct order... :)
	}

}
