<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTrackerDomainsTable extends Migration {

	private $table = 'tracker_domains';

	public function up()
	{
        Schema::create($this->table, function (Blueprint $table)
        {
            $table->bigIncrements('id');

            $table->string('name')->index();

            $table->timestamp('created_at')->index();
            $table->timestamp('updated_at')->index();
        });
	}

	public function down()
	{
		Schema::drop($this->table);
	}

}
