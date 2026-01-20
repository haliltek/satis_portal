<?php
// classes/DatabaseManager.php

declare(strict_types=1);

namespace Proje;

use RuntimeException;
use App\Models\SalesOrderMap;

class DatabaseManager
{
    private \mysqli $conn;

    public function __construct(array $dbConfig)
    {
        $port = isset($dbConfig['port']) ? (int) $dbConfig['port'] : 3306;

        $this->conn = new \mysqli(
            $dbConfig['host'],
            $dbConfig['user'],
            $dbConfig['pass'],
            $dbConfig['name'],
            $port
        );

        if ($this->conn->connect_error) {
            throw new RuntimeException('DB bağlantı hatası: ' . $this->conn->connect_error);
        }
        $this->conn->set_charset('utf8mb4');
    }

    public function getConnection(): \mysqli
    {
        return $this->conn;
    }

    public function __destruct()
    {
        $this->conn->close();
    }

    public function getCompanyInfo(string $sirketCode): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM sirket WHERE s_arp_code = ?");
        $stmt->bind_param("s", $sirketCode);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
        return $res;
    }

    public function getCompanyInfoById(int $sirketId): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM sirket WHERE sirket_id = ?");
        $stmt->bind_param("i", $sirketId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
        return $res;
    }

    /**
     * Checks if a company record exists for the given ARP code.
     */
    public function companyExists(string $arpCode): bool
    {
        $stmt = $this->conn->prepare('SELECT sirket_id FROM sirket WHERE s_arp_code = ? LIMIT 1');
        $stmt->bind_param('s', $arpCode);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    /**
     * Inserts a company with arbitrary column set.
     *
     * Provided array keys must match column names in the `sirket` table.
     * Additional columns will automatically be included in the query.
     */
    public function insertCompany(array $data): bool
    {
        $columns = array_keys($data);
        $placeholders = rtrim(str_repeat('?,', count($columns)), ',');
        $sql = 'INSERT INTO sirket (' . implode(',', $columns) . ') VALUES (' . $placeholders . ')';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $types = str_repeat('s', count($columns));
        $stmt->bind_param($types, ...array_values($data));
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * Updates an existing company by ARP code.
     *
     * Array keys should match column names to be updated.
     */
    public function updateCompany(string $arpCode, array $data): bool
    {
        $columns = array_keys($data);
        $sets = implode('=?, ', $columns) . '=?';
        $sql = 'UPDATE sirket SET ' . $sets . ' WHERE s_arp_code=?';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $types = str_repeat('s', count($columns) + 1);
        $values = array_values($data);
        $values[] = $arpCode;
        $stmt->bind_param($types, ...$values);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * Updates an existing company by internal reference.
     *
     * Array keys should match column names to be updated.
     */
    public function updateCompanyByRef(int $ref, array $data): bool
    {
        $columns = array_keys($data);
        $sets = implode('=?, ', $columns) . '=?';
        $sql = 'UPDATE sirket SET ' . $sets . ' WHERE internal_reference=?';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $types = str_repeat('s', count($columns) + 1);
        $values = array_values($data);
        $values[] = $ref;
        $stmt->bind_param($types, ...$values);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * Bulk upsert for multiple companies using ON DUPLICATE KEY UPDATE.
     *
     * @param array<int,array<string,mixed>> $rows
     * @param int $chunkSize Number of rows per statement
     * @return int Number of affected rows
     */
    public function upsertCompanies(array $rows, int $chunkSize = 500): int
    {
        if (!$rows) {
            return 0;
        }

        $columns = array_keys(reset($rows));
        $colList = implode(',', $columns);
        $placeholderRow = '(' . rtrim(str_repeat('?,', count($columns)), ',') . ')';
        $updateList = [];
        foreach ($columns as $col) {
            $updateList[] = "$col=VALUES($col)";
        }
        $updateClause = implode(',', $updateList);
        $affected = 0;

        $chunks = array_chunk($rows, $chunkSize);
        foreach ($chunks as $chunk) {
            $placeholders = array_fill(0, count($chunk), $placeholderRow);
            $sql = 'INSERT INTO sirket (' . $colList . ') VALUES ' .
                implode(',', $placeholders) .
                ' ON DUPLICATE KEY UPDATE ' . $updateClause;
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                continue;
            }
            $types = str_repeat('s', count($chunk) * count($columns));
            $params = [];
            foreach ($chunk as $row) {
                foreach ($columns as $c) {
                    $params[] = $row[$c];
                }
            }
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $affected += $stmt->affected_rows;
            $stmt->close();
        }

        return $affected;
    }

    public function getPersonInfo(int $personelId): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM personel WHERE personel_id = ?");
        $stmt->bind_param("i", $personelId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
        return $res;
    }

    public function getProductInfo(int $urunId): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM urunler WHERE urun_id = ?");
        $stmt->bind_param("i", $urunId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
        return $res;
    }

    public function getOffer(int $teklifId): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM ogteklif2 WHERE id = ?");
        $stmt->bind_param("i", $teklifId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
        return $res;
    }

    public function getManagerProfile(int $managerId): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM yonetici WHERE yonetici_id = ?");
        $stmt->bind_param("i", $managerId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
        return $res;
    }

    public function getMaxDiscount(int $yoneticiId): float
    {
        $stmt = $this->conn->prepare("SELECT iskonto_max FROM yonetici WHERE yonetici_id = ?");
        $stmt->bind_param("i", $yoneticiId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ? floatval($row['iskonto_max']) : 0.0;
    }

    /**
     * Kullanıcının en son kullandığı sipariş başlık seçimlerini döner.
     *
     * @param int $yoneticiId
     * @return array<string,mixed>
     */
    public function getHeaderPrefs(int $yoneticiId): array
    {
        $stmt = $this->conn->prepare(
            'SELECT pref_auxil_code, pref_division, pref_department, pref_source_wh, pref_factory, pref_salesmanref
             FROM yonetici WHERE yonetici_id = ?'
        );
        $stmt->bind_param('i', $yoneticiId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();
        return $res;
    }

    /**
     * Kullanıcının sipariş başlık tercihlerini günceller.
     */
    public function saveHeaderPrefs(
        int $yoneticiId,
        string $auxilCode,
        int $division,
        int $department,
        int $sourceWh,
        string $factory,
        int $salesmanRef
    ): bool {
        $stmt = $this->conn->prepare(
            'UPDATE yonetici SET
                pref_auxil_code = ?,
                pref_division = ?,
                pref_department = ?,
                pref_source_wh = ?,
                pref_factory = ?,
                pref_salesmanref = ?
             WHERE yonetici_id = ?'
        );
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            'siiisii',
            $auxilCode,
            $division,
            $department,
            $sourceWh,
            $factory,
            $salesmanRef,
            $yoneticiId
        );

        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function getOfferItems(int $teklifId): array
    {
        $stmt = $this->conn->prepare("SELECT * FROM ogteklifurun2 WHERE teklifid = ?");
        $stmt->bind_param("i", $teklifId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $res;
    }

    /**
     * Teklif başlığını, API’dan map’lenmiş DB sütun adlarıyla döner.
     * @return array<string,mixed>  // örn ['internal_reference'=>…, 'tekliftarihi'=>…, …]
     */
    public function getOfferHeaderMapped(int $teklifId): array
    {
        $raw = $this->getOffer($teklifId) ?: [];
        $dbFields = array_values(SalesOrderMap::$header);  // DB sütun adları
        $out = [];
        foreach ($dbFields as $col) {
            $out[$col] = $raw[$col] ?? null;
        }
        return $out;
    }

    /**
     * Teklif kalemlerini, API’dan map’lenmiş DB sütun adlarıyla döner.
     * Key olarak önce internal_reference, sonra kod, en son da id kullanır.
     *
     * @return array<string,array<string,mixed>>
     */
    public function getOfferItemsMapped(int $teklifId): array
    {
        // 1) Ham DB satırlarını çek
        $rows = $this->getOfferItems($teklifId);
        // 2) Map’lenmiş DB sütun adlarını al (SalesOrderMap::$transaction içindekiler)
        $dbFields = array_values(SalesOrderMap::$transaction);
        $out = [];

        foreach ($rows as $r) {
            // 3) Anahtar atama: internal_reference → kod → fallback olarak "db_id_<id>"
            if (!empty($r['internal_reference'])) {
                $key = (string) $r['internal_reference'];
            } elseif (!empty($r['kod'])) {
                $key = (string) $r['kod'];
            } else {
                $key = 'db_id_' . $r['id'];
            }

            // 4) Filtrelenmiş satır: önce id, sonra transaction alanları
            $filtered = ['id' => $r['id']];
            foreach ($dbFields as $col) {
                $filtered[$col] = $r[$col] ?? null;
            }

            $out[$key] = $filtered;
        }

        return $out;
    }


    /**
     * Güncelleme işlemi yapar ve yeni net/unit fiyat ile toplam tutarı da döner.
     * @return array{netFiyat: float, toplam: float}|false
     */
    public function updateOfferItem(int $itemId, int $miktar, string $birim, float $iskonto)
    {
        // 1) Mevcut liste fiyatını al
        $stmt = $this->conn->prepare("SELECT liste FROM ogteklifurun2 WHERE id = ?");
        $stmt->bind_param("i", $itemId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return false;
        }

        $liste = floatval($row['liste']);
        $netFiyat = $liste * (1 - $iskonto / 100);
        $toplam = $netFiyat * $miktar;

        // 2) Güncelle
        $upd = $this->conn->prepare("
            UPDATE ogteklifurun2
                SET miktar   = ?,
                    birim    = ?,
                    iskonto  = ?,
                    nettutar = ?,
                    tutar    = ?
                WHERE id = ?
        ");
        $upd->bind_param("isdddi", $miktar, $birim, $iskonto, $netFiyat, $toplam, $itemId);
        $ok = $upd->execute();
        $upd->close();

        if (!$ok) {
            return false;
        }

        return [
            'netFiyat' => $netFiyat,
            'toplam' => $toplam,
        ];
    }

    public function deleteOfferItem(int $itemId): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM ogteklifurun2 WHERE id = ?");
        $stmt->bind_param("i", $itemId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function logAction(string $action, int $personel, int $time, string $status): bool
    {
        $stmt = $this->conn->prepare("
            INSERT INTO log_yonetim (islem, personel, tarih, durum)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("siss", $action, $personel, $time, $status);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * Manual Logo bilgilerini günceller.
     *
     * @param int    $teklifId
     * @param string $vatexceptCode
     * @param string $vatexceptReason
     * @param string $auxilCode
     * @param string $authCode
     * @param string $notes
     * @param int    $division
     * @param int    $department
     * @param int    $sourceWh
     * @param int    $sourceCostGrp
     * @param string $salesmanCode
     * @param int    $salesmanRef
     * @param string $tradingGrp
     * @param string $paymentCode
     * @param int    $paydefRef
     * @param string $docNumber
     * @param int    $orderStatus
     * @return bool
     */
    public function updateLogoInfo(
        int $teklifId,
        string $vatexceptCode,
        string $vatexceptReason,
        string $auxilCode,
        string $authCode,
        string $notes,
        int $division,
        int $department,
        int $sourceWh,
        int $sourceCostGrp,
        string $factory,
        string $salesmanCode,
        int $salesmanRef,
        string $tradingGrp,
        string $paymentCode,
        int $paydefRef,
        string $docNumber,
        int $orderStatus,
        int $sozlesmeId,
    ): bool {
        $sql = "
        UPDATE ogteklif2
            SET vatexcept_code    = ?,
                vatexcept_reason  = ?,
                auxil_code        = ?,
                auth_code         = ?,
                notes1            = ?,
                division          = ?,
                department        = ?,
                source_wh         = ?,
                source_cost_grp   = ?,
                factory           = ?,
                salesman_code     = ?,
                salesmanref       = ?,
                trading_grp       = ?,
                payment_code      = ?,
                paydefref         = ?,
                doc_number        = ?,
                order_status      = ?,
                sozlesme_id       = ?
            WHERE id = ?
        ";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            // 5s    + 5i      + s s i s s i s s i i
            "sssssiiiiisissisiii",
            $vatexceptCode,   // s
            $vatexceptReason, // s
            $auxilCode,       // s
            $authCode,        // s
            $notes,           // s
            $division,        // i
            $department,      // i
            $sourceWh,        // i
            $sourceCostGrp,   // i
            $factory,         // i
            $salesmanCode,    // s
            $salesmanRef,     // i
            $tradingGrp,      // s
            $paymentCode,     // s
            $paydefRef,       // i
            $docNumber,       // s
            $orderStatus,     // i
            $sozlesmeId,      // i
            $teklifId         // i (WHERE)
        );

        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function updateOfferHeader(array $data): bool
    {
        // data anahtarları SalesOrderMap::$header karşılığı: internal_reference, number, tekliftarihi, …
        $columns = $this->getOfferColumns();
        $sets    = [];
        $types   = '';
        $values  = [];
        $missing = [];
        foreach ($data as $col => $val) {
            if ($col === 'id') {
                continue;
            }
            if (!in_array($col, $columns, true)) {
                $missing[] = $col;
                continue;
            }
            $sets[]  = "`$col` = ?";
            $types  .= 's';
            $values[] = $val;
        }

        if ($missing) {
            error_log('[DatabaseManager] Missing columns in ogteklif2: ' . implode(',', $missing) . "\n", 3, __DIR__ . '/../debug.log');
        }

        if (empty($sets)) {
            return true; // güncellenecek bir şey yok
        }

        $sql = "UPDATE ogteklif2 SET " . implode(', ', $sets) . " WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $types .= 'i';
        $values[] = $data['id'];
        $stmt->bind_param($types, ...$values);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    private function getOfferColumns(): array
    {
        static $cols = null;
        if ($cols === null) {
            $cols = [];
            if ($result = $this->conn->query('SHOW COLUMNS FROM ogteklif2')) {
                while ($row = $result->fetch_assoc()) {
                    $cols[] = $row['Field'];
                }
                $result->close();
            }
        }
        return $cols;
    }

    public function deleteDiscountLines(int $teklifId): bool
    {
        $sql = "DELETE FROM ogteklifurun2 WHERE teklifid = ? AND transaction_type = 2";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $teklifId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * Yeni bir indirim satırı ekler.
     */
    public function insertDiscountLine(
        int     $teklifId,
        ?int    $parentInternalRef,
        float   $rate,
        int     $newInternalRef,
        int     $ordficheref    = 0,
        float   $miktar         = 0,
        float   $liste          = 0,
        float   $vat_base       = 0,
        float   $total_net      = 0,
        int     $unit_conv1     = 1,
        int     $unit_conv2     = 1,
        int     $data_reference = 0,
        ?string $guid           = null,
        int     $eu_vat_status  = 0,
        int     $multi_add_tax  = 0,
        int     $affect_risk    = 0,
        float   $org_quantity   = 0
    ): bool {
        $sql = <<<SQL
        INSERT INTO ogteklifurun2 (
            teklifid,
            transaction_type,
            parent_internal_reference,
            iskonto,
            internal_reference,
            ordficheref,
            miktar,
            liste,
            vat_base,
            total_net,
            unit_conv1,
            unit_conv2,
            data_reference,
            guid,
            eu_vat_status,
            multi_add_tax,
            affect_risk,
            org_quantity
        ) VALUES (
            ?, 2, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
        SQL;
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "iidiiddddiiisiiid",
            $teklifId,
            $parentInternalRef,
            $rate,
            $newInternalRef,
            $ordficheref,
            $miktar,
            $liste,
            $vat_base,
            $total_net,
            $unit_conv1,
            $unit_conv2,
            $data_reference,
            $guid,
            $eu_vat_status,
            $multi_add_tax,
            $affect_risk,
            $org_quantity
        );
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function updateLogoTransferStatus(int $orderId, string $status, bool $setDates = false): bool
    {
        if ($setDates) {
            $sql = "
                UPDATE ogteklif2
                    SET logo_transfer_status   = ?,
                        logo_transfer_date     = NOW(),
                        last_logo_update_date  = NOW()
                    WHERE id = ?
            ";
        } else {
            $sql = "
                UPDATE ogteklif2
                    SET logo_transfer_status  = ?,
                        last_logo_update_date = NOW()
                    WHERE id = ?
            ";
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $status, $orderId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function setInternalReference(int $orderId, int $internalRef): bool
    {
        $stmt = $this->conn->prepare("
            UPDATE ogteklif2
                SET internal_reference    = ?,
                    logo_transfer_status  = 'Aktarıldı',
                    last_logo_update_date = NOW()
                WHERE id = ?
        ");
        $stmt->bind_param("ii", $internalRef, $orderId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function updateProductInternalReference(int $teklifId, string $kod, int $productInternalRef): bool
    {
        $stmt = $this->conn->prepare("
            UPDATE ogteklifurun2
                SET internal_reference = ?
                WHERE teklifid = ? AND kod = ?
        ");
        $stmt->bind_param("iis", $productInternalRef, $teklifId, $kod);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * Logo’ya aktarım işlemi başlatıldığında siparişi “Aktarılıyor” durumuna geçirir.
     */
    public function markLogoTransferring(int $orderId): bool
    {
        $sql = "
            UPDATE ogteklif2
                SET logo_transfer_status   = 'Aktarılıyor',
                    logo_transfer_date     = NOW(),
                    last_logo_update_date  = NOW()
                WHERE id = ?
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $orderId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * Logo API hata döndürdüğünde sipariş durumunu “Hata” olarak ayarlar.
     */
    public function markLogoError(int $orderId): bool
    {
        $sql = "
            UPDATE ogteklif2
                SET logo_transfer_status   = 'Hata',
                    logo_transfer_date     = NULL,
                    last_logo_update_date  = NULL
                WHERE id = ?
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $orderId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * Logo API başarıyla dönünce internal_reference ve durumu günceller.
     */
    public function setLogoSuccess(int $orderId, int $internalRef, string $logoNumberVal): bool
    {
        $sql = "
            UPDATE ogteklif2
                SET internal_reference    = ?,
                    number = ?,
                    logo_transfer_status  = 'Aktarıldı',
                    last_logo_update_date = NOW(),
                    logo_transfer_date = NOW()
                WHERE id = ?
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("isi", $internalRef, $logoNumberVal, $orderId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * Birden fazla ürün satırına ait internal_reference değerlerini topluca günceller.
     * @param int   $orderId
     * @param array $items  // her eleman ['kod'=>string, 'internal'=>int]
     */
    public function updateProductsInternalRefs(int $orderId, array $items): void
    {
        // 1) Eksik kolonları buraya ekleyin
        $sql = "
        UPDATE ogteklifurun2
            SET 
                internal_reference = ?,
                ordficheref        = ?,
                liste               = ?,
                vat_base            = ?,
                total_net           = ?,
                unit_conv1          = ?,
                unit_conv2          = ?,
                data_reference      = ?,
                eu_vat_status       = ?,
                multi_add_tax       = ?,
                affect_risk         = ?,
                org_quantity        = ?,
                guid                = ?
            WHERE teklifid = ? AND kod = ?
        ";
        if (! $stmt = $this->conn->prepare($sql)) {
            throw new RuntimeException("Prepare failed: " . $this->conn->error);
        }
        foreach ($items as $it) {
            $stmt->bind_param(
                "iisddiiiiiiisis",     // tip sırası: i=int, d=float, s=string
                $it['internal'],        // i
                $it['ordficheref'],    // i
                $it['liste'],                 // s
                $it['vat_base'],              // d
                $it['total_net'],             // d
                $it['unit_conv1'],            // i
                $it['unit_conv2'],            // i
                $it['data_reference'],        // i
                $it['eu_vat_status'],         // i
                $it['multi_add_tax'],         // i
                $it['affect_risk'],           // i
                $it['org_quantity'],          // i
                $it['guid'],                  // s
                $orderId,                     // i
                $it['kod']                    // s
            );
            if (! $stmt->execute()) {
                // hata logu
                error_log("updateProductsInternalRefs failed: " . $stmt->error);
            }
        }
        $stmt->close();
    }

    /**
     * Bir ürün satırına bağlı yeni bir indirim satırı ekler.
     *
     * @param int    $teklifId     Teklif (sipariş) ID
     * @param int    $parentRowId  Ana ürün satırının PK’si (ogteklifurun2.id)
     * @param float  $rate         İskonto oranı (%)
     * @param string $description  İndirim satırı açıklaması (TRANS_DESCRIPTION)
     * @return bool                Başarılıysa true, değilse false
     */
    public function addOfferItemDiscount(int $teklifId, int $parentRowId, float $rate, string $description): bool
    {
        // 1) Parent satırdan sadece internal_reference alıyoruz
        $stmt = $this->conn->prepare("
            SELECT internal_reference
                    FROM ogteklifurun2
                WHERE id = ?
        ");
        $stmt->bind_param("i", $parentRowId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            // parent bulunamadıysa false dön
            return false;
        }

        $parentRef = (int) $row['internal_reference'];

        // 2) Yalnızca gerekli sütunlarla indirim satırını ekleyelim
        $stmt = $this->conn->prepare("
            INSERT INTO ogteklifurun2
                (teklifid, transaction_type, parent_internal_reference, adi, iskonto)
            VALUES
                (?,            ?,                ?,                         ?,     ?)
        ");
        $type = 2; // 2 = indirim satırı
        $stmt->bind_param(
            "iiisd",
            $teklifId,     // teklifid
            $type,         // transaction_type
            $parentRef,    // parent_internal_reference
            $description,  // adi (TRANS_DESCRIPTION)
            $rate          // iskonto (DISCOUNT_RATE)
        );

        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    /**
     * Teklifin geneline (header) bağlı yeni bir indirim satırı ekler.
     *
     * @param int    $teklifId    Teklif ID
     * @param float  $rate        İskonto oranı (%)
     * @param string $description İndirim açıklaması
     * @return bool               Başarılıysa true
     */
    public function addOfferTotalDiscount(int $teklifId, float $rate, string $description): bool
    {
        // 1) INSERT hazırlığı
        $stmt = $this->conn->prepare("
        INSERT INTO ogteklifurun2
            (teklifid, transaction_type, adi, iskonto)
        VALUES
            (?,            ?,                ?,     ?)
    ");
        $type = 2;
        $stmt->bind_param(
            "iisd",
            $teklifId,    // teklifid
            $type,        // transaction_type
            $description, // adi (TRANS_DESCRIPTION)
            $rate         // iskonto (DISCOUNT_RATE)
        );

        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * Günceller: miktar, birim, iskonto; ve
     * nettutar ile tutar sütunlarını da hesaplayıp yazar.
     */

    public function updateOfferItemAndTotals(int $id, float $miktar, string $birim, float $iskonto): bool
    {
        // 1) Önce liste fiyatını çek
        $sql0 = "SELECT liste FROM ogteklifurun2 WHERE id = ?";
        $stmt0 = $this->conn->prepare($sql0);
        $stmt0->bind_param("i", $id);
        $stmt0->execute();
        $liste = (float) $stmt0->get_result()->fetch_assoc()['liste'];
        $stmt0->close();

        // 2) Yeni net fiyat ve toplamı hesapla
        $netUnit = $liste * (1 - $iskonto / 100);
        $rowTotal = $netUnit * $miktar;

        // 3) Güncelle
        $sql = "
            UPDATE ogteklifurun2
            SET miktar   = ?,
                birim    = ?,
                iskonto  = ?,
                nettutar = ?,
                tutar    = ?
            WHERE id      = ?
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("isdddi", $miktar, $birim, $iskonto, $netUnit, $rowTotal, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * Verilen teklifId için subtotal, kdv ve total değerlerini hesaplayıp döner.
     * @return array{subtotal: float, kdv: float, total: float}
     */
    public function getOrderTotals(int $teklifId): array
    {
        // 1) Döviz kurlarını çek
        $stmt = $this->conn->prepare("SELECT dolarkur, eurokur FROM ogteklif2 WHERE id = ?");
        $stmt->bind_param("i", $teklifId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();

        $dolarkuru = isset($row['dolarkur']) ? (float) str_replace(',', '.', $row['dolarkur']) : 0;
        $eurokuru = isset($row['eurokur']) ? (float) str_replace(',', '.', $row['eurokur']) : 0;

        // 2) Her döviz için toplam tutar
        $stmt = $this->conn->prepare("
        SELECT doviz, SUM(miktar * nettutar) AS toplam
                    FROM ogteklifurun2
                WHERE teklifid = ?
                GROUP BY doviz
        ");
        $stmt->bind_param("i", $teklifId);
        $stmt->execute();
        $res = $stmt->get_result();
        $totTL = $totEUR = $totUSD = 0;
        while ($r = $res->fetch_assoc()) {
            switch ($r['doviz']) {
                case 'TL':
                    $totTL = (float) $r['toplam'];
                    break;
                case 'EUR':
                    $totEUR = (float) $r['toplam'];
                    break;
                case 'USD':
                    $totUSD = (float) $r['toplam'];
                    break;
            }
        }
        $stmt->close();

        $convEUR = $totEUR * $eurokuru;
        $convUSD = $totUSD * $dolarkuru;
        $subtotal = $totTL + $convEUR + $convUSD;
        $kdv = $subtotal * 0.20;
        $total = $subtotal + $kdv;

        return [
            'subtotal' => $subtotal,
            'kdv' => $kdv,
            'total' => $total,
        ];
    }

    public function getContracts(): array
    {
        $res = $this->conn->query("SELECT * FROM sozlesmeler ORDER BY sozlesmeadi");
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getContractById(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM sozlesmeler WHERE sozlesme_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    // ---------------------------------------------------------------------
    // B2B USERS

    public function createB2bUser(
        int $companyId,
        ?string $cariCode,
        string $username,
        string $email,
        string $password,
        int $status,
        string $role
    ): int {
        $stmt = $this->conn->prepare(
            "INSERT INTO b2b_users (company_id, cari_code, username, email, password, status, role) VALUES (?,?,?,?,?,?,?)"
        );
        $stmt->bind_param('issssis', $companyId, $cariCode, $username, $email, $password, $status, $role);
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    public function getB2bUserById(int $id): ?array
    {
        $stmt = $this->conn->prepare('SELECT * FROM b2b_users WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function getB2bUserByEmail(string $email): ?array
    {
        $stmt = $this->conn->prepare('SELECT * FROM b2b_users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /**
     * Resolves preparer information from either dealer or manager tables.
     *
     * @param string $id Raw preparer id from orders
     * @return array{name:string,source:string}
     */
    public function resolvePreparer(string $id): array
    {
        $out = ['name' => '', 'email' => '', 'source' => 'Gemas'];
        $num = (int)preg_replace('/\D+/', '', $id);
        if ($num > 0) {
            $row = $this->getB2bUserById($num);
            if ($row) {
                $out['name'] = $row['username'] ?? '';
                $out['email'] = $row['email'] ?? '';
                $out['source'] = 'Bayi';
                return $out;
            }
            $mgr = $this->getManagerProfile($num);
            if ($mgr) {
                $out['name'] = $mgr['adsoyad'] ?? '';
                $out['email'] = $mgr['mailposta'] ?? ($mgr['eposta'] ?? '');
            }
        }
        return $out;
    }

    public function updateB2bUser(int $id, array $data): bool
    {
        $fields = [];
        $types  = '';
        $values = [];

        $map = [
            'company_id' => 'i',
            'cari_code'  => 's',
            'username'   => 's',
            'email'      => 's',
            'password'   => 's',
            'status'     => 'i',
            'role'       => 's',
        ];

        foreach ($map as $col => $type) {
            if (array_key_exists($col, $data)) {
                $fields[] = "$col = ?";
                $types   .= $type;
                $values[] = $data[$col];
            }
        }

        if (!$fields) {
            return true;
        }

        $sql = 'UPDATE b2b_users SET ' . implode(', ', $fields) . ', updated_at = NOW() WHERE id = ?';
        $types .= 'i';
        $values[] = $id;

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function deleteB2bUser(int $id): bool
    {
        $stmt = $this->conn->prepare('DELETE FROM b2b_users WHERE id = ?');
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    // CAMPAIGNS
    public function createCampaign(array $data): int
    {
        $columns = array_keys($data);
        $placeholders = rtrim(str_repeat('?,', count($columns)), ',');
        $sql = 'INSERT INTO campaigns (' . implode(',', $columns) . ') VALUES (' . $placeholders . ')';
        $stmt = $this->conn->prepare($sql);
        $types = '';
        $values = [];
        foreach ($data as $v) {
            $types .= is_int($v) ? 'i' : (is_float($v) ? 'd' : 's');
            $values[] = $v;
        }
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    public function updateCampaign(int $id, array $data): bool
    {
        $cols = array_keys($data);
        if (!$cols) return true;
        $sets = implode('=?, ', $cols) . '=?';
        $sql = 'UPDATE campaigns SET ' . $sets . ' WHERE id = ?';
        $stmt = $this->conn->prepare($sql);
        $types = '';
        $values = [];
        foreach ($data as $v) {
            $types .= is_int($v) ? 'i' : (is_float($v) ? 'd' : 's');
            $values[] = $v;
        }
        $types .= 'i';
        $values[] = $id;
        $stmt->bind_param($types, ...$values);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function deleteCampaign(int $id): bool
    {
        $stmt = $this->conn->prepare('DELETE FROM campaigns WHERE id = ?');
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function getActiveCampaigns(): array
    {
        $today = date('Y-m-d');
        $stmt = $this->conn->prepare(
            'SELECT * FROM campaigns WHERE (start_date IS NULL OR start_date<=?) AND (end_date IS NULL OR end_date>=?)'
        );
        $stmt->bind_param('ss', $today, $today);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $res;
    }

    /**
     * Returns recent orders for a given company.
     *
     * @param int $companyId Company ID from `sirket` table
     * @param int $limit     Number of rows to fetch
     * @return array<int, array<string,mixed>>
     */
    public function getOrdersForCompany(int $companyId, int $limit = 20, ?string $status = null): array
    {
        $sql = 'SELECT id, teklifkodu, projeadi, tekliftarihi, durum, geneltoplam, tltutar, dolartutar, eurotutar FROM ogteklif2 WHERE sirketid = ?';
        if ($status !== null) {
            $sql .= ' AND durum = ?';
        }
        $sql .= ' ORDER BY id DESC LIMIT ?';
        $stmt = $this->conn->prepare($sql);
        if ($status !== null) {
            $stmt->bind_param('isi', $companyId, $status, $limit);
        } else {
            $stmt->bind_param('ii', $companyId, $limit);
        }
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /**
     * Returns paginated orders for a given company with optional filtering.
     */
    public function listOrdersForCompany(
        int $companyId,
        int $limit = 20,
        int $offset = 0,
        ?string $status = null,
        string $query = '',
        string $sort = 'id',
        string $dir = 'DESC',
        ?string $cariCode = null
    ): array {
        $allowedSort = ['id','tekliftarihi','durum','geneltoplam'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'id';
        }
        $dir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        // Hem sirketid hem de sirket_arp_code ile filtreleme yap (admin panelinden oluşturulan siparişler için)
        $sql = 'SELECT id, teklifkodu, projeadi, tekliftarihi, durum, geneltoplam, tltutar, dolartutar, eurotutar FROM ogteklif2 WHERE (sirketid = ?';
        $params = [$companyId];
        $types = 'i';
        
        // Eğer cariCode varsa, sirket_arp_code ile de filtrele
        if ($cariCode !== null && $cariCode !== '') {
            $sql .= ' OR sirket_arp_code = ?';
            $params[] = $cariCode;
            $types .= 's';
        }
        $sql .= ')';
        
        if ($status !== null && $status !== '') {
            $sql .= ' AND durum = ?';
            $params[] = $status;
            $types .= 's';
        }
        if ($query !== '') {
            $sql .= ' AND (teklifkodu LIKE ? OR projeadi LIKE ?)';
            $like = "%" . $query . "%";
            $params[] = $like;
            $params[] = $like;
            $types .= 'ss';
        }
        $sql .= " ORDER BY $sort $dir LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /**
     * Counts orders for a given company with optional filter.
     */
    public function countOrdersForCompany(int $companyId, ?string $status = null, string $query = '', ?string $cariCode = null): int
    {
        // Hem sirketid hem de sirket_arp_code ile filtreleme yap (admin panelinden oluşturulan siparişler için)
        $sql = 'SELECT COUNT(*) as c FROM ogteklif2 WHERE (sirketid = ?';
        $params = [$companyId];
        $types = 'i';
        
        // Eğer cariCode varsa, sirket_arp_code ile de filtrele
        if ($cariCode !== null && $cariCode !== '') {
            $sql .= ' OR sirket_arp_code = ?';
            $params[] = $cariCode;
            $types .= 's';
        }
        $sql .= ')';
        
        if ($status !== null && $status !== '') {
            $sql .= ' AND durum = ?';
            $params[] = $status;
            $types .= 's';
        }
        if ($query !== '') {
            $sql .= ' AND (teklifkodu LIKE ? OR projeadi LIKE ?)';
            $like = "%" . $query . "%";
            $params[] = $like;
            $params[] = $like;
            $types .= 'ss';
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $count = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
        $stmt->close();
        return $count;
    }

    public function getAllOrderStatuses(): array
    {
        $res = $this->conn->query('SELECT DISTINCT durum FROM ogteklif2 ORDER BY durum');
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Returns the active campaign for the given product reference if any.
     */
    public function getActiveCampaignForProduct(int $productRef): ?array
    {
        $today = date('Y-m-d');
        $stmt = $this->conn->prepare(
            'SELECT * FROM campaigns WHERE product_id = ? AND (start_date IS NULL OR start_date<=?) AND (end_date IS NULL OR end_date>=?) LIMIT 1'
        );
        $stmt->bind_param('iss', $productRef, $today, $today);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res ?: null;
    }

    /**
     * Convenience helper that returns the discount rate for a product if a campaign exists.
     */
    public function getCampaignDiscountForProduct(int $productRef): ?float
    {
        $c = $this->getActiveCampaignForProduct($productRef);
        return $c ? (float)$c['discount_rate'] : null;
    }
}
