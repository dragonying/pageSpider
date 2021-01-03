<?php
/**
 * Created by PhpStorm.
 * User: fangying.zhong
 * Date: 2020/10/17 0017
 * Time: 19:46
 */
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/function.php';

use QL\QueryList;
use QL\Ext\PhantomJs;
use JonnyW\PhantomJs\Http\RequestInterface;


$keyword = '新宝岛';

$url = 'https://search.bilibili.com/all?keyword='.urlencode($keyword).'&from_source=nav_suggest_new';


$query = QueryList::getInstance()->use(PhantomJs::class, __DIR__ . '/../exefile/phatomjs/bin/phantomjs.exe')->browser(function (RequestInterface $r) use ($url){
    $r->setMethod('GET');
    $r->setHeaders([
        ':authority' => 'search.bilibili.com',
        ':method: GET' => 'path: /all?keyword=%E6%96%B0%E5%AE%9D%E5%B2%9B&from_source=nav_suggest_new',
        'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
        'cookie' => "_uuid=E1E88D4B-09D2-7DA3-7C75-D34BB5DB188286942infoc; buvid3=8C1E50C8-A5C6-4B93-A6D5-762467893028155818infoc; rpdid=|(k||Rll~~)u0J'ulm~J|~m)~; CURRENT_FNVAL=80; blackside_state=1; bsource=search_baidu; sid=b5dt04gd; PVID=1; finger=158939783; arrange=matrix",
        'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.4158.5 Safari/537.36'
    ]);
    $r->setUrl($url);
    $r->setTimeout(10000);
    $r->setDelay(5);

    return $r;
})->range('.video-item')->rules([
    'link'=>['a','href'],
    'title'=>['a','title'],
]);

$data = $query->queryData();

foreach($data as $val){
    $link = 'https:'.str_replace('www','m',$val['link']);
    if(empty($val['title'])){
            continue;
    }
    stdout('视频：'.$val['title'].' 地址：'.$link);
    $header = [
        ':authority' => 'm.bilibili.com',
        ':method: GET' => substr($val['link'],strrpos($val['link'],'/video')),
        'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
        'cookie' => "finger=158939783; _uuid=E1E88D4B-09D2-7DA3-7C75-D34BB5DB188286942infoc; buvid3=8C1E50C8-A5C6-4B93-A6D5-762467893028155818infoc; rpdid=|(k||Rll~~)u0J'ulm~J|~m)~; CURRENT_FNVAL=80; blackside_state=1; bsource=search_baidu; sid=b5dt04gd; PVID=1",
        'user-agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 10_3_1 like Mac OS X) AppleWebKit/603.1.30 (KHTML, like Gecko) Version/10.0 Mobile/14E304 Safari/602.1'
    ];
    $videoQuery = QueryList::getInstance()->use(PhantomJs::class, __DIR__ . '/../exefile/phatomjs/bin/phantomjs.exe')->browser(function (RequestInterface $r) use ($link,$header){
        $r->setMethod('GET');
        $r->setHeaders($header);
        $r->setUrl($link);
        $r->setTimeout(10000);
        $r->setDelay(5);

        return $r;
    });
    $html = $query->getHtml();
    preg_match('/readyVideoUrl:(.*?)\n/',$html,$math);
    $videoUrl = 'https:'.str_replace("'",'',trim($math[1],"',，‘’'\' "));

    stdout('视频地址：'.$videoUrl);
    file_put_contents('./bili/video/'.$val['title'].'.mp4',file_get_contents($videoUrl));
}