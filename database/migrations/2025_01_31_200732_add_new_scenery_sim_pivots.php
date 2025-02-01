<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('scenery_simulators', function (Blueprint $table) {
            $table->string('link')->nullable()->after('simulator_id');
            $table->boolean('payware')->after('link');
            $table->boolean('published')->after('payware');
            $table->string('source')->nullable()->after('published');
            $table->foreignId('suggested_by_user_id')->nullable()->constrained('users', 'id')->onDelete('SET NULL')->after('source');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scenery_simulators', function (Blueprint $table) {
            $table->dropColumn('link');
            $table->dropColumn('payware');
            $table->dropColumn('published');
            $table->dropColumn('source');
            $table->dropForeign(['suggested_by_user_id']);
            $table->dropColumn('suggested_by_user_id');
            $table->dropTimestamps();
        });
    }
};
