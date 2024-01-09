<?php

/**
 *
 * @time 2023/12/13
 * @uthor meetmr
 */

namespace spider\TiktokSpider\Exception;

class NotExistError extends SpiderException
{
    protected $message = "帖子已经被删除";

}