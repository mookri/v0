<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Modules\Customers\Models\CustomerSetting;
use App\Modules\Customers\CustomerActivityRecorder;
use App\Modules\Customers\Outbox\FactAppender;
use App\Platform\Audit\AuditWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Customer settings (B4): GET merges stored rows over the closed v1
 * defaults; PATCH accepts only declared keys with strict per-key types
 * (unknown keys and mistyped values are 422). The BFF maps this
 * key-value surface to its sections shape in F2.
 */
final class CustomerSettingController extends CustomerSelfServiceController
{
    private readonly FactAppender $facts;

    public function __construct(
        private readonly AuditWriter $audit,
        private readonly CustomerActivityRecorder $activity,
        ?FactAppender $facts = null
    ) {
        $this->facts = $facts ?? app(FactAppender::class);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        return new JsonResponse([
            'data' => [
                'object' => 'customer_settings',
                'settings' => $this->resolved((string) $user->getAuthIdentifier()),
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }
        $userId = (string) $user->getAuthIdentifier();

        $unknown = array_diff(array_keys($request->all()), array_keys(CustomerSetting::KEYS));
        if ($unknown !== []) {
            return new JsonResponse([
                'error' => [
                    'code' => 'validation_failed',
                    'message' => 'Unknown settings: '.implode(', ', $unknown).'.',
                    'details' => ['fields' => array_fill_keys($unknown, ['unknown_setting'])],
                    'request_id' => $this->requestId($request),
                ],
            ], 422);
        }

        $validated = $request->validate($this->rules());

        foreach ($validated as $key => $value) {
            CustomerSetting::query()->updateOrCreate(
                ['user_id' => $userId, 'key' => $key],
                ['value' => $value]
            );
        }

        $this->audit->record(
            'customers.settings.updated',
            ['module' => 'customers', 'resource_type' => 'customer_settings', 'resource_id' => $userId],
            null,
            ['user_id' => $userId, 'keys' => array_keys($validated)]
        );
        $this->facts->append('customers.settings.updated', $userId, ['keys' => array_keys($validated)]);
        $this->activity->record($userId, 'settings_updated', 'Settings updated');

        return new JsonResponse([
            'data' => [
                'object' => 'customer_settings',
                'settings' => $this->resolved($userId),
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolved(string $userId): array
    {
        $stored = CustomerSetting::query()
            ->where('user_id', $userId)
            ->pluck('value', 'key')
            ->all();

        return array_merge(CustomerSetting::KEYS, array_intersect_key($stored, CustomerSetting::KEYS));
    }

    /**
     * @return array<string, list<string>>
     */
    private function rules(): array
    {
        return [
            'locale' => ['sometimes', 'string', 'max:8'],
            'notifications_email' => ['sometimes', 'boolean'],
            'notifications_sms' => ['sometimes', 'boolean'],
            'marketing_opt_in' => ['sometimes', 'boolean'],
            'timezone' => ['sometimes', 'string', 'max:64'],
            'quiet_hours' => ['sometimes', 'nullable', 'array'],
            'quiet_hours.start' => ['required_with:quiet_hours', 'date_format:H:i'],
            'quiet_hours.end' => ['required_with:quiet_hours', 'date_format:H:i'],
        ];
    }
}
