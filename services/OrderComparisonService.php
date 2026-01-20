<?php

declare(strict_types=1);

namespace Proje\Services;

use App\Models\SalesOrderMap;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class OrderComparisonService
{
    private LoggerInterface $logger;

    public function __construct(string $debugLogFile)
    {
        $monolog = new Logger('order_comparer');

        // 1) Handler ve Formatter ekliyoruz
        $handler = new StreamHandler($debugLogFile, Logger::DEBUG);
        $formatter = new LineFormatter(
            // format: [tarih] kanal.seviye: mesaj context
            "[%datetime%] %channel%.%level_name%: %message% %context%\n",
            "Y-m-d\TH:i:sP",
            true,
            true
        );
        $handler->setFormatter($formatter);
        $monolog->pushHandler($handler);

        $this->logger = $monolog;
    }

    // -- PUBLIC API ---------------------------------------------------------

    /**
     * Hem diff’i hem de header için update payload’unu döner.
     *
     * @return [
     *   'diff' => [FIELD => ['old'=>..., 'new'=>...], …],
     *   'updatePayload' => [ API_FIELD => yeni_değer, … ]
     * ]
     */
    public function compareHeadersWithPayload(array $local, array $rawLogo): array
    {
        // 1) Diff
        $diff = $this->compareHeaders($local, $rawLogo);

        // 2) Payload: DB’deki (“old”) değerleri gönderecek şekilde topla
        $payload = [];
        foreach ($diff as $apiField => $change) {
            // change['old'] = local DB’deki değer
            $payload[$apiField] = $change['old'];
        }

        return [
            'diff' => $diff,
            'updatePayload' => $payload,
        ];
    }

    /**
     * Hem diff’i hem de items için update payload’unu döner.
     *
     * @return [
     *   'diff' => [key => [FIELD => ['old'=>..., 'new'=>...], …], …],
     *   'updatePayload' => [ {API-raw-item}, … ]
     * ]
     */
    public function compareItemsWithPayload(
        array $localItems,
        array $rawLogoItems,
        array $ignore = []
    ): array {
        // 1) Diff
        $diffItems = $this->compareItems($localItems, $rawLogoItems, $ignore);

        // 2) Payload: mevcut rawLogoItems’ı patch’leyerek, değişen alanların
        //    eski (old) değerlerini koyuyoruz. Yeni satırları mapLocalToApiRaw ile ekliyoruz.
        $reverseMap = array_flip(SalesOrderMap::$transaction);
        [$localByKey, $logoByKey, $rawByKey] = $this->prepareItemCollections($localItems, $rawLogoItems);

        $patchedRaw = $this->patchRawItems($rawByKey, $diffItems, $reverseMap);
        foreach (array_diff(array_keys($localByKey), array_keys($logoByKey)) as $newKey) {
            $patchedRaw[$newKey] = $this->mapLocalToApiRaw($localByKey[$newKey], $reverseMap);
        }

        foreach ($rawLogoItems as $raw) {
            $mapped = SalesOrderMap::remap($raw, SalesOrderMap::$transaction);
            $key = $mapped['internal_reference'] ?: $mapped['kod'];
            // raw’daki DREF’i payload’a taşı
            if (isset($raw['DREF'])) {
                $patchedRaw[$key]['DREF'] = $raw['DREF'];
            }
        }

        return [
            'diff' => $diffItems,
            'updatePayload' => array_values($patchedRaw),
        ];
    }

    public function compareHeaders(array $local, array $rawLogo): array
    {
        $this->logDebug('compareHeaders: raw logo header', ['logoRaw' => $rawLogo]);

        // Logo’dan gelen header’ı DB sütun adlarına map et
        $mappedLogo = $this->mapHeader($rawLogo);

        // DB adlarıyla diff al
        $diff = self::diffAssocRecursive($local, $mappedLogo);

        // DB adlarını API alan adlarına çevir
        $reverseMap = array_flip(SalesOrderMap::$header);
        $apiDiff = [];
        foreach ($diff as $dbField => $change) {
            $apiKey = $reverseMap[$dbField] ?? $dbField;
            $apiDiff[$apiKey] = $change;
        }

        // Eğer gerçekten güncelleme varsa, yeni değerleri yine API adlarıyla logla
        if ($apiDiff) {
            $updated = $this->extractNewValues($apiDiff);
            $this->logInfo('compareHeaders: updated header fields', ['updatedFields' => $updated]);
        }

        // API adlarıyla diff’i döndür
        return $apiDiff;
    }

    public function compareItems(array $localItems, array $rawLogoItems, array $ignore = []): array
    {
        $this->logDebug('compareItems: raw logo items', ['logoRawItems' => $rawLogoItems]);
        $reverseMap = array_flip(SalesOrderMap::$transaction);
        [$localByKey, $logoByKey, $rawByKey] = $this->prepareItemCollections($localItems, $rawLogoItems);
    
        // A) Ortak anahtarlar için diff
        $diffItems = $this->computeDiffItems($localByKey, $logoByKey, $ignore);
    
        // B) Logo’da olmayan (yeni) local satırları diff’e ekle
        $newKeys = array_diff(array_keys($localByKey), array_keys($logoByKey));
        foreach ($newKeys as $newKey) {
            $diffItems[$newKey] = self::diffAssocRecursive($localByKey[$newKey], []);
        }
    
        // C) “id” farkını sadece indirim (type=2) satırlarında, ama diğer alanlar da değişmişse bırak,
        //    aksi halde yani id tek başına diff ise hepsinden silelim.
        foreach ($diffItems as $key => $fields) {
            $type = $localByKey[$key]['transaction_type']
                  ?? $logoByKey[$key]['transaction_type']
                  ?? null;
    
            if ($type !== 2) {
                // indirim değilse id’i her halükârda çıkar
                unset($diffItems[$key]['id']);
            } else {
                // indirimse, diff yalnızca id’den ibaretse komple sil
                if (count($fields) === 1 && isset($fields['id'])) {
                    unset($diffItems[$key]);
                    continue;
                }
            }
    
            // eğer çıkarınca başka diff kalmadıysa tüm satırı sil
            if (isset($diffItems[$key]) && empty($diffItems[$key])) {
                unset($diffItems[$key]);
            }
        }
    
        // Log’la ve sadece diff’i döndür
        if ($diffItems || $newKeys) {
            $this->logInfo('compareItems: updated logo raw items', [
                'diffItems' => $diffItems,
                'newKeys'   => $newKeys,
            ]);
        }
    
        return $diffItems;
    }

    // -- PRIVATE HELPERS ---------------------------------------------------

    private function mapHeader(array $raw): array
    {
        return SalesOrderMap::mapHeader($raw);
    }

    private function extractNewValues(array $diff): array
    {
        $out = [];
        foreach ($diff as $field => $change) {
            $out[$field] = $change['new'];
        }
        return $out;
    }

    private function prepareItemCollections(array $localItems, array $rawLogoItems): array
    {
        $localByKey = $logoByKey = $rawByKey = [];

        // — Logo’dan gelen kalemler
        foreach ($rawLogoItems as $i => $raw) {
            $mapped = SalesOrderMap::remap($raw, SalesOrderMap::$transaction);
            self::normalizeTransactionRow($mapped);

            if (!empty($mapped['internal_reference'])) {
                $key = (string) $mapped['internal_reference'];
            } elseif (!empty($mapped['kod'])) {
                $key = (string) $mapped['kod'];
            } elseif (!empty($raw['DREF'])) {
                // eğer Logo’dan DREF dönüyorsa
                $key = 'logo_DREF_' . $raw['DREF'];
            } else {
                // en son çare: döngü indeksi
                $key = 'logo_idx_' . $i;
            }

            $logoByKey[$key] = $mapped;
            $rawByKey[$key] = $raw;
        }

        // — Lokal DB kalemleri
        foreach ($localItems as $item) {
            self::normalizeTransactionRow($item);

            if (!empty($item['internal_reference'])) {
                $key = (string) $item['internal_reference'];
            } elseif (!empty($item['kod'])) {
                $key = (string) $item['kod'];
            } else {
                // BURADA: veritabanındaki PK id’sini kullanıyoruz
                $key = 'local_db_' . $item['id'];
            }

            $localByKey[$key] = $item;
        }

        // Debug: hangi anahtarlarla başlamış gördük
        $this->logDebug('prepareItemCollections keys', [
            'localKeys' => array_keys($localByKey),
            'logoKeys' => array_keys($logoByKey),
        ]);

        return [$localByKey, $logoByKey, $rawByKey];
    }


    private function computeDiffItems(array $localByKey, array $logoByKey, array $ignore): array
    {
        $diffItems = [];
        $allKeys = array_unique(array_merge(array_keys($localByKey), array_keys($logoByKey)));

        foreach ($allKeys as $key) {
            $diff = $this->diffAssocRecursive(
                $localByKey[$key] ?? [],
                $logoByKey[$key] ?? []
            );
            foreach ($ignore as $fld) {
                unset($diff[$fld]);
            }
            if ($diff) {
                $diffItems[$key] = $diff;
            }
        }

        return $diffItems;
    }

    private function patchRawItems(array $rawByKey, array $diffItems, array $reverseMap): array
    {
        foreach ($diffItems as $key => $changes) {
            foreach ($changes as $dbField => $change) {
                if (isset($reverseMap[$dbField])) {
                    $apiField = $reverseMap[$dbField];
                    $rawByKey[$key][$apiField] = $change['old'];
                }
            }
        }
        return $rawByKey;
    }

    /**
     * Map a local DB-style row back into the minimal API payload for “new” items.
     * We only include those fields that actually changed in Logo.
     */
    private function mapLocalToApiRaw(array $localRow, array $reverseMap): array
    {
        $apiRaw = [];
        foreach ($localRow as $dbField => $value) {
            if (isset($reverseMap[$dbField])) {
                $apiField = $reverseMap[$dbField];
                $apiRaw[$apiField] = $value;
            }
        }
        // ensure at least the identifier is set
        if (!isset($apiRaw['INTERNAL_REFERENCE']) && isset($localRow['kod'])) {
            // this is genuinely new, so no internal_reference
            $apiRaw['MASTER_CODE'] = $localRow['kod'];
        }
        return $apiRaw;
    }

    private function logDebug(string $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    private function logInfo(string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    // -- EXISTING STATICS ---------------------------------------------------

    public static function diffAssocRecursive(array $a, array $b): array
    {
        $diff = [];
        $keys = array_unique(array_merge(array_keys($a), array_keys($b)));
        foreach ($keys as $k) {
            $v1 = $a[$k] ?? null;
            $v2 = $b[$k] ?? null;
            if (is_array($v1) && is_array($v2)) {
                $sub = self::diffAssocRecursive($v1, $v2);
                if ($sub) {
                    $diff[$k] = $sub;
                }
            } elseif (self::valuesDiffer($v1, $v2)) {
                $diff[$k] = ['old' => $v1, 'new' => $v2];
            }
        }
        return $diff;
    }

    private static function valuesDiffer($v1, $v2): bool
    {
        if (is_numeric($v1) && is_numeric($v2)) {
            return abs((float)$v1 - (float)$v2) > 0.0001;
        }
        return (string) $v1 !== (string) $v2;
    }

    public static function normalizeTransactionRow(array &$row): void
    {
        foreach (['due_date', 'org_due_date'] as $fld) {
            if (!empty($row[$fld])) {
                $row[$fld] = substr((string) $row[$fld], 0, 10);
            }
        }
        foreach (['vat_base', 'total_net', 'curr_price', 'pc_price', 'rc_xrate', 'excline_price', 'excline_total', 'excline_vat_matrah', 'excline_line_net', 'edt_price', 'edt_curr', 'org_price'] as $fld) {
            if (isset($row[$fld])) {
                $row[$fld] = number_format((float) $row[$fld], 2, '.', '');
            }
        }
    }
}
