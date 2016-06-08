<?php
/**
 * Created by PhpStorm.
 * User: jianlinz
 * Date: 2015/7/1
 * Time: 19:52
 */

/*
 * 本工具用于管理员在后台强制绑定用户与设备

 *
**/
include_once "config.php";
include_once "wechat.class.php";
header("Content-type:text/html;charset=utf-8");
//如果运行在本地，以下地址存放二维码图片
static $imagePath = "D:/work/image/";

$wx_options = array(
    'token'=>WX_TOKEN, //填写你设定的key
    'encodingaeskey'=>WX_ENCODINGAESKEY, //填写加密用的EncodingAESKey，如接口为明文模式可忽略
    'appid'=>WX_APPID,
    'appsecret'=>WX_APPSECRET, //填写高级调用功能的密钥
    'debug'=> WX_DEBUG,
    'logcallback' => WX_LOGCALLBACK
);
$wxObj0 = new class_wechat_sdk($wx_options);


$deviceid = $_POST["subscribe_deviceid"];
$openid = $_POST["subscribe_openid"];
echo "Input Device ID = " . $deviceid ."<br>";
echo "Input User ID = " . $openid ."<br>";

//Step1:刷新Token
echo "<br><H2>微信硬件工作环境即将开始......<br></H2>";
$wxObj = new class_wx_IOT_sdk(WX_APPID, WX_APPSECRET);
//实验Token是否已经被刷新
echo "<br>测试最新刷新的Token=<br>"."$wxObj->access_token"."<br>";


// Step2 设备状态查询
echo "<br>查询设备状态 <br>";
var_dump($wxObj->getstat_qrcodebyDeviceid($deviceid));

// Step3 用户绑定设备查询
echo "<br>查询用户绑定状态 <br>";
$result = $wxObj->getstat_qrcodebyOpenId($openid);
var_dump($result);
echo "<br>Device_List =  <br>";
$devicetype = $result["device_list"][0]["device_type"];
var_dump($result["device_list"]);

// Step4 微信云强制绑定设备
$result = $wxObj->compel_bind($deviceid, $openid);
echo "<br>微信云强制绑定设备结果  <br>";
var_dump($result);

// Step5 第三方数据库绑定
$wxDbObj = new class_mysql_db();
echo "<br>第三方云数据库查询设备结果  <br>";
var_dump($wxDbObj->db_BleBoundInfo_query($openid));
echo "<br>第三方云数据库强绑设备结果  <br>";
var_dump($wxDbObj->db_BleBoundInfo_save($openid, $deviceid, $openid, $devicetype));

//end of tool_main();
?>