<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTrackerAgentsTable extends Migration {

    private $table = 'tracker_agents';

    public function up()
    {
        Schema::create($this->table, function (Blueprint $table)
        {
            $table->bigIncrements('id');

            $table->string('name')->unique();
            $table->string('browser')->index();
            $table->string('browser_version');

            $table->timestamp('created_at')->index();
            $table->timestamp('updated_at')->index();
        });
    }

    public function down()
    {
        Schema::drop($this->table);
    }

}
