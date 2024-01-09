<?php

/**
 *
 * @time 2023/12/5
 * @uthor meetmr
 */

namespace spider\TiktokSpider\Exception;


class GetUserIDError extends SpiderException
{
    protected $message = "获取用户id失败";
}