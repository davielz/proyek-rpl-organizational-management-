<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\StoreAiConfigurationRequest;
use App\Http\Requests\Admin\UpdateAiConfigurationRequest;
use App\Models\AiConfiguration;
use App\Services\Ai\NvidiaChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminAiConfigurationController extends Controller
{
    /**
     * Display a listing of AI configurations.
     */
    public function index(): View
    {
        $configurations = AiConfiguration::query()
            ->orderByDesc('is_active')
            ->orderBy('id')
            ->get();

        $activeConfig = AiConfiguration::getActive();

        return view('admin.ai.index', compact('configurations', 'activeConfig'));
    }

    /**
     * Store a newly created AI configuration.
     */
    public function store(StoreAiConfigurationRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $provider = strtolower($data['provider']);

        // Populate default base_url if left empty
        if (empty($data['base_url'])) {
            $data['base_url'] = match ($provider) {
                'gemini' => 'https://generativelanguage.googleapis.com/v1beta',
                'openai' => 'https://api.openai.com/v1',
                default => 'https://integrate.api.nvidia.com/v1',
            };
        }

        $data['timeout'] = $data['timeout'] ?? 60;
        $data['max_tokens'] = $data['max_tokens'] ?? 1000;
        $data['temperature'] = $data['temperature'] ?? 0.70;
        $shouldActivate = $request->boolean('is_active');
        $data['is_active'] = false;
        $data['is_default'] = false;

        $config = AiConfiguration::create($data);

        if ($shouldActivate) {
            $config->activate();
        }

        return redirect()->route('admin.ai.index')->with(
            'success',
            "Provider AI '{$config->name}' berhasil ditambahkan ke dalam daftar sistem."
        );
    }

    /**
     * Update the specified AI configuration.
     */
    public function update(UpdateAiConfigurationRequest $request, AiConfiguration $config): RedirectResponse
    {
        $data = $request->validated();

        // Only update api_key if the administrator filled a new value
        if (empty($data['api_key'])) {
            unset($data['api_key']);
        }

        $shouldActivate = $request->boolean('is_active');
        unset($data['is_active']);

        $config->update($data);

        if ($shouldActivate) {
            $config->activate();
        }

        return redirect()->route('admin.ai.index')->with(
            'success',
            "Konfigurasi provider AI '{$config->name}' berhasil diperbarui."
        );
    }

    /**
     * Set the specified configuration as the active AI provider.
     */
    public function activate(AiConfiguration $config): RedirectResponse
    {
        $config->activate();

        return redirect()->route('admin.ai.index')->with(
            'success',
            "Provider '{$config->name}' ({$config->model}) sekarang aktif sebagai model utama AI Assistant."
        );
    }

    /**
     * Remove the specified AI configuration from storage.
     */
    public function destroy(AiConfiguration $config): RedirectResponse
    {
        if ($config->is_active) {
            return back()->with(
                'error',
                'Tidak dapat menghapus provider yang sedang aktif. Silakan aktifkan provider lain terlebih dahulu.'
            );
        }

        if ($config->is_default) {
            return back()->with(
                'error',
                'Provider bawaan sistem (default) tidak dapat dihapus.'
            );
        }

        $name = $config->name;
        $config->delete();

        return redirect()->route('admin.ai.index')->with(
            'success',
            "Konfigurasi provider AI '{$name}' berhasil dihapus."
        );
    }

    /**
     * Test connection for the given AI configuration.
     */
    public function test(AiConfiguration $config, NvidiaChatService $chatService): JsonResponse
    {
        $result = $chatService->testConnection($config);

        return response()->json($result);
    }
}

