### 介绍


Tiktok 采集库，支持采集Tiktok用户、视频信息等，使用php开发，本项目依赖xbogus，项目文件已经包含，请使用nodejs
使用时，请修改TiktokSpider.php 文件中的
```php
    private $cmdRoot = "node D:\mycode\\xbogus\index.js \"%s\"";
```

### 特征

- 支持采集抖音用户基本信息
- 支持采集抖音用户所有发布视频
- 支持获取视频基本信息 
- 支持采集视频所以评论
- 支持下载视频
- 支持搜索关键词

### demo
#### 1、获取抖音用户基本信息
```php
$tiktok = new TiktokSpider('', '', '', [
    "ip" => "127.0.0.1:49881"
]);
$data = $tiktok->debug()->getUserInfo("cnn");
print_r($data);
```

#### 2、采集用户所有发布视频
```php
$tiktok = new TiktokSpider('', '', '', [
    "ip" => "127.0.0.1:49881"
]);
$data = $tiktok->getUserItemList(new GetListItemOptions("cnn","",20,0,1));
print_r($data);
```

#### 3、采集视频所以评论
```php
$data = (new TiktokSpider("","","",[
    "ip" => "127.0.0.1:49881"
]))->getVideoComments((new GetVideoCommentOptions("7315171634346741038",20,0,1)));
print_r($data);
```

#### 4、获取视频基本信息
```php
$data = (new TiktokSpider($cookie,"","",[
    "ip" => "127.0.0.1:49881"
]))->debug()->getVideoDetail($url);
dd($data);
```

#### 5、下载视频
下载视频必须需要Tiktok的cookie
```php
$spider = new  TiktokSpider($cookie,"","",[
    "ip" => "127.0.0.1:49881"
]);

$data = $spider->debug()->downloadVideo($url);
file_put_contents("1.mp4",$data['data']);
```

#### 6、搜索
下载视频必须需要Tiktok的cookie、token
```php
$tiktok = new TiktokSpider($cookie,   $token, '', [
    "ip" => "127.0.0.1:49881"
]);
$data = $tiktok->search(new SearchOptions("搞笑",20,0,1));
dd($data);
```
