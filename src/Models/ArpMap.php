<?php
// src/Models/ArpMap.php

declare(strict_types=1);

namespace App\Models;

/**
 * Mapping helpers between Logo ARP API fields and local `sirket` table columns.
 */
class ArpMap
{
    /**
     * Logo API field => DB column mapping
     */
    public static array $fields = [
        'CODE'         => 's_arp_code',
        // Logo'daki internal reference (LOGICALREF) değeri
        // local tabloda 'internal_reference' olarak tutulur
        'INTERNAL_REFERENCE' => 'internal_reference',
        'TITLE'        => 's_adi',
        'AUXIL_CODE'   => 's_auxil_code',
        'AUTH_CODE'    => 's_auth_code',
        'ADDRESS1'     => 's_adresi',
        'ADDRESS2'     => 's_adresi2',
        'TOWN'         => 's_ilce',
        'CITY'         => 's_il',
        'COUNTRY_CODE' => 's_country_code',
        'COUNTRY'      => 's_country',
        'POSTAL_CODE'  => 's_postal_code',
        'TELEPHONE1'   => 's_telefonu',
        'TELEPHONE1_CODE' => 's_tel1_code',
        'TELEPHONE2'   => 's_telefonu2',
        'TELEPHONE2_CODE' => 's_tel2_code',
        'FAX'          => 's_fax',
        'WEB_URL'      => 's_web',
        'CORRESP_LANG' => 's_corresp_lang',
        'TAX_ID'       => 's_vno',
        'TAX_OFFICE'   => 's_vd',
        'TAX_OFFICE_CODE' => 's_tax_office_code',
        'CONTACT'      => 'yetkili',
        'CONTACT2'     => 'yetkili2',
        'CONTACT3'     => 'yetkili3',
        'E_MAIL'       => 'mail',
        'E_MAIL2'      => 'mail2',
        'E_MAIL3'      => 'mail3',
        'PAYMENT_CODE' => 'payplan_code',
        'TRADING_GRP'  => 'trading_grp',
        'GL_CODE'      => 's_gl_code',
        'ACCOUNT_TYPE' => 'account_type',
        'SUBSCRIBER_EXT' => 's_subscriber_ext',
        'CURRENCY'     => 'currency',
        'CREDIT_LIMIT' => 'credit_limit',
        'RISK_LIMIT'   => 'risk_limit',
        'RISKFACT_CHQ'    => 'riskfact_chq',
        'RISKFACT_PROMNT' => 'riskfact_promnt',
        'BLOCKED'      => 'blocked',
        'RECORD_STATUS'=> 'record_status',
        'CL_ORD_FREQ'     => 'cl_ord_freq',
        'LOGOID'          => 'logoid',
        'INVOICE_PRNT_CNT' => 'invoice_prnt_cnt',
        'ACCEPT_EINV'     => 'accept_einv',
        'PROFILE_ID'      => 'profile_id',
        'POST_LABEL'      => 'post_label',
        'SENDER_LABEL'    => 'sender_label',
        'FACTORY_DIV_NR'  => 'factory_div_nr',
        'CREATE_WH_FICHE' => 'create_wh_fiche',
        'DISP_PRINT_CNT'  => 'disp_print_cnt',
        'ORD_PRINT_CNT'   => 'ord_print_cnt',
        'GUID'            => 'guid',
        'LOW_LEVEL_CODES1'=> 'low_level_codes1',
    ];

    /** Map Logo API data to DB column names */
    public static function map(array $logo): array
    {
        return self::remap($logo, self::$fields);
    }

    /** Convert DB column array to Logo API field names */
    public static function unmap(array $local): array
    {
        return self::remap($local, array_flip(self::$fields));
    }

    private static function remap(array $src, array $map): array
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
