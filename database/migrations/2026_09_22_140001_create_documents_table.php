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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('type', ['proposal', 'lpj'])->index();
            $table->string('title');
            $table->longText('background')->nullable();
            $table->longText('objectives')->nullable();
            $table->longText('budget_plan')->nullable();
            $table->longText('summary_content')->nullable();
            $table->longText('ai_generated_draft')->nullable();
            $table->enum('status', ['draft', 'submitted', 'approved', 'revision_requested', 'rejected'])->default('draft')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
