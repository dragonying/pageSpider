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

//获取静态文件隐藏的资源
function getStatic($url)
{
    $allMaterial = [];
    try{
        $sourceUrlInfo = pathinfo($url);
        preg_match_all('/url\((.*?)\)/', file_get_contents($url), $match);
        $cssContainSource = $match[1] ?? [];

        foreach($cssContainSource as $cssSource){
            $cssSource = trim($cssSource, "\'\"");
            stdout('源文件' . $url, 'blue');
            if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $cssSource) || strripos($cssSource, 'base64') !== false){
                stdout("base64地址自动过滤!!!", 'red');
                continue;
            }
            stdout(' 抓取资源:' . $cssSource);
            $sourcePath = $sourceUrlInfo['dirname'];//默认资源路径
            if (preg_match('/^(\.\.\/)+/', $cssSource, $match)){
                $relative = $match[0] ?? null;
                if (!$relative){
                    continue;
                }
                $showTimes = substr_count($relative, "../");

                for($i = 0; $i < $showTimes; $i++){
                    $sourcePath = dirname($sourcePath);
                }
                $material = $sourcePath . '/' . ltrim($cssSource, '\.\./');
            }else{
                if (empty($material = getLink($sourcePath, $cssSource))){
                    if (preg_match('/^(\w+)/', $url)){
                        list($host, $domain) = parseUrl($url);
                        $material = ($domain == $url ? $domain : $sourcePath) . '/' . $cssSource;
                    }
                }
            }
            if (empty($material)){
                continue;
            }
            stdout('得到资源:' . $material . PHP_EOL, 'yellow');
            $allMaterial[] = $material;
        }
    }catch(\Throwable $e){
        //        stdout('获取静态文件内包含资源错误: '.$e->getTraceAsString(),'red');
    }

    return $allMaterial;
}

;

//保存静态资源
function saveStatic($root, $domain, $source)
{
    foreach($source as $sce){
        try{
            if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $sce)){
                throw new Exception("base64地址自动过滤!!!");
                continue;
            }
            $originLink = getLink($domain, $sce);

            stdout('目标地址:' . $originLink, 'blue');
            if (empty($originLink)){
                throw new Exception("非法地址： $sce ,自动过滤!!!");
                continue;
            }

            $link = strpos($originLink, '?') === false ? $originLink : substr($originLink, 0, strrpos($originLink, '?'));
            $relativePath = strstr(substr(strrchr($link, '://'), 3), '/');//相对路径
            $extension = substr(strrchr($relativePath, '.'), 1);//扩展名

            stdout('路径:' . $relativePath . '    扩展名:' . $extension, 'yellow');

            $savePath = $root . '/' . trim($relativePath, '/');
            if (!makeDir(dirname($savePath))){
                throw new Exception("创建目录 $savePath 失败");
                continue;
            }
            $content = @file_get_contents($originLink);
            if ($content){
                file_put_contents($savePath, $content);
                stdout('下载完成:' . $sce);
                stdout('保存路径' . $savePath);
            }else{
                throw new Exception('404 file not found !!!');
            }


        }catch(\Throwable $e){
            stdout($e->getMessage(), 'red');
        }

        echo str_repeat(PHP_EOL, 2);
    }
}


function run($url)
{
    $url = rtrim($url, '/');
    stdout('处理地址:' . $url);
    list($host, $domain, $path) = parseUrl($url);
    $root = DEMO_SAVE_DIR_PATH . '/' . $host;
    $logFile = LOG_SAVE_DIR_PATH . '/' . $host . '.php';
    $logs = spiderLog($logFile);
    if (!IGNORE_HISTORY && in_array($url, $logs)){
        stdout($url . ' 已爬取!!!!', 'red');
        return;
    }
    /**
     * @var $query QueryList
     */
    $query = QueryList::getInstance()->use(PhantomJs::class, __DIR__ . '/../exefile/phatomjs/bin/phantomjs.exe')->browser(function (RequestInterface $r) use ($url){
        $r->setMethod('GET');
        $r->setUrl($url);
        $r->setTimeout(10000);
        $r->setDelay(5);

        return $r;
    });
    $html = $query->getHtml();
    //保存html
    $htmlPath = $root . '/' . trim($path == '/' ? 'index.html' : $path, '/');
    makeDir(dirname($htmlPath)) && file_put_contents($htmlPath, $html);

    //保存静态资源
    $staticSource = [
        'image' => $query->find('img')->attrs('src')->all(),
        'css' => $query->find('link')->attrs('href')->all(),
        'js' => $query->find('script')->attrs('src')->all()
    ];
    $pages = $query->find('a')->attrs('href')->all();

    $otherSource = [];
    foreach(array_merge([$url], $staticSource['css']) as $ss){
        $ssLink = getLink($domain, $ss);
        if (empty($ssLink)){
            continue;
        }
        $containSource = getStatic($ssLink);
        !empty($containSource) && $otherSource = array_merge($otherSource, $containSource);
    }
    $staticSource['other'] = $otherSource;

    foreach($staticSource as $sType => $source){
        stdout("处理 $sType 资源！！！");
        if (empty($source)){
            continue;
        }
        saveStatic($root, $domain, $source);
    }

    //记入日志
    spiderLog($logFile, $url);
    foreach($pages as $alink){
        $link = getLink($domain, $alink);
        if (empty($link)){
            continue;
        }

        list($host, $targetDomain) = parseUrl($link);
        //只抓取同域名下的页面
        if ($domain != $targetDomain){
            continue;
        }
        run($link);
    }

}

/**
 * 主运行
 */
$data = [
    'demo_save_dir_path' => 'demo',
    'log_save_dir_path' => 'log'
];
$configFile = __DIR__ . '/config.json';
$config = [];
if (file_exists($configFile)){
    $config = json_decode(file_get_contents($configFile), 'true');
    stdout('用户配置信息:');
    print_r($config);
    !$config && exit('配置文件格式错误!!!');
}

defined('DEMO_SAVE_DIR_PATH') or define('DEMO_SAVE_DIR_PATH', __DIR__ . '/' . trim($config['demo_save_dir_path'] ?? __DIR__ . '/demo/'));
defined('LOG_SAVE_DIR_PATH') or define('LOG_SAVE_DIR_PATH', __DIR__ . '/' . trim($config['log_save_dir_path'] ?? __DIR__ . '/log/'));
defined('IGNORE_HISTORY') or define('IGNORE_HISTORY', isset($config['ignore_history']) ? boolval($config['ignore_history']) : true);

$url = $config['target'] ?? '';

empty($url) && exit('请输入爬取目标地址');

list($host, $domain) = parseUrl($url);

empty($link = getLink($domain, $url)) ? exit('地址不合法,请以http:// 或 https://开头') : run($link);


