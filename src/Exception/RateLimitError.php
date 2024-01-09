<?php

/**
 *
 * @time 2023/12/5
 * @uthor meetmr
 */

namespace spider\TiktokSpider\Exception;

class RateLimitError extends SpiderException
{
    protected $message = "速率限制";
}