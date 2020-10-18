<?php
/**
 * Created by PhpStorm.
 * User: fangying.zhong
 * Date: 2020/8/31 0031
 * Time: 15:50
 */

//获取地址
function getLink($domain,$url){
    $link = '';
    if(!preg_match('/^https*:\/\//',$domain)){
        return $link;
    }
    $domain = rtrim($domain,'/');
    if(preg_match('/^(\/\/)\w+/',$url)){
        $link = 'http:'.$url;
    }elseif(preg_match('/^((\/)\w+)|((\.\/)\w+)/',$url)){
        $link = $domain.trim($url,' .');
    }elseif (preg_match('/^(https*)/',$url)){
        $link = $url;
    }elseif(preg_match('/^[^.\/(javascript)#]*\w+/',$url)){
        $link  = $domain.'/'.$url;
    }
    return $link;
}

/**文件备份
 * @param $originFileName
 * @param $barkFileName
 * @return bool
 */
function barkFile($originFileName, $barkFileName)
{
    if (!file_exists($originFileName)){
        return false;
    }
    $dir = dirname($barkFileName);
    if (!makeDir($dir)){
        return false;
    }

    return copy($originFileName, $barkFileName);
}

/**文件重命名
 * @param $originFileName
 * @param $renameFileName
 * @return bool
 */
function renameFile($originFileName, $renameFileName)
{
    if (!file_exists($originFileName)){
        return false;
    }
    $dir = dirname($renameFileName);
    if (!makeDir($dir)){
        return false;
    }

    return rename($originFileName, $renameFileName);
}


/**创建目录
 * @param $dir
 * @return bool
 */
function makeDir($dir)
{
    try{
        if (!is_dir($dir)){
            mkdir($dir, 0755, true);
        }
        if (!is_writable($dir)){
            chmod($dir, 0755);
        }
    }catch(\Throwable $e){
        return false;
    }

    return true;
}

/**创建文件
 * @param $fileName
 * @return bool
 */
function makeFile($fileName)
{
    $fileName = createFile($fileName);
    $dir = dirname($fileName);
    if (!makeDir($dir)){
        return false;
    }
    touch($fileName);

    return $fileName;
}

//文件名过滤
function filterInputFile($file)
{
    return trim($file, "./\\ \t\n\r\0\x0B");
}

//输出
function out($data)
{
    echo json_encode($data);
    exit();
}

/**打包文件为zip压缩包
 * @param $dir
 * @param $targe
 * @return bool
 */
function pkgFileToZip($dir, $targe)
{

    $addFileToZip = function ($dir, $zip) use (&$addFileToZip){
        if (is_dir($dir)){
            if ($handle = opendir($dir)){
                while(($file = readdir($handle)) !== false){    //循环  ， readdir返回一个目录里的文件
                    if ($file != "." && $file != ".."){   //判断文件类型
                        if (is_dir($dir . '/' . $file)){   //如果是目录则递归
                            $addFileToZip($dir . '/' . $file, $zip);
                        }else{
                            $zip->addFile($dir . '/' . $file);
                        }
                    }

                }
                closedir($handle);
            }
        }else{
            $zip->addFile($dir);
        }
    };


    //清除历史文件
    if (file_exists($targe)){
        unlink($targe);
    }
    /** @var  $zip ZipArchive */
    $zip = new ZipArchive();
    if ($zip->open($targe, ZipArchive::CREATE) === true){
        if (is_array($dir)){
            foreach($dir as $d){
                $addFileToZip($d, $zip);
            }
        }else{
            $addFileToZip($dir, $zip);
        }
        $zip->close();
    }

    return file_exists($targe);
}

//下载文件
function dl_file($file)
{
    //First, see if the file exists

    if (!is_file($file)){
        die ("<b>404 File not found!</b>");
    }

    // Gather relevent info about file
    $len = filesize($file);
    $filename = basename($file);
    $file_extension = strtolower(substr(strrchr($filename, "."), 1));

    // This will set the Content-Type to the appropriate setting for the file
    switch($file_extension){
        case "pdf" :
            $ctype = "application/pdf";
            break;
        case "exe" :
            $ctype = "application/octet-stream";
            break;
        case "zip" :
            $ctype = "application/zip";
            break;
        case "doc" :
            $ctype = "application/msword";
            break;
        case "xls" :
            $ctype = "application/vnd.ms-excel";
            break;
        case "ppt" :
            $ctype = "application/vnd.ms-powerpoint";
            break;
        case "gif" :
            $ctype = "image/gif";
            break;
        case "png" :
            $ctype = "image/png";
            break;
        case "jpeg" :
        case "jpg" :
            $ctype = "image/jpg";
            break;
        case "mp3" :
            $ctype = "audio/mpeg";
            break;
        case "wav" :
            $ctype = "audio/x-wav";
            break;
        case "mpeg" :
        case "mpg" :
        case "mpe" :
            $ctype = "video/mpeg";
            break;
        case "mov" :
            $ctype = "video/quicktime";
            break;
        case "avi" :
            $ctype = "video/x-msvideo";
            break;

        // The following are for extensions that shouldn't be downloaded
        // (sensitive stuff, like php files)
        case "php" :
        case "htm" :
        case "html" :
        case "txt" :
            die ("<b>Cannot be used for " . $file_extension . " files!</b>");
            break;

        default :
            $ctype = "application/force-download";
    }


    $file_temp = fopen($file, "r");


    // Begin writing headers
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    // Use the switch-generated Content-Type
    header("Content-Type: $ctype");
    // Force the download
    $header = "Content-Disposition: attachment; filename=" . $filename . ";";
    header($header);
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: " . $len);


    //@readfile ( $file );
    echo fread($file_temp, filesize($file));
    fclose($file_temp);

    exit ();
}

//重名文件处理
function createFile($fileName, $n = '')
{
    static $oldFile;
    if (!file_exists($fileName)){
        $oldFile = null;

        return $fileName;
    }

    if (empty($oldFile)){
        $oldFile = $fileName;
    }
    $n++;
    $node = strripos($oldFile, '.');
    $fileName = substr($oldFile, 0, $node) . "($n)" . substr($oldFile, $node);

    return createFile($fileName, $n);
}

//输出
function stdout($string, $color = 'green')
{
    $colorArr = [
        'black' => 30,
        'red' => 31,
        'green' => 32,
        'yellow' => 33,
        'blue' => 34,
        'white' => 37

    ];


    $format = '1;1;';
    $code = $format . $colorArr[$color];

    $content = "\033[0m" . ($code !== '' ? "\033[" . $code . 'm' : '') . $string . "\033[0m";

    echo date('Y-m-d H:i:s'), '   ', $content, PHP_EOL;

}

//爬取过的日志
function spiderLog($fileName,$link=null)
{
    $dir = dirname($fileName);
    makeDir($dir);
    $fileData = file_exists($fileName) ? include($fileName) : [];
    if(empty($link)){
        return $fileData;
    }
    if(!in_array($link,$fileData)){
        $fileData[]=$link;
        $dataArr = "<?php \n return \n" . var_export($fileData, true) . ";";
        return file_put_contents($fileName, $dataArr) > 0;
    }
    return true;
}