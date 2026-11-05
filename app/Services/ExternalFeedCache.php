<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;
use Throwable;

/** Feed cache explicitly uses files, never the application's database cache store. */
class ExternalFeedCache
{
    public function remember(string $key, int $ttlSeconds, Closure $loader): array
    {
        $cache = Cache::store('file');
        $freshKey = 'sigap:feed:fresh:'.$key;
        $lastKey = 'sigap:feed:last-success:'.$key;
        $failureKey = 'sigap:feed:last-failure:'.$key;
        try {
            $fresh = $this->get($cache, $freshKey);
            if (is_array($fresh)) {
                return $fresh;
            }
            $failure = $this->get($cache, $failureKey);
            if (is_array($failure)) {
                return $this->fallback($this->get($cache, $lastKey), $failure['message']);
            }
            $result = $loader();
            if (! is_array($result) || ! in_array($result['status'] ?? null, ['available', 'partial'], true)) {
                throw new \UnexpectedValueException('Format sumber tidak valid.');
            }
            $result['fetched_at'] = now()->toIso8601String();
            $this->put($cache, $lastKey, $result, now()->addDays(7));
            $this->put($cache, $freshKey, $result, now()->addSeconds($ttlSeconds));
            $this->forget($cache, $failureKey);

            return $result;
        } catch (Throwable) {
            $message = 'Sumber tidak dapat diperbarui saat ini.';
            $this->put($cache, $failureKey, ['message' => $message], now()->addMinute());

            return $this->fallback($this->get($cache, $lastKey), $message);
        }
    }

    private function get(mixed $cache, string $key): mixed
    {
        try {
            return $cache->get($key);
        } catch (Throwable) {
            return null;
        }
    }

    private function put(mixed $cache, string $key, mixed $value, mixed $expiration): void
    {
        try {
            $cache->put($key, $value, $expiration);
        } catch (Throwable) { /* feed still works without cache */
        }
    }

    private function forget(mixed $cache, string $key): void
    {
        try {
            $cache->forget($key);
        } catch (Throwable) { /* cache cleanup is best-effort */
        }
    }

    private function fallback(mixed $last, string $message): array
    {
        if (is_array($last)) {
            $last['status'] = 'stale';
            $last['message'] = $message;

            return $last;
        }

        return ['status' => 'unavailable', 'items' => [], 'fetched_at' => null, 'message' => $message];
    }
}
