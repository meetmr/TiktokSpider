<?php

/**
 *
 * @time 2023/12/5
 * @uthor meetmr
 */


namespace spider\TiktokSpider\option;

class StatusCode
{
    public static $Ok = 0;
    public static $ErrorCode = 10000; // 未知异常
    public static $NetworkErrorCode = 20000; // 未知异常
    public static $RateLimitErrorCode = 10001; // 速率限制
    public static $OperationErrorCode = 10002; // 获取到的不是json
    public static $RequestFailureErrorCode = 10003; // 请求失败
    public static $AnalysisErrorCode = 10004;// 解析json失败
    public static $GetUserIDErrorCode = 10005;// 解析json失败
    public static $AnalysisTweetErrorCode = 10006;// 解析ID 失败
    public static $CookieLoseEfficacyErrorCode = 10007;//Cookie失效
    public static $UserTsProtectionExistErrorCode = 10008;// 帖子受到保护
    public static $LineExistErrorCode = 10009;// 线路异常

    public static $NotExistError = 10010;
    public static $NoDataObtainedError = 10020;
}