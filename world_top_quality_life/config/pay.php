<?php
/*
|--------------------------------------------------------------------------
|跨境支付配置（汇付）
|--------------------------------------------------------------------------
*/
return [
    'merchantAcctId' => env('HUIFUID'),
    'terminalId' => env('HUIFUTERMINALID'),
    'terminalId_zi_ying' => env('HUIFUTERMINALID_ZI_YING'),
    'url' => env('HUIFUURL'),
    'queryUrl' => env('HUIFUQUERYURL'),
];