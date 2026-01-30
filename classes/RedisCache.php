<?php
// classes/RedisCache.php
declare(strict_types=1);

namespace Proje;

use Redis;
use Exception;

class RedisCache
{
    private ?Redis $redis = null;
    private int $defaultTtl = 3600; // 1 saat
    private bool $connected = false;

    public function __construct()
    {
        try {
            $this->redis = new Redis();
            $host = getenv('REDIS_HOST') ?: 'redis';
            $port = (int)(getenv('REDIS_PORT') ?: 6379);
            
            $this->connected = $this->redis->connect($host, $port, 2.5);
            
            if ($this->connected) {
                $password = getenv('REDIS_PASSWORD');
                if ($password) {
                    $this->redis->auth($password);
                }
                
                // Serialization için PHP native kullan
                $this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
            }
        } catch (Exception $e) {
            error_log("Redis connection failed: " . $e->getMessage());
            $this->connected = false;
        }
    }

    public function isConnected(): bool
    {
        return $this->connected && $this->redis !== null;
    }

    public function get(string $key)
    {
        if (!$this->isConnected()) {
            return null;
        }

        try {
            $value = $this->redis->get($key);
            return $value === false ? null : $value;
        } catch (Exception $e) {
            error_log("Redis GET error for key {$key}: " . $e->getMessage());
            return null;
        }
    }

    public function set(string $key, $value, ?int $ttl = null): bool
    {
        if (!$this->isConnected()) {
            return false;
        }

        try {
            $ttl = $ttl ?? $this->defaultTtl;
            return $this->redis->setex($key, $ttl, $value);
        } catch (Exception $e) {
            error_log("Redis SET error for key {$key}: " . $e->getMessage());
            return false;
        }
    }

    public function delete(string $key): bool
    {
        if (!$this->isConnected()) {
            return false;
        }

        try {
            return $this->redis->del($key) > 0;
        } catch (Exception $e) {
            error_log("Redis DELETE error for key {$key}: " . $e->getMessage());
            return false;
        }
    }

    public function flush(): bool
    {
        if (!$this->isConnected()) {
            return false;
        }

        try {
            return $this->redis->flushDB();
        } catch (Exception $e) {
            error_log("Redis FLUSH error: " . $e->getMessage());
            return false;
        }
    }

    public function remember(string $key, callable $callback, ?int $ttl = null)
    {
        // Cache'ten al
        $cached = $this->get($key);
        if ($cached !== null) {
            return $cached;
        }

        // Cache miss - callback'i çalıştır
        $value = $callback();

        // Cache'e kaydet
        $this->set($key, $value, $ttl);

        return $value;
    }

    public function increment(string $key, int $value = 1): int
    {
        if (!$this->isConnected()) {
            return 0;
        }

        try {
            return $this->redis->incrBy($key, $value);
        } catch (Exception $e) {
            error_log("Redis INCREMENT error for key {$key}: " . $e->getMessage());
            return 0;
        }
    }

    public function expire(string $key, int $seconds): bool
    {
        if (!$this->isConnected()) {
            return false;
        }

        try {
            return $this->redis->expire($key, $seconds);
        } catch (Exception $e) {
            error_log("Redis EXPIRE error for key {$key}: " . $e->getMessage());
            return false;
        }
    }

    public function __destruct()
    {
        if ($this->redis !== null && $this->connected) {
            try {
                $this->redis->close();
            } catch (Exception $e) {
                // Ignore close errors
            }
        }
    }
}
