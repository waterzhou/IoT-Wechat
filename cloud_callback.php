<?php

// 主程序MAIN()
include_once "config.php";
include_once "wechat.class.php";
header("Content-type:text/html;charset=utf-8");

// Main()
$wx_options = array(
    'token'=>WX_TOKEN, //填写你设定的key
    'encodingaeskey'=>WX_ENCODINGAESKEY, //填写加密用的EncodingAESKey，如接口为明文模式可忽略
    'appid'=>WX_APPID,
    'appsecret'=>WX_APPSECRET, //填写高级调用功能的密钥
    'debug'=> WX_DEBUG,
    'logcallback' => WX_LOGCALLBACK
);

$wxObj = new class_wechat_sdk($wx_options);
if (isset($_GET['echostr'])) {
    $wxObj->valid_sdk01();
}else{
    $wxObj->responseMsg();
}

?>
