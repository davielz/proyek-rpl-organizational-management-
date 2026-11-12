<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('provider', 50); // nvidia, gemini, openai, custom
            $table->text('api_key')->nullable();
            $table->string('model', 150);
            $table->string('base_url', 255)->nullable();
            $table->unsignedSmallInteger('timeout')->default(60);
            $table->unsignedInteger('max_tokens')->default(1000);
            $table->decimal('temperature', 3, 2)->default(0.70);
            $table->boolean('is_active')->default(false);
            $table->boolean('is_default')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Seed initial built-in providers: NVIDIA and Gemini
        $now = now();
        $nvidiaKey = config('ai.api_key') ?: env('NVIDIA_API_KEY');
        $nvidiaModel = config('ai.model', 'nvidia/nemotron-3.5-bolt-e4b');

        DB::table('ai_configurations')->insert([
            [
                'name' => 'NVIDIA NIM',
                'provider' => 'nvidia',
                'api_key' => $nvidiaKey,
                'model' => $nvidiaModel,
                'base_url' => 'https://integrate.api.nvidia.com/v1',
                'timeout' => 60,
                'max_tokens' => 1000,
                'temperature' => 0.70,
                'is_active' => true,
                'is_default' => true,
                'description' => 'Provider resmi NVIDIA NIM Inference Microservices (Llama, Nemotron, Mistral).',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Google Gemini',
                'provider' => 'gemini',
                'api_key' => env('GEMINI_API_KEY') ?: '',
                'model' => 'gemini-1.5-flash',
                'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
                'timeout' => 60,
                'max_tokens' => 1000,
                'temperature' => 0.70,
                'is_active' => false,
                'is_default' => true,
                'description' => 'Google Generative Language API (Gemini 1.5 Flash, Gemini 1.5 Pro, Gemini 2.0).',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_configurations');
    }
};
