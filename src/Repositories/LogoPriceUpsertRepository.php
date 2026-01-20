<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use PDOException;

/**
 * Upsert repository for LG_xxx_PRCLIST tabloları.
 *
 *  • Önce UPDATE dener; satır yoksa INSERT yapar.
 *  • UOMREF’i dinamik olarak ilgili prefix tablosundan sorguluyoruz.
 *  • INSERT sonrası LG_<prefix>_PRCLSTDIV tablosuna da bir detay satırı ekler.
 */
class LogoPriceUpsertRepository
{
    private PDO   $db;
    private mixed $logger;
    private int   $defaultPType;

    public function __construct(PDO $db, $logger, int $defaultPType = 2)
    {
        $this->db           = $db;
        $this->logger       = $logger;
        $this->defaultPType = $defaultPType;
    }

    /**
     * @return array{success:bool,action:string,newLogicalRef:int|null,error:string}
     */
    public function upsertPrice(
        string $table,
        int    $cardRef,
        float  $price,
        string $cyphCode = ''
    ): array {
        $allowed = ['LG_566_PRCLIST', 'LG_526_PRCLIST'];
        if (!in_array($table, $allowed, true)) {
            throw new \InvalidArgumentException("Invalid table: $table");
        }

        // prefix'i buradan çıkarıyoruz ("565" veya "525")
        $prefix = substr($table, 3, 3);

        try {
            // 1) UPDATE denemesi
            $tradingGrp = $cyphCode === 'EXPORT' ? 'YD' : null;
            $updateSql  = "
            UPDATE {$table}
                SET PRICE = :price";
            if ($tradingGrp !== null) {
                $updateSql .= ", TRADINGGRP = :tradingGrp";
            }
            $updateSql .= "
                WHERE CARDREF = :cardRef
                    AND PTYPE   = :ptype
                    AND (CYPHCODE = :cyph1 OR (CYPHCODE IS NULL AND :cyph2 = ''))
            ";
            $upd = $this->db->prepare($updateSql);
            $params = [
                'price'   => $price,
                'cardRef' => $cardRef,
                'ptype'   => $this->defaultPType,
                'cyph1'   => $cyphCode,
                'cyph2'   => $cyphCode,
            ];
            if ($tradingGrp !== null) {
                $params['tradingGrp'] = $tradingGrp;
            }
            $upd->execute($params);

            if ($upd->rowCount() > 0) {
                $logicalRef = (int)$this->db->query("
                    SELECT LOGICALREF
                      FROM {$table}
                     WHERE CARDREF = {$cardRef}
                       AND PTYPE   = {$this->defaultPType}
                       AND (CYPHCODE = '{$cyphCode}' OR (CYPHCODE IS NULL AND '{$cyphCode}' = ''))
                ")->fetchColumn();

                // CODE boşsa doldur
                $this->db->exec("
                    UPDATE {$table}
                       SET CODE = CAST(LOGICALREF AS VARCHAR(25))
                     WHERE LOGICALREF = {$logicalRef} AND CODE IS NULL
                ");

                return [
                    'success'       => true,
                    'action'        => 'updated',
                    'newLogicalRef' => $logicalRef,
                    'error'         => '',
                ];
            }

            // 2) INSERT – önce UOMREF’i dinamik alıyoruz
            $uomRefStmt = $this->db->prepare("
                SELECT TOP 1 L.LOGICALREF
                  FROM LG_{$prefix}_UNITSETL AS L
                  JOIN LG_{$prefix}_ITEMS    AS I
                    ON I.UNITSETREF = L.UNITSETREF
                 WHERE I.LOGICALREF = :cardRef
                   AND L.LINENR     = 1
            ");
            $uomRefStmt->execute([':cardRef' => $cardRef]);
            $uomRef = (int)$uomRefStmt->fetchColumn();

            // 3) Asıl PRCLIST INSERT’i
            $insert = $this->db->prepare("
                INSERT INTO {$table} (
                    CARDREF, PAYPLANREF, PRICE, UOMREF,
                    INCVAT, CURRENCY, PRIORITY, PTYPE,
                    MTRLTYPE, LEADTIME, BEGDATE, ENDDATE,
                    CONDITION, SHIPTYP, SPECIALIZED,
                    CAPIBLOCK_CREATEDBY, CAPIBLOCK_CREADEDDATE,
                    CAPIBLOCK_CREATEDHOUR, CAPIBLOCK_CREATEDMIN, CAPIBLOCK_CREATEDSEC,
                    CAPIBLOCK_MODIFIEDBY, CAPIBLOCK_MODIFIEDDATE,
                    CAPIBLOCK_MODIFIEDHOUR, CAPIBLOCK_MODIFIEDMIN, CAPIBLOCK_MODIFIEDSEC,
                    SITEID, RECSTATUS, ORGLOGICREF, WFSTATUS,
                    UNITCONVERT, EXTACCESSFLAGS, CYPHCODE, ORGLOGOID,
                    TRADINGGRP, BEGTIME, ENDTIME, DEFINITION_,
                    GRPCODE, ORDERNR,
                    GENIUSPAYTYPE, GENIUSSHPNR,
                    PRCALTERTYP1, PRCALTERLMT1,
                    PRCALTERTYP2, PRCALTERLMT2,
                    PRCALTERTYP3, PRCALTERLMT3,
                    ACTIVE, PURCHCONTREF, BRANCH, COSTVAL,
                    CLTRADINGGRP, CLCYPHCODE,
                    CLSPECODE2, CLSPECODE3, CLSPECODE4, CLSPECODE5,
                    GLOBALID, VARIANTCODE, WFLOWCRDREF, GUID,
                    PROJECTREF, MARKREF, TRSPECODE, NOTIFYCRDREF
                ) OUTPUT INSERTED.LOGICALREF
                VALUES (
                    :cardRef, 0, :price, :uomRef,
                    0, 20, 0, :ptype,
                    0, 0, :begDate, :endDate,
                    '', '', 0,
                    1, :begDateCreated,
                    11, 32, 27,
                    30, :begDateModified,
                    0, 0, 0,
                    0, 0, 0, 0,
                    0, 0, :cyph, NULL,
                    :tradingGrp, 0, 0, '',
                    '', '0',
                    NULL, '0',
                    0, 0,
                    0, 0,
                    0, 0,
                    0, 0, -1, 0,
                    NULL, '',
                    '', '', '', '',
                    NULL, '', 0, NULL,
                    0, 0, '', 0
                )
            ");

            $begDate = date('Ymd');
            $endDate = date('Ymd', strtotime('+1 year'));

            $insertTradingGrp = $cyphCode === 'EXPORT' ? 'YD' : '';

            $insert->execute([
                'cardRef'         => $cardRef,
                'price'           => $price,
                'uomRef'          => $uomRef,
                'ptype'           => $this->defaultPType,
                'begDate'         => $begDate,
                'endDate'         => $endDate,
                'begDateCreated'  => $begDate,
                'begDateModified' => $begDate,
                'cyph'            => $cyphCode,
                'tradingGrp'      => $insertTradingGrp,
            ]);

            $newLogicalRef = (int)$insert->fetchColumn();

            // 4) CODE alanını doldur
            $this->db->exec("
                UPDATE {$table}
                   SET CODE = CAST(LOGICALREF AS VARCHAR(25))
                 WHERE LOGICALREF = {$newLogicalRef} AND CODE IS NULL
            ");

            // 5) Detay tablosuna da insert et
            $detailTable = "LG_{$prefix}_PRCLSTDIV";
            $divIns = $this->db->prepare("
                INSERT INTO {$detailTable} (PARENTPRCREF, DIVCODES)
                VALUES (:parentRef, -1)
            ");
            $divIns->execute([':parentRef' => $newLogicalRef]);

            return [
                'success'       => true,
                'action'        => 'inserted',
                'newLogicalRef' => $newLogicalRef,
                'error'         => '',
            ];
        } catch (PDOException $e) {
            if (method_exists($this->logger, 'error')) {
                $this->logger->error("Upsert error on {$table}: " . $e->getMessage());
            } else {
                $this->logger->log("ERROR", "Upsert error on {$table}: " . $e->getMessage());
            }
            return [
                'success'       => false,
                'action'        => 'error',
                'newLogicalRef' => null,
                'error'         => $e->getMessage(),
            ];
        }
    }
}
