<?php

/**
 *
 * @time 2023/12/13
 * @uthor meetmr
 */

namespace spider\TiktokSpider\Exception;


class UserNotExistError extends SpiderException
{
    protected $message = "此账号不存在";

}