<?php

/**
 *
 * @time 2023/12/5
 * @uthor meetmr
 */

namespace spider\TiktokSpider\option;

class GetListItemOptions
{
    public static $account;
    public static $userId;
    public static $maxNumber;
    public static $cursor;

    /**
     * @return mixed
     */
    public static function getAccount(): mixed {
        return self::$account;
    }

    /**
     * @param mixed $account
     */
    public static function setAccount(mixed $account): void {
        self::$account = $account;
    }
    public static mixed $sleepTime;

    public function __construct($account,$userId,$maxNumber,$cursor,$sleepTime) {
        self::$account = $account;
        self::$userId = $userId;
        self::$maxNumber = $maxNumber;
        self::$cursor = $cursor;
        self::$sleepTime = $sleepTime;
    }


    /**
     * @return mixed
     */
    public static function getUserId(): mixed {
        return self::$userId;
    }

    /**
     * @param mixed $userId
     */
    public static function setUserId(mixed $userId): void {
        self::$userId = $userId;
    }

    /**
     * @return mixed
     */
    public static function getMaxNumber(): mixed {
        return self::$maxNumber;
    }

    /**
     * @param mixed $maxNumber
     */
    public static function setMaxNumber(mixed $maxNumber): void {
        self::$maxNumber = $maxNumber;
    }

    /**
     * @return mixed
     */
    public static function getCursor(): mixed {
        return self::$cursor;
    }

    /**
     * @param mixed $cursor
     */
    public static function setCursor(mixed $cursor): void {
        self::$cursor = $cursor;
    }

    /**
     * @return mixed
     */
    public static function getSleepTime(): mixed {
        return self::$sleepTime;
    }

    /**
     * @param mixed $sleepTime
     */
    public static function setSleepTime(mixed $sleepTime): void {
        self::$sleepTime = $sleepTime;
    }
}