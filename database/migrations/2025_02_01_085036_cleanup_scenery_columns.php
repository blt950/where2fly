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
        Schema::table('sceneries', function (Blueprint $table) {
            $table->dropColumn('link');
            $table->dropColumn('payware');
            $table->dropColumn('published');
            $table->dropForeign(['suggested_by_user_id']);
            $table->dropColumn('suggested_by_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sceneries', function (Blueprint $table) {
            $table->string('link')->nullable()->after('developer');
            $table->boolean('payware')->default(false)->after('link');
            $table->boolean('published')->default(false)->after('payware');
            $table->foreignId('suggested_by_user_id')->nullable()->constrained('users', 'id')->onDelete('SET NULL')->after('published');
        });
    }
};
