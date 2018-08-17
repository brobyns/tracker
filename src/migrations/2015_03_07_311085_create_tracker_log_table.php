<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTrackerLogTable extends Migration {

	private $table = 'tracker_log';

	public function up()
	{
        Schema::create($this->table, function (Blueprint $table)
        {
            $table->bigIncrements('id');

            $table->bigInteger('session_id')->unsigned()->index();
            $table->bigInteger('user_id')->unsigned()->index();
            $table->bigInteger('image_id')->unsigned()->index();
            $table->bigInteger('referer_id')->unsigned()->index();
            $table->bigInteger('geoip_id')->unsigned()->index();
            $table->ipAddress('client_ip')->unsigned()->index();
            $table->boolean('is_real');
            $table->boolean('is_adblock');
            $table->boolean('is_proxy');
            $table->boolean('is_confirmed');
            $table->boolean('is_unique');

            $table->timestamp('created_at')->index();
            $table->timestamp('updated_at')->index();
        });
	}

	public function down()
	{
		Schema::drop($this->table);
	}

}
