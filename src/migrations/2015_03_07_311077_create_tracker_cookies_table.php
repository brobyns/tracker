<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTrackerCookiesTable extends Migration {

	private $table = 'tracker_cookies';

	public function up()
	{
        Schema::create($this->table, function (Blueprint $table)
        {
            $table->bigIncrements('id');

            $table->string('uuid')->unique();

            $table->timestamp('created_at')->index();
            $table->timestamp('updated_at')->index();
        });
	}

	public function down()
	{
		Schema::drop($this->table);
	}

}
