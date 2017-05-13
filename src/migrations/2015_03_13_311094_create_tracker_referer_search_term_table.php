<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTrackerRefererSearchTermTable extends Migration {

	private $table = 'tracker_referers_search_terms';


	public function up()
	{
        Schema::create($this->table, function (Blueprint $table)
        {
            $table->bigIncrements('id');

            $table->bigInteger('referer_id')->unsigned()->index();
            $table->string('search_term')->index();

            $table->timestamp('created_at')->index();
            $table->timestamp('updated_at')->index();
        });
	}

	public function down()
	{
		Schema::drop($this->table);
	}

}
