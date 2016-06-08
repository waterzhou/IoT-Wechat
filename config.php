<?php
/**
 * Created by Atmel.
 * User: waterzhou
 * Date: 2016/4/26
 * Time: 9:26
 */

//正式测试公号/服务公号的配置参数
define("WX_TOKEN", "weixin");  //TOKEN，必须和微信绑定的URL使用的TOKEN一致
//smdzjl@sina.com  wx32f73ab219f56efb
//714846578@qq.com wxb53c6e72971723ad
define("WX_APPID", "wxbeef7ada97d832cf");  //
define("WX_ENCODINGAESKEY", "7Tp1NIUzUa0JBezeJUjG8O61Kdjcu2ce6BQVukZlv3u");   //填写加密用的EncodingAESKey，如接口为明文模式可忽略
define("WX_APPSECRET", "9db75f9a357b6d90f60c032af2cd3191");  //填写高级调用功能的app id, 请在微信开发模式后台查询
define("WX_DEBUG", true);
define("WX_LOGCALLBACK", true);

//不同的方式来确定本机运行环境，还是服务器运行环境，本来想获取Localhost来进行判断，但没成功
//实验了不同的方式，包括$_SERVER['LOCAL_ADDR']， $_SERVER['SERVER_ADDR']， getenv('SERVER_ADDR')等方式
//GetHostByName($_SERVER['SERVER_NAME'])只能获取IP地址，也不稳定
//使用php_uname('n') == "CV0002816N4")也算是一个不错的方式，但依然丑陋，需要每个测试者单独配置，
//也可以使用云服务器的名字来反向匹配，因为服务器的名字是唯一的
//SAE官方的说法：可以使用isset(SAE_TMP_PATH)来判断是不是在SAE云上
//
if ($_SERVER['SERVER_NAME'] == "flyingfishzhoutest.applinzi.com") //smdzjl.sinaapp.com 服务器数据库配置信息
{
    define("WX_DBHOST", SAE_MYSQL_HOST_M);    //连接的服务器地址 w.rdc.sae.sina.com.cn
    define("WX_DBUSER",SAE_MYSQL_USER);     //连接数据库的用户名
    define("WX_DBPSW", SAE_MYSQL_PASS);        //连接数据库的密码
    define("WX_DBNAME","app_flyingfishzhoutest");         //连接的数据库名称 app_smdzjl
    define("WX_DBPORT", SAE_MYSQL_PORT);
    define("WX_DBHOST_S", SAE_MYSQL_DB);
}else   //CV0002816N4  本地配置数据库信息
{
    define("WX_DBHOST", "localhost");    //连接的服务器地址
    define("WX_DBUSER","root");     //连接数据库的用户名
    define("WX_DBPSW", "qwerty123");        //连接数据库的密码
    define("WX_DBNAME","watertest1");         //连接的数据库名称
    define("WX_DBPORT", 3306);           //缺省设置
    define("WX_DBHOST_S", "");          //无效
}

//测试公号的后台运行配置参数
define("WX_TOOL_SERVICENUM", "gh_53744ba5ec71");
define("WX_TOOL_APPID", "wxbeef7ada97d832cf");
define("WX_TOOL_APPSECRET", "9db75f9a357b6d90f60c032af2cd3191");
define("WX_TOOL_WIFIMAC", "F8F005F0023D");

//层三处理消息的定义
define("L3_MAGIC_BL", 0xFECF);
define("L3_HEAD_LENGTH", 12);
define("CMDID_SEND_TEXT_REQ", 0x1);    //HW -> CLOUD
define("CMDID_SEND_TEXT_RESP", 0x1001);   //CLOUD ->HW
define("CMDID_OPEN_LIGHT_PUSH", 0x2001);  //CLOUD ->HW
define("CMDID_CLOSE_LIGHT_PUSH", 0x2002);   //CLOUD ->HW
define("CMDID_HW_VERSION_REQ", 0x3001);
define("CMDID_HW_VERSION_RESP", 0x3002);
define("CMDID_HW_VERSION_PUSH", 0x3003);
define("CMDID_TIME_SYN_PUSH", 0x3004);
define("CMDID_EMC_DATA_REQ", 0x4001);  //电磁波辐射测试量，定时测量方式
define("CMDID_EMC_DATA_RESP", 0x4002);
define("CMDID_EMC_DATA_PUSH", 0x4003);  //主动强制要求的测量
define("CMDID_EMC_DATA_REV", 0x4004);
define("CMDID_OCH_DATA_REQ", 0x4010);  //酒精测试量
define("CMDID_OCH_DATA_RESP", 0x4011);
?>
