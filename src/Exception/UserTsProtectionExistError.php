<?php

/**
 *
 * @time 2023/12/13
 * @uthor meetmr
 */

namespace spider\TiktokSpider\Exception;


class UserTsProtectionExistError extends SpiderException
{
    protected $message = "这些帖子受到保护";

}