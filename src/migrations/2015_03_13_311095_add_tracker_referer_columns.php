<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTrackerRefererColumns extends Migration {

	private $table = 'tracker_referers';

	private $foreign = 'tracker_referers_search_terms';

	public function up()
	{
        Schema::table($this->table, function (Blueprint $table)
        {
            $table->string('medium')->nullable()->index();
            $table->string('source')->nullable()->index();
            $table->string('search_terms_hash')->nullable()->index();
        });

        Schema::table($this->foreign, function (Blueprint $table)
        {
			$table->foreign('referer_id', 'tracker_referers_referer_id_fk')
				->references('id')
				->on('tracker_referers')
				->onUpdate('cascade')
				->onDelete('cascade');
		});
	}

	public function down()
	{
        Schema::table($this->table, function (Blueprint $table) {
            $table->dropColumn('medium');
            $table->dropColumn('source');
            $table->dropColumn('search_terms_hash');
        });

        Schema::table($this->foreign, function (Blueprint $table) {
            $table->dropForeign('tracker_referers_referer_id_fk');
        });

	}

}
