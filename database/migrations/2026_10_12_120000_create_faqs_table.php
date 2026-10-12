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
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shofol_module_id')->constrained('shofol_modules')->cascadeOnDelete();
            $table->foreignId('knowledge_category_id')->nullable()->constrained('knowledge_categories')->nullOnDelete();
            $table->string('question', 500);
            $table->string('slug')->unique();
            $table->longText('answer');
            $table->text('keywords')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Indexes for fast querying, filtering, ordering, and RAG retrieval
            $table->index('is_active');
            $table->index('sort_order');
            $table->index(['shofol_module_id', 'is_active']);
            $table->index(['knowledge_category_id', 'is_active']);
            $table->index(['shofol_module_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
