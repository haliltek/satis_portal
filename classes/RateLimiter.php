<?php
// classes/RateLimiter.php
declare(strict_types=1);

namespace Proje;

use Exception;

class RateLimiter
{
    private RedisCache $cache;

    public function __construct(RedisCache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Rate limit kontrolü yapar
     * 
     * @param string $key Unique identifier (örn: "logo_api:user_123")
     * @param int $maxRequests Maksimum istek sayısı
     * @param int $windowSeconds Zaman penceresi (saniye)
     * @return bool True = izin ver, False = limit aşıldı
     */
    public function checkLimit(string $key, int $maxRequests, int $windowSeconds): bool
    {
        if (!$this->cache->isConnected()) {
            // Redis yoksa rate limiting yapma (fail-open)
            return true;
        }

        try {
            $current = $this->cache->increment($key);
            
            if ($current === 1) {
                // İlk istek - TTL ayarla
                $this->cache->expire($key, $windowSeconds);
            }
            
            return $current <= $maxRequests;
        } catch (Exception $e) {
            error_log("Rate limiter error: " . $e->getMessage());
            return true; // Hata durumunda izin ver
        }
    }

    /**
     * Kalan istek sayısını döndürür
     */
    public function remaining(string $key, int $maxRequests): int
    {
        if (!$this->cache->isConnected()) {
            return $maxRequests;
        }

        $current = (int)$this->cache->get($key);
        return max(0, $maxRequests - $current);
    }

    /**
     * Rate limit'i sıfırla
     */
    public function reset(string $key): bool
    {
        return $this->cache->delete($key);
    }
}
