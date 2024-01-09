<?php

/**
 *
 * @time 2023/12/26
 * @uthor meetmr
 */

namespace spider\TiktokSpider;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use spider\TiktokSpider\Exception\NetworkError;
use spider\TiktokSpider\Exception\NoDataObtained;
use spider\TiktokSpider\Exception\OperationError;
use spider\TiktokSpider\Exception\RateLimitError;
use spider\TiktokSpider\log\ILog;
use spider\TiktokSpider\log\Log;
use spider\TiktokSpider\option\GetListItemOptions;
use spider\TiktokSpider\option\GetVideoCommentOptions;
use spider\TiktokSpider\option\SearchOptions;
use spider\TiktokSpider\option\StatusCode;

class TiktokSpider
{
    public string $cookie;
    public static $userId;
    private Client $request;

    private bool $debug = false;

    protected ILog $log;

    private $cmdRoot = "node D:\mycode\\xbogus\index.js \"%s\"";

    protected array $httpProxy = [];
    /**
     * @var string
     */
    private string $token;

    public function getHttpProxy(): array {
        return $this->httpProxy;
    }

    public function setHttpProxy(array $httpProxy): void {
        $this->httpProxy = $httpProxy;
    }

    public function getCookie(): string {
        return $this->cookie;
    }


    public function setCookie(string $cookie): void {
        $this->cookie = $cookie;
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

    public function __construct($cookie, $token = "", $log = null, $httpProxy = []) {
        $this->cookie = $cookie;
        $this->token = $token;

        $clientOptions = [
            'verify' => true,
            'headers' => [
                'authority' => 'www.tiktok.com',
                'accept' => '*/*',
                'accept-language' => 'zh-CN,zh;q=0.9,en-GB;q=0.8,en-US;q=0.7,en;q=0.6,eu;q=0.5',
                'cookie' => $cookie,
                'dnt' => '1',
                'referer' => 'https://www.tiktok.com/',
                'sec-ch-ua' => '"Google Chrome";v="119", "Chromium";v="119", "Not?A_Brand";v="24"',
                'sec-ch-ua-mobile' => '?0',
                'sec-ch-ua-platform' => '"Windows"',
                'sec-fetch-dest' => 'empty',
                'sec-fetch-mode' => 'cors',
                'sec-fetch-site' => 'same-origin',
                'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36'
            ],
            'timeout' => 50,
        ];

        if ($httpProxy) {
            $clientOptions['proxy'] = $httpProxy['ip'];
            if (isset($httpProxy['user'])) {
                $clientOptions['curl'] = [
                    CURLOPT_PROXYUSERPWD => $httpProxy['user']
                ];
            }
        }
        if ($log) {
            $this->log = $log;
        } else {
            $this->log = new Log();
        }
        $this->request = new Client($clientOptions);
    }

    private function getXBogus($url): string {
        $cmd = sprintf($this->cmdRoot, $url);
        $res = shell_exec($cmd);
        return trim($res);
    }

    public function debug(): static {
        $this->debug = true;
        return $this;
    }

    public function getUserInfo($account): array {
        $return = [
            "msg" => "",
            "code" => StatusCode::$Ok,
            "status" => "error",
            "data" => "",
        ];
        $userUrl = "https://www.tiktok.com/api/user/detail/?WebIdLastTime=" . time() . "&aid=1988&app_language=zh-Hans&app_name=tiktok_web&browser_language=zh-CN&browser_name=Mozilla&browser_online=true&browser_platform=Win32&browser_version=5.0%20%28Windows%20NT%2010.0%3B%20Win64%3B%20x64%29%20AppleWebKit%2F537.36%20%28KHTML%2C%20like%20Gecko%29%20Chrome%2F119.0.0.0%20Safari%2F537.36&channel=tiktok_web&cookie_enabled=true&device_id=7316807826889098783&device_platform=web_pc&focus_state=true&from_page=user&history_len=20&is_fullscreen=false&is_page_visible=true&language=zh-Hans&os=windows&priority_region=&referer=&region=US&screen_height=864&screen_width=1536&tz_name=Etc%2FGMT-8&uniqueId=" . $account . "&webcast_language=zh-Hans&msToken=" . $this->token;
        $userUrl .= "&X-Bogus=" . $this->getXBogus($userUrl);
        try {
            $options = [];
            if ($this->debug) {
                $options["debug"] = true;
            }
            $res = $this->request->get($userUrl, $options);
        } catch (GuzzleException $e) {
            $return['code'] = StatusCode::$ErrorCode;
            $return['msg'] = "请求失败" . $e->getMessage();
            return $return;
        }
        if ($res->getStatusCode() !== 200) {
            $return['code'] = StatusCode::$NetworkErrorCode;
            $return['msg'] = "请求失败";
            return $return;
        }
        $data = $res->getBody()->getContents();
        $resData = json_decode($data, 256);
        if (!is_array($resData)) {
            if (str_contains($data, 'Rate limit exceeded')) {
                $return['code'] = StatusCode::$RateLimitErrorCode;
                $return['msg'] = "请求失败";
                return $return;
            }
            $return['code'] = StatusCode::$ErrorCode;
            $return['msg'] = "请求失败" . substr($data, 0, 100);
            return $return;
        }

        if (!isset($resData['userInfo'])) {
            $return['code'] = StatusCode::$ErrorCode;
            $return['msg'] = "请求接口没有获取到数据";
            return $return;
        }

        try {
            $userInfo = [
                "desc" => $resData['shareMeta']['desc'],
                'followerCount' => $resData['userInfo']['stats']['followerCount'], // 粉丝
                'followingCount' => $resData['userInfo']['stats']['followingCount'], // 已关注
                'friendCount' => $resData['userInfo']['stats']['friendCount'],
                'heart' => $resData['userInfo']['stats']['heart'], // 赞
                'videoCount' => $resData['userInfo']['stats']['videoCount'], // 视频数量
                'avatarLarger' => $resData['userInfo']['user']['avatarLarger'],
                'nickname' => $resData['userInfo']['user']['nickname'],
                'id' => $resData['userInfo']['user']['id'],
                'nickNameModifyTime' => $resData['userInfo']['user']['nickNameModifyTime'],
                'secUid' => $resData['userInfo']['user']['secUid'],
                'uniqueId' => $resData['userInfo']['user']['uniqueId'],
            ];
        } catch (\Exception $exception) {
            $return['code'] = StatusCode::$AnalysisErrorCode;
            $return['msg'] = "数据解析失败";
            return $return;
        }
        $return['data'] = $userInfo;
        $return['status'] = "success";
        return $return;
    }

    public function getVideoComments(GetVideoCommentOptions $options): array {
        $data = [
            "data" => [],
            "lastCursor" => "",
            "status" => "error",
            "msg" => '',
            'hasMore' => 1,
            "code" => StatusCode::$Ok,
        ];
        if (!$options::getAwemeId()) {
            $data['msg'] = "请传入视频ID";
            return $data;
        }

        $maxNumber = $options::$maxNumber ?: 20;
        $sleepTime = $options::$sleepTime ?: 1;

        if (!$options::getCursor()) {
            $options::setCursor(0);
        }
        for ($i = 1; $i <= $maxNumber; $i++) {
            $this->log->saveLog("正在获取第：" . $i . " 页");
            try {
                $results = $this->_getVideoComments($options);
                $cursor = $results['cursor'];
                $data['lastCursor'] = $cursor;
                $data['hasMore'] = $results['has_more'];
                if (count($results['data']) == 0) {
                    break;
                }
                $options::setCursor($cursor);
                $this->log->saveLog(sprintf("%d 页获取到：%d", $i, count($results['data'])));
                $data['data'] = array_merge($data['data'], $results['data']);
                $this->log->saveLog(sprintf("%d 页 lastCursor：%s", $i, $results['cursor']));
                if ($results['cursor'] == "") {
                    break;
                }
                if ($results['has_more'] != 1) {
                    $this->log->saveLog("数据已经获取完毕...");
                    break;
                }
            } catch (NoDataObtained $e) {
                $data['msg'] = $e->getMessage();
                $data['code'] = StatusCode::$NoDataObtainedError;
            } catch (ConnectException $e) {
                $data['msg'] = "线路异常" . $e->getMessage();
                $data['code'] = StatusCode::$LineExistErrorCode;
            } catch (GuzzleException $e) {
                $data = $this->getException($e, $data);
            } catch (NetworkError $e) {
                $data['msg'] = $e->getMessage();
                $data['code'] = StatusCode::$RequestFailureErrorCode;
            } catch (OperationError $e) {
                $data['msg'] = $e->getMessage();
                $data['code'] = StatusCode::$OperationErrorCode;
            } catch (RateLimitError $e) {
                $data['msg'] = $e->getMessage();
                $data['code'] = StatusCode::$RateLimitErrorCode;
            }
            if ($data['msg']) {
                break;
            }
            sleep($sleepTime);
        }
        $data['status'] = $data['msg'] == "" ? "success" : "error";
        return $data;
    }

    /**
     * @throws NetworkError
     * @throws OperationError
     * @throws RateLimitError
     * @throws GuzzleException
     * @throws NoDataObtained
     */
    public function _getVideoComments(GetVideoCommentOptions $options): array {
        $results = [
            'data' => [],
            'cursor' => 0,
            "has_more" => 1,
        ];
        $url = "https://www.tiktok.com/api/comment/list/?WebIdLastTime=1703577086&aid=1988&app_language=ja-JP&app_name=tiktok_web&aweme_id=" . $options::getAwemeId() . "&browser_language=zh-CN&browser_name=Mozilla&browser_online=true&browser_platform=Win32&browser_version=5.0%20%28Windows%20NT%2010.0%3B%20Win64%3B%20x64%29%20AppleWebKit%2F537.36%20%28KHTML%2C%20like%20Gecko%29%20Chrome%2F120.0.0.0%20Safari%2F537.36&channel=tiktok_web&cookie_enabled=true&count=50&current_region=JP&cursor=" . $options::getCursor() . "&device_id=7316807826889098783&device_platform=web_pc&enter_from=tiktok_web&focus_state=false&fromWeb=1&from_page=video&history_len=3&is_fullscreen=false&is_non_personalized=false&is_page_visible=true&os=windows&priority_region=&referer=&region=US&screen_height=864&screen_width=1536&tz_name=Etc%2FGMT-8&webcast_language=zh-Hans&msToken=" . $this->token;
        $url .= "&X-Bogus=" . $this->getXBogus($url);
        try {
            $options = [];
            if ($this->debug) {
                $options["debug"] = true;
            }
            $res = $this->request->get($url, $options);
        } catch (GuzzleException $e) {
            throw new $e;
        }
        if ($res->getStatusCode() !== 200) {
            throw new NetworkError();
        }
        $data = $res->getBody()->getContents();
        $resData = json_decode($data, 256);
        if (!is_array($resData)) {
            if (str_contains($data, 'Rate limit exceeded')) {
                throw new RateLimitError();
            }
            throw new OperationError(
                'Invalid response: ' . substr($data, 0, 100)
            );
        }

        if ($resData['status_code'] !== 0) {
            throw new OperationError("statusCode." . $resData['status_code']);
        }
        if (!isset($resData['comments'])) {
            throw new NoDataObtained();
        }
        $results['has_more'] = $resData['has_more'];
        $results['cursor'] = $resData['cursor'];

        $listItem = [];
        foreach ($resData['comments'] as $datum) {
            $item = [
                'aweme_id' => $datum['aweme_id'],
                'cid' => $datum['cid'],
                'create_time' => $datum['create_time'],
                'share_info' => $datum['share_info'],
                'text' => $datum['text'],
                'digg_count' => $datum['digg_count'],
                'user' => [
                    'nickname' => $datum['user']['nickname'],
                    'sec_uid' => $datum['user']['sec_uid'],
                    'uid' => $datum['user']['uid'],
                    'unique_id' => $datum['user']['unique_id'],
                    'home_url' => "https://www.tiktok.com/@" . $datum['user']['unique_id']
                ],
            ];
            unset($item['share_info']['acl']);
            $listItem[] = $item;
        }
        $results['data'] = $listItem;
        return $results;
    }


    public function getUserItemList(GetListItemOptions $options): array {
        $data = [
            "data" => [],
            "lastCursor" => "",
            "status" => "error",
            "msg" => '',
            'hasMore' => true,
            "code" => StatusCode::$Ok,
        ];

        if (!$options::getAccount()) {
            $data['msg'] = "请传入采集的推特账号";
            return $data;
        }
        if (!$options::getUserId()) {
            $userData = $this->getUserInfo($options::getAccount());
            if ($userData['status'] != "success") {
                $data['msg'] = "获取userid失败";
                $data['code'] = StatusCode::$AnalysisTweetErrorCode;
                return $data;
            }
            $options::setUserId($userData['data']['secUid']);
        }

        $maxNumber = $options::$maxNumber ?: 20;
        $sleepTime = $options::$sleepTime ?: 1;
        if (!$options::getCursor()) {
            $options::setCursor(0);
        }
        for ($i = 1; $i <= $maxNumber; $i++) {
            $this->log->saveLog("正在获取第：" . $i . " 页");
            try {
                $results = $this->_getUserItemList($options);
                $cursor = $results['lastCursor'];
                $data['lastCursor'] = $cursor;
                $data['hasMore'] = $results['hasMore'];
                if (count($results['data']) == 0) {
                    break;
                }
                $options::setCursor($cursor);
                $this->log->saveLog(sprintf("%d 页获取到：%d", $i, count($results['data'])));
                $data['data'] = array_merge($data['data'], $results['data']);
                $this->log->saveLog(sprintf("%d 页 lastCursor：%s", $i, $results['lastCursor']));
                if (!$results['hasMore']) {
                    $this->log->saveLog("数据已经获取完毕...");
                    break;
                }
            } catch (NoDataObtained $e) {
                $data['msg'] = $e->getMessage();
                $data['code'] = StatusCode::$NoDataObtainedError;
            } catch (ConnectException $e) {
                $data['msg'] = "线路异常" . $e->getMessage();
                $data['code'] = StatusCode::$LineExistErrorCode;
            } catch (GuzzleException $e) {
                $data = $this->getException($e, $data);
            } catch (NetworkError $e) {
                $data['msg'] = $e->getMessage();
                $data['code'] = StatusCode::$RequestFailureErrorCode;
            } catch (OperationError $e) {
                $data['msg'] = $e->getMessage();
                $data['code'] = StatusCode::$OperationErrorCode;
            } catch (RateLimitError $e) {
                $data['msg'] = $e->getMessage();
                $data['code'] = StatusCode::$RateLimitErrorCode;
            }
            if ($data['msg']) {
                break;
            }
            sleep($sleepTime);
        }
        $data['status'] = $data['msg'] == "" ? "success" : "error";
        return $data;
    }


    /**
     * @throws NetworkError
     * @throws RateLimitError
     * @throws OperationError
     * @throws NoDataObtained
     */
    private function _getUserItemList(GetListItemOptions $options): array {
        $results = [
            'data' => [],
            'lastCursor' => "",
            "hasMore" => true,
        ];
        $url = "https://www.tiktok.com/api/post/item_list/?WebIdLastTime=" . time() . "&aid=1988&app_language=zh-Hans&app_name=tiktok_web&browser_language=zh-CN&browser_name=Mozilla&browser_online=true&browser_platform=Win32&browser_version=5.0%20%28Windows%20NT%2010.0%3B%20Win64%3B%20x64%29%20AppleWebKit%2F537.36%20%28KHTML%2C%20like%20Gecko%29%20Chrome%2F120.0.0.0%20Safari%2F537.36&channel=tiktok_web&cookie_enabled=true&count=35&coverFormat=2&cursor=" . $options::getCursor() . "&device_id=7316807826889098783&device_platform=web_pc&focus_state=true&from_page=user&history_len=5&is_fullscreen=false&is_page_visible=true&language=zh-Hans&os=windows&priority_region=&referer=&region=US&screen_height=864&screen_width=1536&secUid=" . $options::getUserId() . "&tz_name=Etc%2FGMT-8&webcast_language=zh-Hans&msToken=" . $this->token;
        $url .= "&X-Bogus=" . $this->getXBogus($url);
        try {
            $options = [];
            if ($this->debug) {
                $options["debug"] = true;
            }
            $res = $this->request->get($url, $options);
        } catch (GuzzleException $e) {
            throw new NetworkError();
        }
        if ($res->getStatusCode() !== 200) {
            throw new NetworkError();
        }
        $data = $res->getBody()->getContents();
        $resData = json_decode($data, 256);

        if (!is_array($resData)) {
            if (str_contains($data, 'Rate limit exceeded')) {
                throw new RateLimitError();
            }
            throw new OperationError(
                'Invalid response: ' . substr($data, 0, 100)
            );
        }

        if ($resData['statusCode'] !== 0) {
            throw new OperationError("statusCode." . $resData['statusCode']);
        }
        if (!isset($resData['itemList'])) {
            throw new NoDataObtained();
        }
        $results['hasMore'] = $resData['hasMore'];
        $results['lastCursor'] = $resData['cursor'];

        $listItem = [];
        foreach ($resData['itemList'] as $datum) {
            $item = [
                'avatarThumb' => $datum['author']['avatarLarger'],
                'uid' => $datum['author']['secUid'],
                'account' => $datum['author']['uniqueId'],
                'tid' => $datum['author']['id'],
                'desc' => $datum['desc'],
                'id' => $datum['id'],
                'createTime' => $datum['createTime'],
                'collectCount' => $datum['stats']['collectCount'],
                'commentCount' => $datum['stats']['commentCount'],
                'diggCount' => $datum['stats']['diggCount'],
                'playCount' => $datum['stats']['playCount'],
                'shareCount' => $datum['stats']['shareCount'],
            ];
            $item['video'] = [];
            if (isset($datum['video'])) {
                if (isset($datum['video']['bitrateInfo'])) {
                    $bitrateInfo = $datum['video']['bitrateInfo'];
                }
                $video = $datum['video'];
                unset($video['volumeInfo']);
                unset($video['videoQuality']);
                unset($video['ratio']);
                unset($video['bitrateInfo']);
                unset($video['zoomCover']);
                unset($video['subtitleInfos']);
                unset($video['bitrateInfo']);
//                if (isset($bitrateInfo)) {
//                    $video['bitrateInfo'] = $bitrateInfo[0];
//                }
                $item['video'] = $video;
            }
            $listItem[] = $item;
        }
        $results['data'] = $listItem;
        return $results;
    }

    public function getException(Exception|GuzzleException $e, array $data): array {
        if ($e->getCode() == 429) {
            $data['msg'] = "速率限制";
            $data['code'] = StatusCode::$RateLimitErrorCode;
        } elseif ($e->getCode() == 403) {
            $data['msg'] = "cookie 异常";
            $data['code'] = StatusCode::$CookieLoseEfficacyErrorCode;
        } elseif ($e->getCode() == 7) {
            $data['msg'] = "线路 异常";
            $data['code'] = StatusCode::$LineExistErrorCode;
        } else {
            $data['msg'] = $e->getMessage();
            $data['code'] = StatusCode::$RequestFailureErrorCode;
        }
        return $data;
    }

    public function downloadVideo($url): array {
        $return = [
            "code" => StatusCode::$Ok,
            "status" => "error",
            "data" => "",
        ];

        $videoDetail = $this->getVideoDetail($url);
        if ($videoDetail['status'] != "success") {
            $return['msg'] = "获取视频地址失败";
            return $return;
        }

        $options = [];
        if ($this->debug) {
            $options["debug"] = true;
        }
        $options['headers'] = [
            'Accept' => '*/*',
            'Accept-Language' => 'zh-CN,zh;q=0.9,en-GB;q=0.8,en-US;q=0.7,en;q=0.6,eu;q=0.5',
            'Connection' => 'keep-alive',
            'Cookie' => $videoDetail['data']['cookie'],
            'DNT' => '1',
            'Origin' => 'https://www.tiktok.com',
            'Range' => 'bytes=0-',
            'Referer' => 'https://www.tiktok.com/',
            'Sec-Fetch-Dest' => 'video',
            'Sec-Fetch-Mode' => 'cors',
            'Sec-Fetch-Site' => 'same-site',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'sec-ch-ua' => '"Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"Windows"'
        ];
        try {
            $res = $this->request->get($videoDetail['data']['video']['playAddr'], $options);
        } catch (GuzzleException $e) {
            $return['msg'] = $e->getMessage();
            $return['code'] = StatusCode::$NetworkErrorCode;
            return $return;
        }
        $data = $res->getBody()->getContents();
        $return['msg'] = "获取成功";
        $return['data'] = $data;
        $return['status'] = "success";
        return $return;
    }

    public function getVideoDetail($url): array {
        $return = [
            "msg" => "",
            "code" => StatusCode::$Ok,
            "status" => "error",
            "data" => "",
        ];
        $options = [];
        if ($this->debug) {
            $options["debug"] = true;
        }
        try {
            $res = $this->request->get($url, $options);
        } catch (GuzzleException $e) {
            $return['msg'] = $e->getMessage();
            $return['code'] = StatusCode::$NetworkErrorCode;
            return $return;
        }
        $content = $res->getBody()->getContents();
        preg_match('/<script id="__UNIVERSAL_DATA_FOR_REHYDRATION__" type="application\/json">(.*?)<\/script>/', $content, $item);
        if (!isset($item[1])) {
            $return['msg'] = "解析失败";
            $return['code'] = StatusCode::$GetUserIDErrorCode;
            return $return;
        }
        $json = json_decode($item[1], 256);
        if (!isset($json['__DEFAULT_SCOPE__']['webapp.video-detail']['itemInfo']['itemStruct']['video']['playAddr'])) {
            $return['msg'] = "解析失败";
            $return['code'] = StatusCode::$GetUserIDErrorCode;
            return $return;
        }

        $item = $json['__DEFAULT_SCOPE__']['webapp.video-detail']['itemInfo']['itemStruct'];
        $data = [
            'desc' => $item['desc'],
            'id' => $item['id'],
            'createTime' => $item['createTime'],
            'video' => [
                'cover' => $item['video']['cover'],
                'originCover' => $item['video']['originCover'],
                'playAddr' => $item['video']['playAddr'],
                'downloadAddr' => $item['video']['downloadAddr'],
            ],
            'author' => [
                'id' => $item['author']['id'],
                'uniqueId' => $item['author']['uniqueId'],
                'nickname' => $item['author']['nickname'],
                'avatarLarger' => $item['author']['avatarLarger'],
                'signature' => $item['author']['signature'],
                'secUid' => $item['author']['secUid'],
            ],
            'stats' => $item['stats'],
            'cookie' => $this->cookie
        ];

        $return['msg'] = "获取成功";
        $return['data'] = $data;
        $return['status'] = "success";
        return $return;
    }


    public function search(SearchOptions $options): array {
        $data = [
            "data" => [],
            "lastCursor" => "",
            "status" => "error",
            "msg" => '',
            'hasMore' => true,
            "code" => StatusCode::$Ok,
        ];

        if (!$options::getKeyword()) {
            $data['msg'] = "请传入采集词";
            return $data;
        }

        $maxNumber = $options::$maxNumber ?: 20;
        $sleepTime = $options::$sleepTime ?: 1;
        if (!$options::getCursor()) {
            $options::setCursor(0);
        }
        for ($i = 1; $i <= $maxNumber; $i++) {
            $this->log->saveLog("正在获取第：" . $i . " 页");
            try {
                $results = $this->_search($options);
                $cursor = $results['lastCursor'];
                $data['lastCursor'] = $cursor;
                $data['hasMore'] = $results['has_more'];
                if (count($results['data']) == 0) {
                    $this->log->saveLog("数据已经获取完毕...");
                    break;
                }
                $options::setCursor($cursor);
                $this->log->saveLog(sprintf("%d 页获取到：%d", $i, count($results['data'])));
                $data['data'] = array_merge($data['data'], $results['data']);
                $this->log->saveLog(sprintf("%d 页 lastCursor：%s", $i, $results['lastCursor']));
                if ($results['hasMore'] !== 1) {
                    $this->log->saveLog("数据已经获取完毕...");
                    break;
                }
            } catch (NoDataObtained $e) {
                $data['msg'] = $e->getMessage();
                $data['code'] = StatusCode::$NoDataObtainedError;
            } catch (ConnectException $e) {
                $data['msg'] = "线路异常" . $e->getMessage();
                $data['code'] = StatusCode::$LineExistErrorCode;
            } catch (GuzzleException $e) {
                $data = $this->getException($e, $data);
            } catch (NetworkError $e) {
                $data['msg'] = $e->getMessage();
                $data['code'] = StatusCode::$RequestFailureErrorCode;
            } catch (OperationError $e) {
                $data['msg'] = $e->getMessage();
                $data['code'] = StatusCode::$OperationErrorCode;
            } catch (RateLimitError $e) {
                $data['msg'] = $e->getMessage();
                $data['code'] = StatusCode::$RateLimitErrorCode;
            }
            if ($data['msg']) {
                break;
            }
            sleep($sleepTime);
        }
        $data['status'] = $data['msg'] == "" ? "success" : "error";
        return $data;
    }


    /**
     * @throws NetworkError
     * @throws RateLimitError
     * @throws OperationError
     * @throws GuzzleException
     * @throws NoDataObtained
     */
    public
    function _search(SearchOptions $options): array {
        $results = [
            'data' => [],
            'cursor' => 0,
            "has_more" => 1,
        ];
        $url = "https://www.tiktok.com/api/search/item/full/?WebIdLastTime=1704176767&aid=1988&app_language=zh-Hans&app_name=tiktok_web&browser_language=zh-CN&browser_name=Mozilla&browser_online=true&browser_platform=Win32&browser_version=5.0%20%28Windows%20NT%2010.0%3B%20Win64%3B%20x64%29%20AppleWebKit%2F537.36%20%28KHTML%2C%20like%20Gecko%29%20Chrome%2F120.0.0.0%20Safari%2F537.36&channel=tiktok_web&cookie_enabled=true&count=20&device_id=7319383456447514158&device_platform=web_pc&focus_state=false&from_page=search&history_len=16&is_fullscreen=false&is_page_visible=true&keyword=" . urlencode($options::getKeyword()) . "&offset=" . $options::getCursor() . "&os=windows&priority_region=&referer=&region=US&screen_height=864&screen_width=1536&search_id=20240104052158B2DBDA8980D1BC06E0D4&tz_name=Etc%2FGMT-8&msToken=" . $this->token;
        $url .= "&X-Bogus=" . $this->getXBogus($url);

        try {
            $options = [];
            if ($this->debug) {
                $options["debug"] = true;
            }
            $res = $this->request->get($url, $options);
        } catch (GuzzleException $e) {
            throw new $e;
        }
        if ($res->getStatusCode() !== 200) {
            throw new NetworkError();
        }
        $data = $res->getBody()->getContents();
        $resData = json_decode($data, 256);

        if (!is_array($resData)) {
            if (str_contains($data, 'Rate limit exceeded')) {
                throw new RateLimitError();
            }
            throw new OperationError(
                'Invalid response: ' . substr($data, 0, 100)
            );
        }

        if ($resData['status_code'] !== 0) {
            throw new OperationError("statusCode." . $resData['status_code']);
        }
        if (!isset($resData['item_list'])) {
            throw new NoDataObtained();
        }
        $results['hasMore'] = $resData['has_more'];
        $results['lastCursor'] = $resData['cursor'];
        $listItem = [];
        foreach ($resData['item_list'] as $datum) {
            $item[] = [
                "author" => [
                    "uid" => $datum['author']['id'],
                    "avatarLarger" => $datum['author']['avatarLarger'],
                    "nickname" => $datum['author']['nickname'],
                    "secUid" => $datum['author']['secUid'],
                    "signature" => $datum['author']['signature'],
                    "uniqueId" => $datum['author']['uniqueId'],
                    'authorStats' => $datum['authorStats'],
                ],
                'createTime' => $datum['createTime'],
                'desc' => $datum['desc'],
                'id' => $datum['id'],
                'stats' => $datum['stats'],
                'video' => [
                    'cover' => $datum['video']['cover'],
                    'downloadAddr' => $datum['video']['downloadAddr'],
                    'dynamicCover' => $datum['video']['dynamicCover'],
                    'format' => $datum['video']['format'],
                    'playAddr' => $datum['video']['playAddr'],
                    'reflowCover' => $datum['video']['reflowCover'],
                ]
            ];

            if (isset($datum['challenges'])) {
                $item['challenges'] = array_map(function ($challenge) {
                    return [
                        'id' => $challenge['id'],
                        'title' => $challenge['title'],
                    ];
                }, $datum['challenges']);
            }

            if (isset($datum['textExtra'])) {
                $item['textExtra'] = array_map(function ($textExtra) {
                    return [
                        'end' => $textExtra['end'],
                        'hashtagId' => $textExtra['hashtagId'],
                        'hashtagName' => $textExtra['hashtagName'],
                        'start' => $textExtra['start'],
                    ];
                }, $datum['textExtra']);
            }
            $listItem[] = $item;
        }
        $results['data'] = $listItem;
        return $results;
    }
}