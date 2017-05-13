<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTrackerLanguageForeignKeyToSessions extends Migration
{
    public function up()
    {
        Schema::table('tracker_sessions', function (Blueprint $table)
        {
            $table->foreign('language_id')
                  ->references('id')
                  ->on('tracker_languages')
                  ->onUpdate('cascade')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('tracker_sessions', function (Blueprint $table)
        {
            $table->dropForeign(['language_id']);
        });
    }
}
