<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Anton
 * Date: 7/26/13
 * Time: 1:18 PM
 * To change this template use File | Settings | File Templates.
 */

function gen_uuid() {
    if(function_exists('uuid_create'))
        return uuid_create();

    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

        // 16 bits for "time_mid"
        mt_rand( 0, 0xffff ),

        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand( 0, 0x0fff ) | 0x4000,

        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand( 0, 0x3fff ) | 0x8000,

        // 48 bits for "node"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
}

function human_filesize($bytes, $decimals = 2) {
    $sz = array("B", "KB", "MB", "GB", "TB", "PB", "EB");
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[(int)$factor];
}