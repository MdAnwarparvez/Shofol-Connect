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
        Schema::table('knowledge_articles', function (Blueprint $table) {
            $table->longText('embedding')->nullable()->after('keywords');
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->longText('embedding')->nullable()->after('keywords');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('knowledge_articles', function (Blueprint $table) {
            $table->dropColumn('embedding');
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->dropColumn('embedding');
        });
    }
};
