<?php
/**
 * Created by PhpStorm.
 * User: fangying.zhong
 * Date: 2020/11/16 0016
 * Time: 21:54
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/function.php';

use QL\QueryList;
use QL\Ext\PhantomJs;
use JonnyW\PhantomJs\Http\RequestInterface;

//echo strtotime('2020-12-13 11:05:00');die;
//echo ip2long('1.196.177.122');die;
// type 类型 1 普通表格 2接口
defined('TYPE_TABLE') OR define('TYPE_TABLE', 1);
defined('TYPE_API') OR define('TYPE_API', 2);
defined('SAVE_IP_PATH') OR define('SAVE_IP_PATH', __DIR__ . '/iplog/ip.php');
defined('UPDATE_TIME') OR define('UPDATE_TIME', 6*3600);

//$fileInfo = pathinfo(SAVE_IP_PATH);
//echo $fileInfo['dirname'].'/'.$fileInfo['filename'].'.json';die;

//查询
function query($url, $rules, $range = [], $filter = null, $callBack = null)
{
    $query = QueryList::getInstance()->use(PhantomJs::class, __DIR__ . '/../exefile/phatomjs/bin/phantomjs.exe')->browser(function (RequestInterface $r) use ($url){
        $r->setMethod('GET');
        $r->setUrl($url);
        $r->setTimeout(10000);
        $r->setDelay(mt_rand(1, 3));
        $r->setHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.4158.5 Safari/537.36'
        ]);

        return $r;
    });
    //        print_r($query->getHtml());die;
    $data = $query->rules($rules)->range($range)->query()->getData(function ($item) use ($callBack){
        if (is_callable($callBack)){
            $item = $callBack($item);
        }

        return $item;
    })->all();
    if (is_callable($filter)){
        $data = array_filter($data, $filter);
    }

    return $data;
}

//日志
function ipLog($fileName, $info = null)
{

    $fileInfo = pathinfo($fileName);
    $jsonFile = $fileInfo['dirname'] . '/' . $fileInfo['filename'] . '.json';

    $keyword = ip2long($info['ip']);
    $dir = dirname($fileName);
    makeDir($dir);
    $fileData = file_exists($fileName) ? include($fileName) : [];
    if (!isset($fileData[$keyword])){
        $fileData[$keyword] = $info;
        $dataArr = "<?php \n return \n" . var_export($fileData, true) . ";";

        file_put_contents($jsonFile, json_encode($fileData));

        return file_put_contents($fileName, $dataArr) > 0;
    }

    return true;
}

// 资源列表
$source = [
    'kuaidaili' => [
        'url' => 'https://www.kuaidaili.com/free/inha/%u',
        'rules' => [
            'ip' => ['td:eq(0)', 'text'],
            'port' => ['td:eq(1)', 'text'],
            'hideType' => ['td:eq(2)', 'text'],
            'protocol' => ['td:eq(3)', 'text'],
            'position' => ['td:eq(4)', 'text'],
            'speed' => ['td:eq(5)', 'text'],
            'validTime' => ['td:eq(6)', 'text']
        ],
        'range' => 'table tbody tr',
        'type' => TYPE_TABLE,
        'filter' => function ($v){
            return time() - strtotime($v['validTime']) < UPDATE_TIME;
        }
    ],
    'jiangxianli' => [
        'url' => 'https://ip.jiangxianli.com/?page=%u',
        'rules' => [
            'ip' => ['td:eq(0)', 'text'],
            'port' => ['td:eq(1)', 'text'],
            'hideType' => ['td:eq(2)', 'text'],
            'protocol' => ['td:eq(3)', 'text'],
            'position' => ['td:eq(4)', 'text'],
            'speed' => ['td:eq(7)', 'text'],
            'validTime' => ['td:eq(9)', 'text'],
        ],
        'range' => '.layui-table tbody tr',
        'type' => TYPE_TABLE,
        'filter' => function ($v){
            return time() - strtotime($v['validTime']) < UPDATE_TIME;
        }
    ],
    '89ip' => [
        'url' => 'https://www.89ip.cn/index_%u.html',
        'rules' => [
            'ip' => ['td:eq(0)', 'text'],
            'port' => ['td:eq(1)', 'text'],
            'position' => ['td:eq(2)', 'text'],
            'validTime' => ['td:eq(4)', 'text'],
        ],
        'range' => '.layui-table tbody tr',
        'type' => TYPE_TABLE,
        'filter' => function ($v){
            return time() - strtotime($v['validTime']) < UPDATE_TIME;
        }
    ],
    'ip3366' => [
        'url' => 'http://www.ip3366.net/?stype=1&page=%u',
        'rules' => [
            'ip' => ['td:eq(0)', 'text'],
            'port' => ['td:eq(1)', 'text'],
            'hideType' => ['td:eq(2)', 'text'],
            'protocol' => ['td:eq(3)', 'text'],
            'position' => ['td:eq(5)', 'text'],
            'speed' => ['td:eq(6)', 'text'],
            'validTime' => ['td:eq(7)', 'text'],
        ],
        'range' => 'table tbody tr',
        'type' => TYPE_TABLE,
        'filter' => function ($v){
            return time() - strtotime($v['validTime']) < UPDATE_TIME;
        }
    ],
    'data5u' => [
        'url' => 'http://www.data5u.com/',
        'rules' => [
            'ip' => ['span:eq(0)', 'text'],
            'port' => ['span:eq(1)', 'text'],
            'hideType' => ['span:eq(2)', 'text'],
            'protocol' => ['span:eq(3)', 'text'],
            'position' => ['span:eq(5)', 'text'],
            'speed' => ['span:eq(7)', 'text'],
            'validTime' => ['span:eq(8)', 'text'],
        ],
        'type' => TYPE_TABLE,
        'range' => '.wlist ul.l2',
        'once' => true
    ],
    'superfastip' => [
        'url' => 'https://api.superfastip.com/ip/freeip?page=%u',
        'type' => TYPE_API,
        'keyword' => 'freeips',
        'map' => [
            'ip' => 'ip',
            'port' => 'port',
            'level' => 'hideType',
            'country' => 'position',
            'connect_speed' => 'speed',
            'verify_time' => 'validTime'
        ],
        'filter' => function ($v){
            return time() - strtotime($v['validTime']) < UPDATE_TIME;
        }
    ],
    'xiladaili_1' => [
        'url' => 'http://www.xiladaili.com',
        'rules' => [
            'ip' => ['td:eq(0)', 'text'],
            'hideType' => ['td:eq(1)', 'text'],
            'protocol' => ['td:eq(2)', 'text'],
            'position' => ['td:eq(3)', 'text'],
            'speed' => ['td:eq(4)', 'text'],
            'validTime' => ['td:eq(6)', 'text'],
        ],
        'type' => TYPE_TABLE,
        'range' => 'table:eq(0) tbody tr',
        'filter' => function ($v){
            return time() - strtotime($v['validTime']) < UPDATE_TIME;
        },
        'callBack' => function ($v){
            list($v['ip'], $v['port']) = explode(':', $v['ip']);
            $v['validTime'] = str_replace(['月', '日', '年'], '/', $v['validTime']);

            return $v;
        },
        'once' => true,//是否一次性获取
    ],
    'xiladaili_2' => [
        'url' => 'http://www.xiladaili.com',
        'rules' => [
            'ip' => ['td:eq(0)', 'text'],
            'hideType' => ['td:eq(1)', 'text'],
            'protocol' => ['td:eq(2)', 'text'],
            'position' => ['td:eq(3)', 'text'],
            'speed' => ['td:eq(4)', 'text'],
            'validTime' => ['td:eq(6)', 'text'],
        ],
        'type' => TYPE_TABLE,
        'range' => 'table:eq(1) tbody tr',
        'filter' => function ($v){
            return time() - strtotime($v['validTime']) < UPDATE_TIME;
        },
        'callBack' => function ($v){
            list($v['ip'], $v['port']) = explode(':', $v['ip']);
            $v['validTime'] = str_replace(['月', '日', '年'], '/', $v['validTime']);

            return $v;
        },
        'once' => true,
    ],
    'xiladaili_3' => [
        'url' => 'http://www.xiladaili.com',
        'rules' => [
            'ip' => ['td:eq(0)', 'text'],
            'hideType' => ['td:eq(1)', 'text'],
            'protocol' => ['td:eq(2)', 'text'],
            'position' => ['td:eq(3)', 'text'],
            'speed' => ['td:eq(4)', 'text'],
            'validTime' => ['td:eq(6)', 'text'],
        ],
        'type' => TYPE_TABLE,
        'range' => 'table:eq(2) tbody tr',
        'filter' => function ($v){
            return time() - strtotime($v['validTime']) < UPDATE_TIME;
        },
        'callBack' => function ($v){
            list($v['ip'], $v['port']) = explode(':', $v['ip']);
            $v['validTime'] = str_replace(['月', '日', '年'], '/', $v['validTime']);

            return $v;
        },
        'once' => true,
    ],
];

$target = 'https://ip.ihuan.me/?page=881aaf7b5';//待解决
$target = 'http://www.goubanjia.com/';//待解决
//
//$obj = $source['kuaidaili'];
//$rules = $obj['rules'];
//$target = $obj['url'];
//$type = $obj['type'];
//$map = $obj['map'] ?? [];
//$keyword = $obj['keyword'] ?? '';
//$range = $obj['range'] ?? [];
//$filter = $obj['filter'] ?? null;
//$callBack = $obj['callBack'] ?? null;
//$once = $obj['once'] ?? false;

//$filter = function ($v){
//    return time() - strtotime($v['validTime']) < 86400;
//};
//$callBack = function ($v){
//    list($v['ip'], $v['port']) = explode(':', $v['ip']);
//    $v['validTime'] = str_replace(['月', '日', '年'], '/', $v['validTime']);
//
//    return $v;
//};

foreach($source as $key => $obj){
    $rules = $obj['rules'];
    $target = $obj['url'];
    $type = $obj['type'];
    $map = $obj['map'] ?? [];
    $keyword = $obj['keyword'] ?? '';
    $range = $obj['range'] ?? [];
    $filter = $obj['filter'] ?? null;
    $callBack = $obj['callBack'] ?? null;
    $once = $obj['once'] ?? false;

    // 爬取
    $i = 0;
    while(true){
        $i++;
        $data = [];
        $url = sprintf($target, $i);
        stdout($url . ' ------- starting');
        switch($type){
            case TYPE_TABLE:// 普通表格分页
                $data = query($url, $rules, $range, $filter, $callBack);
                break;
            case TYPE_API: // 接口类
                try{
                    $res = json_decode(file_get_contents($url), true);
                    $data = array_map(function ($item) use ($map, $keyword){
                        $rt = [];
                        foreach($item as $k => $v){
                            isset($map[$k]) && $rt[$map[$k]] = $v;
                        }

                        return $rt;
                    }, $keyword ? $res[$keyword] : $res);
                }catch(\Throwable $e){
                    $data = [];
                }

        }
        if (!empty($data)){
            foreach($data as $val){
                $val['origin'] = $target;
                ipLog(SAVE_IP_PATH, $val);
            }
            stdout($url . ' ------- done');

        }else{
            stdout($url . ' ------- 没有数据', 'red');
            break;
        }

        if ($once){
            break;
        }

    }
}






