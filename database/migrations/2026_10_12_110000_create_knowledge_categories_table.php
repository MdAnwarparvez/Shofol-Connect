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
        Schema::create('knowledge_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shofol_module_id')->constrained('shofol_modules')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Uniqueness per module
            $table->unique(['shofol_module_id', 'name']);
            $table->unique(['shofol_module_id', 'slug']);

            // Indexes for fast querying, filtering, and ordering
            $table->index('is_active');
            $table->index('sort_order');
            $table->index(['shofol_module_id', 'is_active']);
            $table->index(['shofol_module_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_categories');
    }
};
