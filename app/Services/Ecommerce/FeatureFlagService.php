<?php

namespace App\Services\Ecommerce;

use App\Models\FeatureFlag;
use Illuminate\Support\Facades\Cache;

class FeatureFlagService
{
    private const CACHE_PREFIX = 'feature_flags:';

    public function isEnabled(string $code, string $scope = 'global', bool $default = false): bool
    {
        $cacheKey = $this->cacheKey($code, $scope);

        return Cache::rememberForever($cacheKey, function () use ($code, $scope, $default) {
            $flag = FeatureFlag::query()
                ->where('code', $code)
                ->where('scope', $scope)
                ->first();

            if (! $flag && $scope !== 'global') {
                $flag = FeatureFlag::query()
                    ->where('code', $code)
                    ->where('scope', 'global')
                    ->first();
            }

            if (! $flag) {
                return (bool) config('ecommerce.feature_flags.'.$code, $default);
            }

            return (bool) $flag->enabled;
        });
    }

    public function set(string $code, bool $enabled, string $scope = 'global'): FeatureFlag
    {
        $scope = $scope !== '' ? $scope : 'global';

        $flag = FeatureFlag::query()->updateOrCreate(
            [
                'code' => $code,
                'scope' => $scope,
            ],
            [
                'enabled' => $enabled,
            ]
        );

        Cache::forget($this->cacheKey($code, $scope));

        return $flag;
    }

    public function forget(string $code, string $scope = 'global'): void
    {
        FeatureFlag::query()
            ->where('code', $code)
            ->where('scope', $scope)
            ->delete();

        Cache::forget($this->cacheKey($code, $scope));
    }

    private function cacheKey(string $code, string $scope): string
    {
        return self::CACHE_PREFIX.$scope.':'.$code;
    }
}
