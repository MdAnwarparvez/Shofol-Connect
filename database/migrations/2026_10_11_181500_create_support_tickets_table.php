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
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained('conversations')->nullOnDelete();
            $table->string('subject');
            $table->longText('description');
            $table->string('category', 50); // HRM, Payroll, CRM, Accounting, Technical, Other
            $table->string('priority', 20)->default('Medium'); // Low, Medium, High
            $table->string('status', 20)->default('Open'); // Open, In Progress, Resolved, Closed
            $table->longText('admin_response')->nullable(); // Admin resolution / response notes
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            // Indexes for efficient querying and admin filters
            $table->index('user_id');
            $table->index('status');
            $table->index('category');
            $table->index('priority');
            $table->index(['status', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
