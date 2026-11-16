<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\ActivityLogger;
use App\Services\AI\AIManager;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    public function aiSettings(AIManager $ai)
    {
        return response()->json(['data' => $this->aiPayload($ai)]);
    }

    public function updateAi(Request $request, AIManager $ai)
    {
        $data = $request->validate([
            'provider' => ['required', Rule::in(array_keys(config('ai.providers')))],
            'model' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._:\-\/]+$/'],
            'timeout' => ['required', 'integer', 'min:5', 'max:300'],
        ]);

        Setting::put('ai.provider', $data['provider']);
        Setting::put('ai.model', $data['model']);
        Setting::put('ai.timeout', $data['timeout']);
        ActivityLogger::log('admin.ai_settings.updated', null, ['provider' => $data['provider'], 'model' => $data['model']]);

        return response()->json(['data' => $this->aiPayload($ai)]);
    }

    public function general()
    {
        return response()->json(['data' => $this->generalPayload()]);
    }

    public function updateGeneral(Request $request)
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:100'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'registration_enabled' => ['required', 'boolean'],
            'documents_retention_days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        Setting::put('site_name', $data['site_name']);
        Setting::put('contact_email', $data['contact_email'] ?? null);
        Setting::put('registration_enabled', (bool) $data['registration_enabled']);
        Setting::put('documents.retention_days', $data['documents_retention_days']);
        ActivityLogger::log('admin.settings.updated', null, ['fields' => array_keys($data)]);

        return response()->json(['data' => $this->generalPayload()]);
    }

    /** API keys are never returned: only whether one is configured and a masked hint. */
    private function aiPayload(AIManager $ai): array
    {
        $provider = $ai->providerName();
        $key = (string) config("ai.providers.$provider.key");

        return [
            'provider' => $provider,
            'available_providers' => array_keys(config('ai.providers')),
            'model' => Setting::get('ai.model', config("ai.providers.$provider.model")),
            'timeout' => (int) Setting::get('ai.timeout', config("ai.providers.$provider.timeout")),
            'api_key_configured' => $key !== '',
            'api_key_hint' => $key !== '' ? '••••••••'.substr($key, -4) : null,
            'max_output_tokens_ceiling' => config('ai.limits.max_output_tokens'),
            'note' => 'API keys are set in the server .env file and cannot be viewed or changed here.',
        ];
    }

    private function generalPayload(): array
    {
        return [
            'site_name' => Setting::get('site_name', config('app.name')),
            'contact_email' => Setting::get('contact_email'),
            'registration_enabled' => (bool) Setting::get('registration_enabled', true),
            'documents_retention_days' => (int) Setting::get('documents.retention_days', config('ai.documents.retention_days')),
        ];
    }
}
