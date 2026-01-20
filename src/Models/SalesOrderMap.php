<?php
// src/Models/SalesOrderMap.php

declare(strict_types=1);

namespace App\Models;

/**
 * API’den gelen SalesOrder verisini
 * local DB sütun adlarına çeviren mapping’ler.
 */
class SalesOrderMap
{
    /**
     * API → DB sütun adı eşlemesi (header kısmı)
     */
    public static array $header = [
        'INTERNAL_REFERENCE'   => 'internal_reference',
        'NUMBER'               => 'number',
        'DATE'                 => 'tekliftarihi',
        'AUXIL_CODE'           => 'auxil_code',
        'AUTH_CODE'            => 'auth_code',
        'ARP_CODE'             => 'sirket_arp_code',
        'SOURCE_WH'            => 'source_wh',
        'SOURCE_COST_GRP'      => 'source_cost_grp',
        'PAYMENT_CODE'         => 'payment_code',
        'PAYDEFREF'            => 'paydefref',
        'DIVISION'             => 'division',
        'DEPARTMENT'           => 'department',
        'SALESMAN_CODE'        => 'salesman_code',
        'SALESMANREF'          => 'salesmanref',
        'TRADING_GRP'          => 'trading_grp',
        'VATEXCEPT_CODE'       => 'vatexcept_code',
        'VATEXCEPT_REASON'     => 'vatexcept_reason',
        'NOTES1'               => 'notes1',
        'DOC_NUMBER'           => 'doc_number',
        'ORDER_STATUS'         => 'order_status', // 1=Öneri, 2=Sevkedilemez, 4=Sevkedilebilir
        'CURR_TRANSACTIN'      => 'curr_transactin',
        'RC_RATE'              => 'rc_rate',
        'RC_NET'               => 'rc_net',
        'TOTAL_DISCOUNTED'     => 'total_discounted',
        'TOTAL_VAT'            => 'total_vat',
        'TOTAL_GROSS'          => 'total_gross',
        'TOTAL_NET'            => 'total_net_header',
        'EXCHINFO_TOTAL_DISCOUNTED' => 'exchinfo_total_discounted',
        'EXCHINFO_TOTAL_VAT'  => 'exchinfo_total_vat',
        'EXCHINFO_GROSS_TOTAL'=> 'exchinfo_gross_total',
    ];

    /**
     * API → DB sütun adı eşlemesi (transactions kısmı)
     */
    public static array $transaction = [
        'INTERNAL_REFERENCE'   => 'internal_reference',
        'TYPE'                 => 'transaction_type',          // 0=ürün, 2=indirim
        'MASTER_CODE'          => 'kod',
        'TRANS_DESCRIPTION'    => 'trans_description',         // satır açıklaması
        'GL_CODE1'             => 'gl_code1',                  // indirim muhasebe kodu
        'PARENTLNREF'          => 'parent_internal_reference', // indirimin bağlı olduğu ürünün internal_reference’i
        'ORDFICHEREF'          => 'ordficheref',
        'QUANTITY'             => 'miktar',
        'PRICE'                => 'liste',
        'DISCOUNT_RATE'        => 'iskonto',
        'VAT_BASE'             => 'vat_base',
        'TOTAL_NET'            => 'total_net',
        'UNIT_CODE'            => 'birim',
        'UNIT_CONV1'           => 'unit_conv1',
        'UNIT_CONV2'           => 'unit_conv2',
        'DUE_DATE'             => 'due_date',
        'CURR_PRICE'           => 'curr_price',
        'PC_PRICE'             => 'pc_price',
        'RC_XRATE'             => 'rc_xrate',
        'DATA_REFERENCE'       => 'data_reference',
        'SALESMAN_CODE'        => 'salesman_code',
        'CURR_TRANSACTIN'      => 'curr_transactin',
        'EU_VAT_STATUS'        => 'eu_vat_status',
        'MULTI_ADD_TAX'        => 'multi_add_tax',
        'AFFECT_RISK'          => 'affect_risk',
        'EXCLINE_PRICE'        => 'excline_price',
        'EXCLINE_TOTAL'        => 'excline_total',
        'EXCLINE_VAT_MATRAH'   => 'excline_vat_matrah',
        'EXCLINE_LINE_NET'     => 'excline_line_net',
        'EDT_PRICE'            => 'edt_price',
        'EDT_CURR'             => 'edt_curr',
        'ORG_DUE_DATE'         => 'org_due_date',
        'ORG_QUANTITY'         => 'org_quantity',
        'ORG_PRICE'            => 'org_price',
        'VATEXCEPT_CODE'       => 'vatexcept_code',
        'VATEXCEPT_REASON'     => 'vatexcept_reason',
        'GUID'                 => 'guid',
    ];

    /** Logo → DB (header) */
    public static function mapHeader(array $logo): array
    {
        return self::remap($logo, self::$header);
    }

    /** DB → Logo (header) */
    public static function unmapHeader(array $local): array
    {
        return self::remap($local, array_flip(self::$header));
    }

    /** Logo → DB (transaction) */
    public static function mapTransaction(array $item): array
    {
        return self::remap($item, self::$transaction);
    }

    /** DB → Logo (transaction) */
    public static function unmapTransaction(array $local): array
    {
        return self::remap($local, array_flip(self::$transaction));
    }

    /** Ortak remap helper’ı */
    static function remap(array $src, array $map): array
    {
        $out = [];
        foreach ($map as $from => $to) {
            if (array_key_exists($from, $src)) {
                $out[$to] = $src[$from];
            }
        }
        return $out;
    }
}
