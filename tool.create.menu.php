<?php
/**
 * Created by PhpStorm.
 * User: jianlinz
 * Date: 2015/7/7
 * Time: 14:15
 */

/**Start of tool_main
 * 本工具用于管理员创建用户侧菜单
 *
 **/
include_once "config.php";
include_once "wechat.class.php";
header("Content-type:text/html;charset=utf-8");

$wx_options = array(
    'token'=>WX_TOKEN, //填写你设定的key
    'encodingaeskey'=>WX_ENCODINGAESKEY, //填写加密用的EncodingAESKey，如接口为明文模式可忽略
    'appid'=>WX_APPID,
    'appsecret'=>WX_APPSECRET, //填写高级调用功能的密钥
    'debug'=> WX_DEBUG,
    'logcallback' => WX_LOGCALLBACK
);
$wxObj = new class_wechat_sdk($wx_options);


//Step1:刷新Token
echo "<br><H2>微信硬件工作环境即将开始......<br></H2>";
$wxObj = new class_wx_IOT_sdk(WX_APPID, WX_APPSECRET);
//实验Token是否已经被刷新
echo "<br>测试最新刷新的Token=<br>"."$wxObj->access_token"."<br>";

//Step2:测试创建微信界面上自定义的菜单
static $self_create_menu = '{"button":[
	{"name":"绑操",
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
                            {"type":"click","name":"测试","key":"CLICK_TEST"},
                            {"type":"view","name":"页面分享测试","url":"http://smdzjl.sinaapp.com/tool.pageshare.php"}]}
    ]}';
echo "<br>自定义菜单创建（先删后建）<br>";
var_dump($wxObj->delete_menu());
var_dump($wxObj->create_menu($self_create_menu));

?>