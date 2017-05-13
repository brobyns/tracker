<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLanguageIdColumnToSessions extends Migration
{
    private $table = 'tracker_sessions';

    public function up()
    {
        Schema::table($this->table, function (Blueprint $table)
        {
            $table->bigInteger('language_id')->unsigned()->nullable()->index();
        });
    }

    public function down()
    {
        Schema::table($this->table, function (Blueprint $table)
        {
            $table->dropColumn('language_id');
        });
    }
}
