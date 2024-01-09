<?php

/**
 *
 * @time 2023/12/13
 * @uthor meetmr
 */

namespace spider\TiktokSpider\Exception;

class UserSuspendedError extends SpiderException
{
    protected $message = "账号已被冻结";
}