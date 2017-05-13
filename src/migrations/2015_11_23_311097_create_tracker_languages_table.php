<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTrackerLanguagesTable extends Migration
{

    private $table = 'tracker_languages';

    public function up()
    {
        Schema::create($this->table, function (Blueprint $table)
        {
            $table->bigIncrements('id');

            $table->string('preference')->index();
            $table->string('language-range')->index();

            $table->unique(['preference', 'language-range']);

            $table->timestamp('created_at')->index();
            $table->timestamp('updated_at')->index();
        });
    }

    public function down()
    {
        Schema::drop($this->table);
    }
}
