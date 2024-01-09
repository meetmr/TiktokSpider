<?php

/**
 *
 * @time 2023/12/5
 * @uthor meetmr
 */

namespace spider\TiktokSpider\Exception;


class NetworkError extends SpiderException
{
    protected $message = "请求失败";

}