<?php

namespace App\Services\Ecommerce;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    private const CACHE_PREFIX = 'settings:';

    /**
     * @var list<string>
     */
    private const CRITICAL_KEYS = [
        'shop.currency',
        'orders.auto_confirm',
        'mail.order_notifications',
        'users.allow_secondary_users',
        'users.default_role',
        'users.admin.id',
        'users.admin.role',
    ];

    public function __construct(
        private readonly AuditLogService $auditLogs,
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = $this->cacheKey($key);

        return Cache::rememberForever($cacheKey, function () use ($key, $default) {
            $setting = Setting::query()->where('key', $key)->first();

            if (! $setting) {
                return config('ecommerce.'.$key, $default);
            }

            return $this->decodeValue($setting->value, $setting->type);
        });
    }

    public function set(string $key, mixed $value, string $type = 'string', ?string $group = null, ?User $actor = null): Setting
    {
        [$storedValue, $resolvedType] = $this->encodeValue($value, $type);

        $previous = Setting::query()->where('key', $key)->first();
        $previousDecoded = $previous ? $this->decodeValue($previous->value, $previous->type) : null;

        $setting = Setting::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $storedValue,
                'type' => $resolvedType,
                'group' => $group,
            ]
        );

        Cache::forget($this->cacheKey($key));

        $newDecoded = $this->decodeValue($setting->value, $setting->type);

        if ($this->shouldAuditKey($key) && $this->isChanged($previousDecoded, $newDecoded)) {
            $this->auditLogs->log(
                action: 'settings.critical_updated',
                entityType: 'setting',
                entityId: $setting->id,
                before: [
                    'key' => $key,
                    'value' => $previousDecoded,
                    'type' => $previous?->type,
                ],
                after: [
                    'key' => $setting->key,
                    'value' => $newDecoded,
                    'type' => $setting->type,
                ],
                actor: $actor,
                meta: [
                    'group' => $setting->group,
                ],
            );
        }

        return $setting;
    }

    public function has(string $key): bool
    {
        return Setting::query()->where('key', $key)->exists();
    }

    public function forget(string $key): void
    {
        Setting::query()->where('key', $key)->delete();
        Cache::forget($this->cacheKey($key));
    }

    private function cacheKey(string $key): string
    {
        return self::CACHE_PREFIX.$key;
    }

    private function decodeValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'int' => (int) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * @return array{0: string|null, 1: string}
     */
    private function encodeValue(mixed $value, string $type): array
    {
        $resolvedType = $this->normalizeType($type, $value);

        if ($value === null) {
            return [null, $resolvedType];
        }

        return match ($resolvedType) {
            'int' => [(string) ((int) $value), 'int'],
            'bool' => [$value ? '1' : '0', 'bool'],
            'json' => [json_encode($value, JSON_THROW_ON_ERROR), 'json'],
            default => [(string) $value, 'string'],
        };
    }

    private function normalizeType(string $type, mixed $value): string
    {
        if ($type !== 'auto') {
            return $type;
        }

        return match (true) {
            is_int($value) => 'int',
            is_bool($value) => 'bool',
            is_array($value) => 'json',
            default => 'string',
        };
    }

    private function shouldAuditKey(string $key): bool
    {
        if (in_array($key, self::CRITICAL_KEYS, true)) {
            return true;
        }

        return str_starts_with($key, 'payment.') || str_starts_with($key, 'shipping.');
    }

    private function isChanged(mixed $before, mixed $after): bool
    {
        return json_encode($before) !== json_encode($after);
    }
}
