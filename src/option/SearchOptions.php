<?php

/**
 *
 * @time 2023/12/5
 * @uthor meetmr
 */

namespace spider\TiktokSpider\option;

class SearchOptions
{

    public static $maxNumber;
    public static $cursor;
    public static string $keyword;
    public static $sleepTime;

    public function __construct($keyword,$maxNumber,$cursor,$sleepTime) {
        self::$keyword = $keyword;
        self::$maxNumber = $maxNumber;
        self::$cursor = $cursor;
        self::$sleepTime = $sleepTime;
    }

    /**
     * @return mixed
     */
    public static function getMaxNumber() {
        return self::$maxNumber;
    }

    /**
     * @param mixed $maxNumber
     */
    public static function setMaxNumber($maxNumber): void {
        self::$maxNumber = $maxNumber;
    }

    /**
     * @return mixed
     */
    public static function getCursor() {
        return self::$cursor;
    }

    /**
     * @param mixed $cursor
     */
    public static function setCursor($cursor): void {
        self::$cursor = $cursor;
    }

    public static function getKeyword(): string {
        return self::$keyword;
    }

    public static function setKeyword(string $keyword): void {
        self::$keyword = $keyword;
    }

    /**
     * @return mixed
     */
    public static function getSleepTime() {
        return self::$sleepTime;
    }

    /**
     * @param mixed $sleepTime
     */
    public static function setSleepTime($sleepTime): void {
        self::$sleepTime = $sleepTime;
    }


}