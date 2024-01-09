<?php

/**
 *
 * @time 2023/12/5
 * @uthor meetmr
 */

namespace spider\TiktokSpider\log;

class Log implements ILog
{

    public function saveLog($log): void {
        echo date('Y-m-d H:i:s')." ".$log." \n";
    }

    public static function save($log): void {
        echo date('Y-m-d H:i:s')." ".$log." \n";
    }
}