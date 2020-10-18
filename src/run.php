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



function run($url)
{
    stdout('处理地址:' . $url);
    //方式一：不等待渲染
    //$query = QueryList::get($url);
    //方式二:等待渲染
    //$query = QueryList::getInstance()->use(PhantomJs::class,__DIR__.'/../exefile/phatomjs/bin/phantomjs.exe')->browser($url);
    //方式三:自定义渲染
    $urlParse = parse_url(rtrim($url, '/'));
    $host = $urlParse['host'];
    $scheme = $urlParse['scheme'] ?? 'http';
    $path = $urlParse['path'] ?? 'index.html';
    $root = __DIR__ . '/demo/' . $host;
    $domain = $scheme . '://' . $host;

    $logFile = __DIR__ . '/log/' . $host . '.php';
    $logs = spiderLog($logFile);
    if (in_array($url, $logs)){
        stdout($url . '已爬取!!!!');

        return;
    }
    /**
     * @var $query QueryList
     */
    $query = QueryList::getInstance()->use(PhantomJs::class, __DIR__ . '/../exefile/phatomjs/bin/phantomjs.exe')->browser(function (RequestInterface $r) use ($url){
        $r->setMethod('GET');
        $r->setUrl($url);
        $r->setTimeout(10000);
        $r->setDelay(3);

        return $r;
    });
    $html = $query->getHtml();
    $imgs = $query->find('img')->attrs('src')->all();
    $aLinks = $query->find('a')->attrs('href')->all();

    //保存html
    $htmlPath = $root . '/' . trim($path, '/');

    makeDir(dirname($htmlPath)) && file_put_contents($htmlPath, $html);

    //保存图片
    $acceptMimes = ['baibmp', 'jpg', 'jpeg', 'png', 'tif', 'gif', 'pcx', 'tga', 'exif', 'fpx', 'svg', 'psd', 'cdr', 'pcd', 'dxf', 'ufo', 'eps', 'ai', 'raw', 'wmf', 'webp'];
    foreach($imgs as $img){
        try{
            $link = getLink($domain, $img);
            stdout('图片地址:' . $link);
            if (empty($link)){
                continue;
            }
            $relativePath = strstr(substr(strrchr($link, '://'), 3), '/');//相对路径
            $extension = substr(strrchr($relativePath, '.'), 1);//扩展名

            stdout('路径:' . $relativePath . '    扩展名:' . $extension);
            //不支持的mime
            if (!in_array(strtolower($extension), $acceptMimes)){
                continue;
            }
            $imgPath = $root . '/' . trim($relativePath, '/');
            if (!makeDir(dirname($imgPath))){
                continue;
            }

            file_put_contents($imgPath, file_get_contents($link));
            stdout($img . '下载完成!!!!!!');
        }catch(\Throwable $e){
            stdout($e->getTraceAsString(),'red');
        }

    }

    //记入日志
    spiderLog($logFile, $url);

    foreach($aLinks as $alink){
        $link = getLink($domain, $alink);
        if (empty($link)){
            continue;
        }
        run($link);
    }
}


/**
 * 主运行
 */

fwrite(STDOUT,'请输入网站地址（包含协议）：');
$url = fgets(STDIN);
echo '您输入的地址为：'.$url;

$urlParse = parse_url(rtrim($url, '/'));
$host = $urlParse['host'] ?? '';
$scheme = $urlParse['scheme'] ?? '';
$domain = $scheme . '://' . $host;

empty($link = getLink($domain, $url)) ? exit('地址不合法') : run($link);



