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
        Schema::create('knowledge_articles', function (Blueprint $table) {
            $table->id();
            $table->string('module', 50); // HRM, Payroll, CRM, Accounting
            $table->string('category', 100);
            $table->string('title');
            $table->text('question')->nullable();
            $table->longText('content'); // Answer / Content
            $table->text('keywords')->nullable();
            $table->string('status', 20)->default('active'); // active, inactive
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Indexes for fast searching and filtering in MVP & RAG
            $table->index('module');
            $table->index('status');
            $table->index(['module', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_articles');
    }
};
