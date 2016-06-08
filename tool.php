<?php
/**
 * Created by PhpStorm.
 * User: jianlinz
 * Date: 2015/7/1
 * Time: 19:52
 */

/**Start of tool_main
 * 本工具用于管理员生成设备二维码，并测试扫码和绑定设备功能
 *
 *
 * 工作步骤如下
 * 1、申请测试账号
 * 2、在config.properties中配置公众平台账号相关信息及token
 * 3、服务端部署，在测试号中配置服务url和token
 * 4、公众平台开发介绍，服务端代码介绍，创建自定义菜单
 * 5、修改demo服务器代码灯泡通信协议为设备自定义协议
 * 6、确定设备id，生成二维码；确定设备参数，对设备进行授权
 * 7、和设备联调
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
$wxObj = new class_wechat_sdk($wx_options);
echo "<br><H2>微信硬件工作环境即将开始......<br></H2>";
$wxDevObj = new class_wx_IOT_sdk(WX_APPID, WX_APPSECRET);


/* 暂时不需要的功能，已经分解到不同的功能组件，放入后台管理工具之中了
** 留在这儿，只是为了测试目的
 *
 *
 *
 *
//Step1:刷新Token
echo "<br><H2>微信硬件工作环境即将开始......<br></H2>";
$wxObj = new class_wx_IOT_sdk(WX_TOOL_APPID, WX_TOOL_APPSECRET);
//实验Token是否已经被刷新
echo "<br>测试最新刷新的Token=<br>"."$wxObj->access_token"."<br>";

//Step2:测试创建微信界面上自定义的菜单
static $self_create_menu = '{"button":[{"name":"绑操",
            "sub_button":[{"type":"click","name":"绑定","key":"CLICK_BIND"},
                            {"type":"click","name":"解绑","key":"CLICK_UNBIND"},
                            {"type":"click","name":"查询","key":"CLICK_BIND_INQ"}]},
    {"name":"开关",
            "sub_button":[{"type":"click","name":"开灯","key":"CLICK_LIGHT_ON"},
                            {"type":"click","name":"关灯","key":"CLICK_LIGHT_OFF"},
                            {"type":"click","name":"查询","key":"CLICK_LIGHT_INQ"}]},
    {"name":"测试",
            "sub_button":[{"type":"click","name":"关于","key":"CLICK_ABOUT"},
                            {"type":"click","name":"技术","key":"CLICK_TECH"},
                            {"type":"click","name":"测试","key":"CLICK_TEST"}]}
    ]}';
echo "<br>自定义菜单创建（先删再建-微信界面需要24小时更新，重新关注可立即刷新） <br>";
var_dump($wxObj->delete_menu());
var_dump($wxObj->create_menu($self_create_menu));


//Step3: 测试使用硬件API创建二维码
//为硬件设备创建二维码，DeviceID自动生成(APPID+随机码组成），也可以自行赋值
//二维码显示在界面上，授权后才能进行扫描绑定
//设备id 由厂商指定，建议由字母、数字、下划线组成，以免json解析失败。
//Step3.1: 使用设备ID和二维码分离的方式创建，一直不成功
// 生成并显示设备ID
//
//$deviceIdBLE= $wxObj->generateDeviceId();
//echo "DeviceID=".$deviceIdBLE."<br>";
//使用API创建设备二维码
//$qrcodetmp = $wxObj->create_qrcodebyDeviceid($deviceIdBLE);
//echo "<br>二维码及Ticket为： <br>".$qrcodetmp."<br>";
//将二维码使用图像方式显示出来
//var_dump($wxObj->createQrcodeDisplan("https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=".$qrcodetmp));
//使用API验证二维码
//echo "<br>二维码验证的结果<br>";
//var_dump($wxObj->verify_qrcode($qrcodetmp));
//
//Step3.2: 使用DeviceId + QrCod一起创建的方式
$qrcode_result = $wxDevObj->create_DeviceidAndQrcode();
var_dump($qrcode_result);
$deviceIdBLE = $qrcode_result["deviceid"];
$qrcodeBLE = $qrcode_result["qrticket"];
//验证二维码
$qrcode_result = $wxDevObj->verify_qrcode($qrcodeBLE);
echo "<br>二维码验证的结果<br>";
var_dump($qrcode_result);
$deviceTypeBLE = $qrcode_result["device_type"];
//将二维码使用图像方式显示出来
var_dump($wxDevObj->create_qrcodeDisplay($qrcodeBLE));

// Step4 设备授权
// 设备授权后才能进行扫码绑定
// 开发调试时，可以先用假的信息授权，测试服务端设备绑定事件的处理。
// 设备参数确定后，更新为正确的设备属性，再和设备联调。
echo "<br>设备授权结果 <br>";
var_dump($wxDevObj->device_AuthWifi($deviceIdBLE, WX_TOOL_WIFIMAC));

// Step5 用户扫码，或者强制用户绑定

// Step6 设备状态查询
echo "<br>查询设备状态 <br>";
var_dump($wxDevObj->getstat_qrcodebyDeviceid($deviceIdBLE));

// Step7 用户绑定设备查询
echo "<br>查询用户绑定状态 <br>";
$result = $wxDevObj->getstat_qrcodebyOpenId("oAjc8uKl-QS9EGIfRGb81kc9fdJE");
var_dump($result);
echo "<br>Device_List =  <br>";
var_dump($result["device_list"]);

// Step8 强制绑定设备
$result = $wxDevObj->compel_bind($deviceIdBLE, "oAjc8uKl-QS9EGIfRGb81kc9fdJE");
echo "<br>绑定设备结果  <br>";
var_dump($result);

// Step9 数据库测试
    ///echo "<br>数据库删除结果  <br>";
    //$wxDbObj = new class_mysql_db();
    //var_dump($wxDbObj->db_BleBoundInfo_delete("oAjc8uKl-QS9EGIfRGb81kc9fdJE"));
    //
$wxDbObj = new class_mysql_db();
var_dump($wxDbObj->db_BleBoundInfo_delete("oAjc8uKl-QS9EGIfRGb81kc9fdJE"));
var_dump($wxDbObj->db_BleBoundInfo_save("oAjc8uKl-QS9EGIfRGb81kc9fdJE", $deviceIdBLE, "oAjc8uKl-QS9EGIfRGb81kc9fdJE", "gh_9b450bb63282"));

//end of tool_main();

*/


?>