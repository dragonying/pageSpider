<?php
/**
 * Created by PhpStorm.
 * User: fangying.zhong
 * Date: 2020/12/19 0019
 * Time: 22:39
 */
$handler = fopen('dytest.txt','r'); //打开文件
$info = [];
while(!feof($handler)){
//    $m[] = fgets($handler,4096); //fgets逐行读取，4096最大长度，默认为1024
    $str = fgets($handler,4096);
    preg_match("/昵称:(.*)\S*短ID：\s*(\d+)\s*抖音号/",$str,$match);
//    print_r($match);
    is_numeric($match[1]) && $numberArr[]=$match[1];
    $info[]=[
        'name'=>$match[1],
        'num'=>$match[2]
    ];
}

fclose($handler); //关闭文件

//输出文件
echo '<pre>';
print_r($info);
//print_r($m);
file_put_contents('dyuser.json',json_encode($info));