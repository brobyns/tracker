<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTrackerRefererColumnToLog extends Migration
{
	private $table = 'tracker_log';

	public function up()
	{

        Schema::table($this->table, function (Blueprint $table)
        {
            $table->integer('referer_id')->unsigned()->nullable()->index();
        });
	}

	public function down()
	{
        Schema::table($this->table, function (Blueprint $table)
        {
            $table->dropColumn('referer_id');
        });
	}
}
