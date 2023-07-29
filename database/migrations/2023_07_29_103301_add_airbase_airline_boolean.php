<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('airports', function (Blueprint $table) {
            $table->text('scheduled_service')->after('municipality');
            $table->boolean('w2f_scheduled_service')->default(false)->after('local_code');
            $table->boolean('w2f_airforcebase')->default(false)->after('w2f_scheduled_service');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('airports', function (Blueprint $table) {
            $table->dropColumn('scheduled_service');
            $table->dropColumn('w2f_scheduled_service');
            $table->dropColumn('w2f_airforcebase');
        });
    }
};
