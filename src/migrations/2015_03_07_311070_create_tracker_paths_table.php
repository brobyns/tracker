<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTrackerPathsTable extends Migration {

	private $table = 'tracker_paths';

    public function up()
    {

        Schema::create($this->table, function (Blueprint $table)
        {
            $table->bigIncrements('id');

            $table->string('path')->index();
            $table->bigInteger('user_id')->unsigned()->index();

            $table->timestamp('created_at')->index();
            $table->timestamp('updated_at')->index();
        });
    }

    public function down()
    {
        Schema::drop($this->table);
    }

}
