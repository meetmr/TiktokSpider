<?php

/**
 *
 * @time 2024/1/2
 * @uthor meetmr
 */

namespace spider\TiktokSpider\option;

class GetVideoCommentOptions
{
    public static $aweme_id;
    public static $maxNumber;
    public static $cursor;

    public static $sleepTime;

    public function __construct($aweme_id, $maxNumber, $cursor, $sleepTime) {
        self::$aweme_id = $aweme_id;
        self::$maxNumber = $maxNumber;
        self::$cursor = $cursor;
        self::$sleepTime = $sleepTime;
    }


    /**
     * @return mixed
     */
    public static function getAwemeId() {
        return self::$aweme_id;
    }

    /**
     * @param mixed $aweme_id
     */
    public static function setAwemeId($aweme_id): void {
        self::$aweme_id = $aweme_id;
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