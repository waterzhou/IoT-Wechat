<?php
/**
 * Created by PhpStorm.
 * User: jianlinz
 * Date: 2015/7/2
 * Time: 20:12
 */

include_once "config.php";

//BXXH硬件设备级 Layer 2.2 SDK02，用户设备级接入到微信系统中
class class_wx_IOT_sdk
{
    var $appid = "";
    var $appsecret = "";

    /** 函数列表开始
     * public function __construct($appid = NULL, $appsecret = NULL)
     * public function get_user_list($next_openid = NULL)
     * get_user_info($openid)
     * public function create_menu($data)
     * public function send_custom_message($touser, $type, $data)
     * public function create_qrcode($scene_type, $scene_id)
     * public function create_group($name)
     * public function update_group($openid, $to_groupid)
     * public function upload_media($type, $file)
     * protected function https_request($url, $data = null)
     * //硬件设备部分的处理
     * public function receive_deviceMessage($data = null, $content)
     * public function xms_responseDeviceText($toUser, $fromUser, $deviceType, $deviceID, $sessionID, $content)
     * public function xms_responseDeviceEvent($toUser, $fromUser, $event, $deviceType, $deviceID, $sessionID, $content)
     * public function L3_deviceL3msgReceive($optType, $content)
     * public function trans_msgtodevice($deviceType, $deviceId, $openId, $content)
     * public function get_openIDbyDeviceId($deviceType, $deviceId)
     * public function create_qrcodebyDeviceid($deviceId)
     * public function device_AuthWifi($deviceId)
     * public function device_AuthAndUpdate($authKey, $deviceId, $mac, $isCreate)
     * public function getstat_qrcodebyDeviceid($deviceId)
     * MSG_1.8/wechat_class public function transmitSocialMessage($object)
     * public function verify_qrcode($ticket)
     * MSG_1.10.2 WIFI设备消息接口
     * public function trans_msgtodeviceWIFI($deviceType, $deviceId, $openId, $msgType, $device_status)
     * public function create_DeviceidAndQrcode()
     * public function create_qrcodeDisplay($url)
     * public function notify_bindSuccessful($ticket, $deviceId, $openId)
     * public function notify_unbindSuccessful($ticket, $deviceId, $openId)
     * public function compel_bind($deviceId, $openId)
     * public function compel_unbind($deviceId, $openId)
     * public function getstat_qrcodebyOpenId($openId)
     * public function generateRandomString($length = 10)
     * public function generateDeviceId()
     *
     **/ //函数列表结束

    //构造函数，获取Access Token
    public function __construct($appid = NULL, $appsecret = NULL)
    {
        if ($appid) {
            $this->appid = $appid;
        }
        if ($appsecret) {
            $this->appsecret = $appsecret;
        }

        //这里的Token刷的太快，会出现超过微信设置的每天API刷新的上限问题
        //解决了Token的心病问题：官方程序使用定时器+共享中控服务器的方式，咱们这里完全采用数据库+用户业务逻辑触发，一样可靠
        //原则上，同一个Appid/Appsecrete的逻辑功能，包括不同Subscriber的操作，都
        $wxDbObj = new class_mysql_db();
        $result = $wxDbObj->db_AccessTokenInfo_inqury($appid, $appsecret);
        //2小时=7200秒为最长限度，考虑到余量，少放点
        if (($result == "NOTEXIST") || (time() > $result["lasttime"] + 6500))
        {
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $this->appid . "&secret=" . $this->appsecret;
            $res = $this->https_request($url);
            $result = json_decode($res, true);
            //下一步存在当前临时变量和数据库中
            $this->lasttime = time();
            $this->access_token = $result["access_token"];
			echo "New access token";
            $wxDbObj->db_AccessTokenInfo_save($appid, $appsecret, $this->lasttime, $this->access_token);
            }
        else{
			echo "Continue old access token";
            $this->lasttime = $result["lasttime"];
            $this->access_token = $result["access_token"];
        }
    }

    //获取关注者列表
    public function get_user_list($next_openid = NULL)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/user/get?access_token=" . $this->access_token . "&next_openid=" . $next_openid;
        $res = $this->https_request($url);
        return json_decode($res, true);
    }

    //获取用户基本信息
    public function get_user_info($openid)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=" . $this->access_token . "&openid=" . $openid . "&lang=zh_CN";
        $res = $this->https_request($url);
        return json_decode($res, true);
    }

    //创建菜单
    //POST
    public function create_menu($data)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=" . $this->access_token;
        $res = $this->https_request($url, $data);
        return json_decode($res, true);
    }

    //删除菜单
    //GET
    public function delete_menu()
    {
        $url = "https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=" . $this->access_token;
        $res = $this->https_request($url);
        return json_decode($res, true);
    }

    //查询菜单
    //GET
    public function inquery_menu()
    {
        $url = "https://api.weixin.qq.com/cgi-bin/menu/get?access_token=" . $this->access_token;
        $res = $this->https_request($url);
        return json_decode($res, true);
    }

    //发送客服消息，已实现发送文本，其他类型可扩展
    public function send_custom_message($touser, $type, $data)
    {
        $msg = array('touser' => $touser);
        switch ($type) {
            case 'text':
                $msg['msgtype'] = 'text';
                $msg['text'] = array('content' => urlencode($data));
                break;
        }
        $url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=" . $this->access_token;
        return $this->https_request($url, urldecode(json_encode($msg)));
    }

    //生成参数二维码
    public function create_qrcode($scene_type, $scene_id)
    {
        $data = NULL;
        switch ($scene_type) {
            case 'QR_LIMIT_SCENE': //永久
                $data = '{"action_name": "QR_LIMIT_SCENE", "action_info": {"scene": {"scene_id": ' . $scene_id . '}}}';
                break;
            case 'QR_SCENE':       //临时
                $data = '{"expire_seconds": 1800, "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": ' . $scene_id . '}}}';
                break;
        }
        $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=" . $this->access_token;
        $res = $this->https_request($url, $data);
        $result = json_decode($res, true);
        return "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=" . urlencode($result["ticket"]);
    }

    //创建分组
    public function create_group($name)
    {
        $data = '{"group": {"name": "' . $name . '"}}';
        $url = "https://api.weixin.qq.com/cgi-bin/groups/create?access_token=" . $this->access_token;
        $res = $this->https_request($url, $data);
        return json_decode($res, true);
    }

    //移动用户分组
    public function update_group($openid, $to_groupid)
    {
        $data = '{"openid":"' . $openid . '","to_groupid":' . $to_groupid . '}';
        $url = "https://api.weixin.qq.com/cgi-bin/groups/members/update?access_token=" . $this->access_token;
        $res = $this->https_request($url, $data);
        return json_decode($res, true);
    }

    //上传多媒体文件
    public function upload_media($type, $file)
    {
        $data = array("media" => "@" . dirname(__FILE__) . '\\' . $file);
        $url = "http://file.api.weixin.qq.com/cgi-bin/media/upload?access_token=" . $this->access_token . "&type=" . $type;
        $res = $this->https_request($url, $data);
        return json_decode($res, true);
    }

    //https请求（支持GET和POST）
    protected function https_request($url, $data = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

    //以下部分为硬件公号相关的功能部分

    //MSG_1.1-1.2 设备通过微信发消息给第三方，以及设备绑定/解绑信息
    //这里的消息属于处理函数并发送返回消息功能
    //输入的$data属于XML解码后的数据，不是Json数据，全部是SimpleXMLElement Object结构体，只能使用指针访问
    //POST方式
    public function receive_deviceMessage($data)
    {
        $msgType = $data->MsgType;

        //经过simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA)之后，数据已经可以使用指针操作
        //一个关键点是，LIBXML_NOCDATA这个选项是否合适

        switch ($msgType)
        {
            case "device_text":
                //先推送接受到的设备消息给客服/测试人员的微信界面上
                //已经BASE64解码后的数据
                //给硬件设备发送回复消息，直接使用客服接口给微信界面
                $content = base64_decode($data->Content);
                $transMsg = "收到设备消息DEVICE_TEXT是： " . $content;
                $this->send_custom_message($data->OpenID, 'text', $transMsg);  //使用API-CURL推送客服微信用户
                //发送到L3的比特流还需要被反系列化
                $wxL3Obj = new class_L3_Process_Func();
                $rev = $wxL3Obj->L3_deviceMsgProcess("device_text", $content, $data->FromUserName, $data->DeviceID);
                // BASE64编码后，再发送给设备，使用ECHO-XML方式推送微信用户
                $tmp = base64_encode($rev);
                $transMsg = $this->xms_responseDeviceText($data->FromUserName, $data->ToUserName, $data->DeviceType, $data->DeviceID, $data->SessionID, $tmp);
                break;
            case "device_event":
                if (($data->Event == "bind") or ($data->Event == "unbind"))
                {
                    //a) 先推送信息给客户/测试人员的微信界面，但考虑到EVENT中详细的内容都是客户推送结果，故而省略以下推送
                    $inputContent = base64_decode($data->Content);  //应该是空包，即$content = ''
                    //b) 再执行绑定解绑存储，以便下一次查询
                    if ($data->Event == "bind") {
                        $wxDbObj = new class_mysql_db();
                        //其实，还需要看看是否已经绑定了，否则不应该重新绑定
                        if ($wxDbObj->db_BleBoundInfo_duplicate($data->FromUserName, $data->DeviceID, $data->OpenID, $data->DeviceType)){
                            $transMsg = "DEVICE_EVENT重复绑定，不处理";
                        }else{
                            $transMsg = "DEVICE_EVENT初次绑定成功";
                            $wxDbObj->db_BleBoundInfo_save($data->FromUserName, $data->DeviceID, $data->OpenID, $data->DeviceType);
                        }
                    //c) 这里还有个假设：解绑来自于用户的操作，Event/Ubscribe和Event_device/Ubind是分离的
                    //如果解绑逻辑触发跟Unsubscribe是一起的，这个逻辑就要改变
                    }else{
                        $db_con = new class_mysql_db();
                        $db_con->db_BleBoundInfo_delete($data->FromUserName);
                        $transMsg = "DEVICE_EVENT解绑";
                    }
                    $this->send_custom_message($data->OpenID, 'text', $transMsg);
                    //d) 最后执行可能的L3解码，处理后的内容，直接使用echo给微信界面即可
                    //因为是DEVICE_EVENT事件，设备内传输的L3信息应该是空的，反馈给设备的L3依然保留，留下一个悬念
                    //反序化及系列化，TBD
                    $wxL3Obj = new class_L3_Process_Func();
                    $tmp = $wxL3Obj->L3_deviceMsgProcess($data->Event, $inputContent, $data->FromUserName, $data->DeviceID);
                    $tmp = base64_encode($tmp); //使用ECHO-XML方式推送微信用户
                    $transMsg = $this->xms_responseDeviceEvent($data->FromUserName, $data->ToUserName, $data->Event, $data->DeviceType, $data->DeviceID, $data->SessionID, $tmp);
                    break;
                }
                else if( ($data->Event == "subscribe_status") or ($data->Event == "unsubscribe_status"))
                {
		$xmlTpl = "<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%u</CreateTime><MsgType><![CDATA[%s]]></MsgType><DeviceType><![CDATA[%s]]></DeviceType><DeviceID><![CDATA[%s]]></DeviceID><DeviceStatus>%u</DeviceStatus></xml>";
        $transMsg = sprintf($xmlTpl, $data->FromUserName, $data->ToUserName, time(), "device_status", $data->DeviceType, $data->DeviceID, 1);
 
	        } else {
			
                    $transMsg = $this->xms_responseText($data->FromUserName, $data->ToUserName,"Not support such device event");
		}
                break;
            default: //设备级的其它消息体
                $transMsg = $this->xms_responseText($data->FromUserName, $data->ToUserName,"收到未识别的设备级消息");
                break;
        } //MsgType判定结束
        return $transMsg;
    } //receive_deviceMessage处理结束

    //XML回复消息接口: DEVICE_TEXT
    public function xms_responseDeviceText($toUser, $fromUser, $deviceType, $deviceID, $sessionID, $content)
    {
        $xmlTpl = "<xml><ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%u</CreateTime>
            <MsgType><![CDATA[device_text]]></MsgType>
            <DeviceType><![CDATA[%s]]></DeviceType>
            <DeviceID><![CDATA[%s]]></DeviceID>
            <SessionID>%u</SessionID>
            <Content><![CDATA[%s]]></Content></xml>";
        $result = sprintf($xmlTpl, $toUser, $fromUser, time(), $deviceType, $deviceID, $sessionID, $content);
        return $result;
    }

    //XML回复消息接口: DEVICE_EVENT
    public function xms_responseDeviceEvent($toUser, $fromUser, $event, $deviceType, $deviceID, $sessionID, $content)
    {
        $xmlTpl = "<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%u</CreateTime><MsgType><![CDATA[device_event]]></MsgType><Event><![CDATA[%s]]></Event><DeviceType><![CDATA[%s]]></DeviceType><DeviceID><![CDATA[%s]]></DeviceID><SessionID>%u</SessionID><Content><![CDATA[%s]]></Content></xml>";
        $result = sprintf($xmlTpl, $toUser, $fromUser, time(), $event, $deviceType, $deviceID, $sessionID, $content);
        return $result;
    }

    //XML回复消息接口: TEXT
    public function xms_responseText($toUser, $fromUser,$content)
    {
        $xmlTpl = "<xml><ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[text]]></MsgType>
            <Content><![CDATA[%s]]></Content></xml>";
        $result = sprintf($xmlTpl, $toUser, $fromUser, time(), $content);
        return $result;
    }

    //API_1.3 第三方发送消息给硬件设备
    //POST方式
    //这个是在用户使用扫码关注后，才能干这件事，否则返回ErrorMsg
    //content内容是明文，Base64编码在本函数直接完成
    public function trans_msgtodevice($deviceType, $deviceId, $openId, $content)
    {
        $data = array("device_type" => $deviceType, "device_id" => $deviceId, "open_id" => $openId, "content" => base64_encode($content));
        $url = "https://api.weixin.qq.com/device/transmsg?access_token=" . $this->access_token;
        $res = $this->https_request($url, json_encode($data));
        return json_decode($res, true);
    }

    //API_1.4 获取硬件设备OPEN-ID
    //GET方式
    public function get_openIDbyDeviceId($deviceType, $deviceId)
    {
        $url = "https://api.weixin.qq.com/device/get_openid?access_token=" . $this->access_token . "&device_type=" . $deviceType . "&device_id=" . $deviceId ;
        $res = $this->https_request($url);
        return json_decode($res, true);
    }

    //API_1.5 生成硬件设备二维码
    //本函数只取出了第一个DEVICEID，并没有取出全部
    //完整的本函数应该返回完整地DEVICE_LIST
    //由于微信本身的原因，这个函数并不成功，因为根据这个生成的二维码通不过验证
    //至于BlueLight Java官方DEMO为什么可以成功，还需要进一步了解
    //POST方式
    public function create_qrcodebyDeviceid($deviceId)
    {
        $data = '{"device_num": 1, "device_id_list": ["' . $deviceId . '"]}';
        $url = "https://api.weixin.qq.com/device/create_qrcode?access_token=" . $this->access_token;
        $res = $this->https_request($url, $data);
        $result = json_decode($res, true);
        //var_dump($result);
        //return urlencode($result["code_list"][0]["ticket"]); //为了安全，做了转义
        return $result["code_list"][0]["ticket"];
    }

    //本TOOL主函数调用，设备授权调用主函数
    public function device_AuthWifi($deviceId, $mac)
    {
        $authKey = "";		//"1234567890ABCDEF1234567890ABCD11"  这里不加密
        $mac1 = $mac;//"1234567890AB";
        $isCreate = 1;	//更新设备属性
        return $this->device_AuthAndUpdate($authKey, $deviceId, $mac1, $isCreate);
    }

    /**
     * API_1.6 / API_1.11.2 设备授权
     * @param authKey 加密key
     * @param deviceId 设备id
     * @param mac 设备的mac地址
     * @param 是否首次授权： true 首次授权； false 更新设备属性
     * POST方式
     */
    public function device_AuthAndUpdate($authKey, $deviceId, $mac, $isCreate)
    {
        $device_list[0] = array(
            "id" => "$deviceId",  //设备id
            "mac" => "$mac",    //设备的mac地址 采用16进制串的方式（长度为12字节），不需要0X前缀，如： 1234567890AB
            "connect_protocol" => "4",  //设备类型 android classic bluetooth – 1 ios classic bluetooth – 2 ble – 3 wifi -- 4
            /**
             * 连接策略，32位整型，按bit位置位，目前仅第1bit和第3bit位有效（bit置0为无效，1为有效；第2bit已被废弃），且bit位可以按或置位
             * （如1|4=5），各bit置位含义说明如下：<br/>
             * 1：（第1bit置位）在公众号对话页面，不停的尝试连接设备<br/>
             * 4：（第3bit置位）处于非公众号页面（如主界面等），微信自动连接。当用户切换微信到前台时，可能尝试去连接设备，连上后一定时间会断开<br/>
             * 8：（第4bit置位），进入微信后即刻开始连接。只要微信进程在运行就不会主动断开
             */
            // 不加密时 authKey 为空字符串，crypt_method、auth_ver都为0
            // 加密时 authKey 需为符合格式的值，crypt_method、auth_ver都为1
            "auth_key" => "$authKey",  //加密key 1234567890ABCDEF1234567890ABCDEF
            "close_strategy" => "2", //1：退出公众号页面时断开 2：退出公众号之后保持连接不断开 3：一直保持连接（设备主动断开连接后，微信尝试重连）
            "conn_strategy" => "1", //连接策略
            "crypt_method" => "0", //auth加密方法  0：不加密 1：AES加密
            "auth_ver" => "0", //0：不加密的version 1：version 1
            // 低功耗蓝牙必须为-1
            "manu_mac_pos" => "-1", //表示mac地址在厂商广播manufature data里含有mac地址的偏移，取值如下： -1：在尾部、 -2：表示不包含mac地址
            "ser_mac_pos" => "-2" //表示mac地址在厂商serial number里含有mac地址的偏移，取值如下： -1：表示在尾部 -2：表示不包含mac地址 其他：非法偏移
        );  //第二部分搞定
        $device = array ("device_num" => "1", "device_list" => $device_list, "op_type" => $isCreate);
        // 调用授权
        //API 11.2, 利用Device_ID生成Json，并调用API，从而授权设备
        $url = "https://api.weixin.qq.com/device/authorize_device?access_token=" . $this->access_token;
        $res = $this->https_request($url, json_encode($device));
        return json_decode($res, true);
    }

    //API_1.7 获取硬件状态
    //只要DEVICE_ID
    //GET方式
    public function getstat_qrcodebyDeviceid($deviceId)
    {
        $url = "https://api.weixin.qq.com/device/get_stat?access_token=" . $this->access_token . "&device_id=" .$deviceId ;
        $res = $this->https_request($url);
        return json_decode($res, true);
    }

    //MSG_1.8 接入社交功能消息
    //class_wechat中transmitSocialMessage($object)函数功能体，暂时不清楚Myrank/Ranklist的生成方法

    //API_1.9 验证硬件设备二维码
    //POST方式
    public function verify_qrcode($ticket)
    {
        $data = '{"ticket":"' . $ticket . '"}';
        $url = "https://api.weixin.qq.com/device/verify_qrcode?access_token=" . $this->access_token;
        $res = $this->https_request($url, $data);
        return json_decode($res, true);
    }

    //MSG_1.10.2 WIFI设备消息接口：用户订阅/退订设备状态
    //to be update

    //API_1.10.3 第三方主动发送消息给硬件设备
    public function trans_msgtodeviceWIFI($deviceType, $deviceId, $openId, $msgType, $device_status)
    {
        $data = array("device_type" => $deviceType, "device_id" => $deviceId, "open_id" => $openId, "msg_type" => $msgType, "device_status" =>$device_status);
        $url = "https://api.weixin.qq.com/device/transmsg?access_token=" . $this->access_token;
        $res = $this->https_request($url, json_encode($data));
        return json_decode($res, true);
    }

    //API_11.1 创建DeviceId + QrCode合二为一的方式，在PHP环境下使用成功，而且在微信测试界面通过测试
    //GET方式
    public function create_DeviceidAndQrcode()
    {
        $url = "https://api.weixin.qq.com/device/getqrcode?access_token=" . $this->access_token;
        $res = $this->https_request($url);
        return json_decode($res, true);
    }

    //引入核心库文件，在界面上显示二维码图像
    public function create_qrcodeDisplay($url)
    {
        //帮助之地：
        //  http://www.jb51.net/article/48124.htm
        //  http://jingyan.baidu.com/article/4b52d70277fbd6fc5d774b61.html

        include "phpqrcode/phpqrcode.php";
        //定义纠错级别
        $errorLevel = "L";
        //定义生成图片宽度和高度;默认为3
        $size = "4";
        //定义生成内容
        $content="微信公众平台：思维与逻辑;公众号:siweiyuluoji";
        //调用QRcode类的静态方法png生成二维码图片//
        //QRcode::png($content, false, $errorLevel, $size);
        //生成网址类型
        /*
        $url="http://jingyan.baidu.com/article/48a42057bff0d2a925250464.html";
        $url.="\r\n";
        $url.="http://jingyan.baidu.com/article/acf728fd22fae8f8e510a3d6.html";
        $url.="\r\n";
        $url.="http://jingyan.baidu.com/article/92255446953d53851648f412.html";
        */
        QRcode::png($url, 'qrcode.png', $errorLevel, $size);
        echo '<img src="qrcode.png">';
        return true;
    }

    //API_1.12.1 绑定成功通知
    //POST方式
    public function notify_bindSuccessful($ticket, $deviceId, $openId)
    {
        $data = '{"ticket":"' . $ticket . '","device_id":"' . $deviceId . '","openid":"' . $openId . '"}';
        $url = "https://api.weixin.qq.com/device/bind?access_token=" . $this->access_token;
        $res = $this->https_request($url, $data);
        return json_decode($res, true);
    }

    //API_1.12.2 解绑成功通知
    //POST方式
    public function notify_unbindSuccessful($ticket, $deviceId, $openId)
    {
        $data = '{"ticket":"' . $ticket . '","device_id":"' . $deviceId . '","openid":"' . $openId . '"}';
        $url = "https://api.weixin.qq.com/device/unbind?access_token=" . $this->access_token;
        $res = $this->https_request($url, $data);
        return json_decode($res, true);
    }

    //API_1.12.3 强制绑定用户和设备 （意味着不是通过扫码完成的)
    //POST方式
    public function compel_bind($deviceId, $openId)
    {
        $data = '{"device_id":"' . $deviceId . '","openid":"' . $openId . '"}';
        $url = "https://api.weixin.qq.com/device/compel_bind?access_token=" . $this->access_token;
        $res = $this->https_request($url, $data);
        return json_decode($res, true);
    }

    //API_1.12.4 强制解绑用户和设备 （意味着不是通过微信界面操作完成)
    //POST方式
    public function compel_unbind($deviceId, $openId)
    {
        $data = '{"device_id":"' . $deviceId . '","openid":"' . $openId . '"}';
        $url = "https://api.weixin.qq.com/device/compel_unbind?access_token=" . $this->access_token;
        $res = $this->https_request($url, $data);
        return json_decode($res, true);
    }

    //API_1.13 获取硬件状态， 只要openid
    //GET方式
    //这个是在用户使用扫码关注后，才能干这件事，否则返回ErrorMsg
    public function getstat_qrcodebyOpenId($openId)
    {
        $url = "https://api.weixin.qq.com/device/get_bind_device?access_token=" . $this->access_token . "&openid=" .$openId ;
        $res = $this->https_request($url);
        return json_decode($res, true);
    }

    //API 用户自己点击菜单命令
    public function receive_deviceClickCommand($data)
    {
        //非常明确是CLICK命令
        switch($data->EventKey) {
            case "CLICK_ABOUT":
                $transMsg = $this->xms_responseText($data->FromUserName, $data->ToUserName, "TokenID = " . $this->access_token);
                break;
            case "CLICK_TECH":
                $transMsg = $this->xms_responseText($data->FromUserName, $data->ToUserName, "Appid = " . $this->appid);
                break;
            case "CLICK_TEST":
                $transMsg = $this->xms_responseText($data->FromUserName, $data->ToUserName, "Appsecret = " . $this->appsecret);
                break;
            case "CLICK_BIND":
                $transMsg = $this->xms_responseText($data->FromUserName, $data->ToUserName, "绑定操作：当前模式下无法完成，请通过后台生成DEVICE_ID和二维码，更新MAC授权后再扫码完成！");
                break;
            case "CLICK_BIND_INQ":
                //增加第三方后台云的绑定状态
                $wxDbObj = new class_mysql_db();
                $result = $wxDbObj->db_BleBoundInfo_query($data->FromUserName);
                if ($result == false) $transMsg = "第三方云数据库中绑定设备：无";
                else $transMsg = "第三方云数据库中有绑定设备：有";
                //再查微信云上的绑定状态
                $result = $this->getstat_qrcodebyOpenId($data->FromUserName);
                if (count($result["device_list"]) == 0)
                    $transMsg = $this->xms_responseText($data->FromUserName, $data->ToUserName, "微信云绑定查询操作结果：无 \n" . $transMsg);
                else
                    $transMsg = $this->xms_responseText($data->FromUserName, $data->ToUserName, "微信云绑定查询操作结果：有 " . $result["device_list"] . " \n" . $transMsg);
                break;
            case "CLICK_UNBIND";
                //先解绑微信云上的绑定状态
                //这里就考虑一个设备，如果存储多个设备的话，需要多次解绑
                $result = $this->getstat_qrcodebyOpenId($data->FromUserName);
                if (count($result["device_list"]) != 0) {
                    $result = $this->compel_unbind($result["device_list"][0]["device_id"], $data->FromUserName);
                    $transMsg = "微信云解绑操作一次一个设备" . $result . " \n";
                } else {
                    $transMsg = "微信云不存在绑定设备\n";
                }
                //再解绑第三方云上的绑定状态
                $wxDbObj = new class_mysql_db();
                $result = $wxDbObj->db_BleBoundInfo_query($data->FromUserName);
                if ($result == false) {
                    $transMsg = $this->xms_responseText($data->FromUserName, $data->ToUserName, $transMsg . "第三方云数据库中无绑定设备");
                } else {
                    $wxDbObj->db_BleBoundInfo_delete($data->FromUserName);
                    $transMsg = $this->xms_responseText($data->FromUserName, $data->ToUserName, $transMsg . "第三方云数据库一次解绑全部");
                }
                break;
            case "CLICK_LIGHT_INQ":
                $transMsg = $this->xms_responseText($data->FromUserName, $data->ToUserName,"暂时未实现的操作，等待设备功能完善后实现");
                break;
            case ("CLICK_LIGHT_ON" || "CLICK_LIGHT_OFF"):
                if ($data->EventKey == "CLICK_LIGHT_ON") $open = "点灯";
                else $open = "关灯";
                $wxDbObj = new class_mysql_db();
                $dbres = $wxDbObj->db_BleBoundInfo_query($data->FromUserName);
                if ($dbres == false) {
                    $transMsg = $this->xms_responseText($data->FromUserName, $data->ToUserName, "数据库中未绑定设备");
                } else {
                    //只考虑一个设备的情况，未来再考虑同一个用户绑定多个设备的情况
                    $dev = $dbres[0];
                    //对点灯的操作进行层三处理，构造可以发送给硬件设备的信息
                    $wxL3Obj = new class_L3_Process_Func();
                    $dev1 = $wxL3Obj->L3_deviceMsgProcess($data->EventKey, $dev, $data->FromUserName, $data->DeviceID); //CLICK_LIGHT_ON or CLICK_LIGHT_OFF
                    //BYTE系列化处理在L3消息处理过程中完成
                    $dev2 = base64_encode($dev1);
                    //推送数据到硬件设备
                    $result = $this->trans_msgtodevice($dev["deviceType"], $dev["deviceID"], $dev["openID"], $dev2);
                    //推送回复消息给微信界面
                    $transMsg = $this->xms_responseText($data->FromUserName, $data->ToUserName,
                        "已发送" . $open . "操作, DeviceID= " . $dev["deviceID"] . ", 设备消息= " . $dev1);
                }
                break;
            default:
                $transMsg = $this->xms_responseText($data->FromUserName, $data->ToUserName,"收到未识别菜单EventKey值");
                break;
        }
        return $transMsg;
    }

    //随机字符串
    public function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

    public function generateDeviceId()
    {
        $tmpArr = array(WX_APPID, $this->generateRandomString(10));
        return implode($tmpArr);
    }

}// End of class_wx_IOT_sdk




//Layer 3 aperation
//建立数据库的持久层功能
class class_mysql_db
{
    //BleBoundInfo
    //存储绑定数据
    public function db_BleBoundInfo_save($fromUserName, $deviceID, $openID, $deviceType)
    {
        //建立连接
        $mysqli=new mysqli(WX_DBHOST, WX_DBUSER, WX_DBPSW, WX_DBNAME, WX_DBPORT);
        if (!$mysqli)
        {
            die('Could not connect: ' . mysqli_error($mysqli));
        }
        //找到数据库中已有序号最大的，也许会出现序号(6 BYTE)用满的情况，这时应该考虑更新该算法，短期内不需要考虑这么复杂的情况
        $result = $mysqli->query("SELECT `sid` FROM `bleboundinfo` WHERE 1");
        $sid =1;
        while($row = $result->fetch_array())
        {
            if ($row['sid'] > $sid)
            {
                $sid = $row['sid'];
            }
        }
        $sid = $sid+1;
        //存储新记录
        $result=$mysqli->query("INSERT INTO `bleboundinfo` (sid, fromUserName, deviceID, openID, deviceType)
          VALUES ('$sid', '$fromUserName', '$deviceID','$openID','$deviceType')");
        $mysqli->close();
        return $result;
    }

    //查询绑定数据
    public function db_BleBoundInfo_query($fromUserName)
    {
        //建立连接
        $mysqli=new mysqli(WX_DBHOST, WX_DBUSER, WX_DBPSW, WX_DBNAME, WX_DBPORT);
        if (!$mysqli)
        {
            die('Could not connect: ' . mysqli_error($mysqli));
        }
        //找到数据库中已有序号最大的，也许会出现序号(6 BYTE)用满的情况，这时应该考虑更新该算法，短期内不需要考虑这么复杂的情况
        $result = $mysqli->query("SELECT * FROM `bleboundinfo` WHERE fromUserName = '$fromUserName'");
        $i=0;
        while($row = $result->fetch_array())
        {
            $res[$i]["sid"] = $row['sid'];
            $res[$i]["fromUserName"] = $row['fromUserName'];
            $res[$i]["deviceID"] = $row['deviceID'];
            $res[$i]["openID"] = $row['openID'];
            $res[$i]['deviceType'] = $row['deviceType'];
            $i++;
        }
        if ($i == 0) $res = false;
        $result->close();
        $mysqli->close();
        return $res;
    }

    //查询绑定数据是否已经有了相同的记录，否则就不应该重新绑定并增加一条记录
    //测试的过程中还有些问题，需要再行测试！！！
    public function db_BleBoundInfo_duplicate($fromUserName, $deviceID, $openID, $deviceType)
    {
        //建立连接
        $mysqli=new mysqli(WX_DBHOST, WX_DBUSER, WX_DBPSW, WX_DBNAME, WX_DBPORT);
        if (!$mysqli)
        {
            die('Could not connect: ' . mysqli_error($mysqli));
        }
        $result = $mysqli->query("SELECT `sid` FROM `bleboundinfo` WHERE ((`fromUserName` = '$fromUserName' AND `deviceID` =
          '$deviceID') AND (`openID` = '$openID' AND `deviceType` = '$deviceType'))");
        if (($result->num_rows)>0) $result = true;
        else $result = false;
        $mysqli->close();
        return $result;
    }

    //删除绑定数据
    public function db_BleBoundInfo_delete($fromUserName)
    {
        //建立连接
        $mysqli=new mysqli(WX_DBHOST, WX_DBUSER, WX_DBPSW, WX_DBNAME, WX_DBPORT);
        if (!$mysqli)
        {
            die('Could not connect: ' . mysqli_error($mysqli));
        }
        //删除表单
        $result = $mysqli->query("DELETE FROM `bleboundinfo` WHERE `fromUserName` = '$fromUserName'");
        $mysqli->close();
        return $result;
    }

    //存储更新Token信息
    public function db_AccessTokenInfo_save($appid, $appsecret, $lasttime, $access_token)
    {
        //建立连接
        $mysqli=new mysqli(WX_DBHOST, WX_DBUSER, WX_DBPSW, WX_DBNAME, WX_DBPORT);
        if (!$mysqli)
        {
            die('Could not connect: ' . mysqli_error($mysqli));
        }
        //先检查是否存在，如果存在，就更新，否则创建
        $result = $mysqli->query("SELECT * FROM `accesstokeninfo` WHERE `appid` = '$appid' AND `appsecret` = '$appsecret'");
        if (($result->num_rows)>0)  {
            $res1=$mysqli->query("UPDATE `accesstokeninfo` SET `lasttime` = '$lasttime'
              WHERE `appid` = '$appid' AND `appsecret` = '$appsecret'");
            $res2=$mysqli->query("UPDATE `accesstokeninfo` SET `access_token` = '$access_token'
              WHERE `appid` = '$appid' AND `appsecret` = '$appsecret'");
            $result = $res1 OR $res2;
        }else{
            $result=$mysqli->query("INSERT INTO `accesstokeninfo` (appid, appsecret, lasttime, access_token)
              VALUES ('$appid', '$appsecret', '$lasttime','$access_token')");
        }
        $mysqli->close();
        return $result;
    }

    //判断是否有已经存在的Token
    public function db_AccessTokenInfo_inqury($appid, $appsecret)
    {
        //建立连接
        $mysqli=new mysqli(WX_DBHOST, WX_DBUSER, WX_DBPSW, WX_DBNAME, WX_DBPORT);
        if (!$mysqli)
        {
            die('Could not connect: ' . mysqli_error($mysqli));
        }
        //先检查是否存在，如果存在，就更新，否则创建
        $result = $mysqli->query("SELECT * FROM `accesstokeninfo` WHERE `appid` = '$appid' AND `appsecret` = '$appsecret'");
        if (($result->num_rows)>0)  {
            $result = $result->fetch_array();
        }else{
            $result = "NOTEXIST";
        }
        $mysqli->close();
        return $result;
    }

    //寻找一个空的DEVICE_ID
    public function db_DeviceQrcode_inqury()
    {
        //建立连接
        $mysqli=new mysqli(WX_DBHOST, WX_DBUSER, WX_DBPSW, WX_DBNAME, WX_DBPORT);
        if (!$mysqli)
        {
            die('Could not connect: ' . mysqli_error($mysqli));
        }
        //先检查是否存在，如果存在，就更新，否则创建
        $result = $mysqli->query("SELECT * FROM `deviceqrcode` WHERE `mac` = ' '");
        //只返回一个
        if (($result->num_rows)>0) {
            $row = $result->fetch_array();
            $res["deviceid"] = $row['deviceid'];
            $res["qrcode"] = $row['qrcode'];
            $res['devicetype'] = $row['devicetype'];
        }else{
            $res = null;
        }
        $mysqli->close();
        return $res;
    }

    //回写MAC属性
    public function db_DeviceQrcode_update_mac($deviceid, $mac)
    {
        //建立连接
        $mysqli=new mysqli(WX_DBHOST, WX_DBUSER, WX_DBPSW, WX_DBNAME, WX_DBPORT);
        if (!$mysqli)
        {
            die('Could not connect: ' . mysqli_error($mysqli));
        }
        //先检查是否存在，如果存在，就更新，否则创建
        $result=$mysqli->query("UPDATE `deviceqrcode` SET `mac` = '$mac' WHERE `deviceid` = '$deviceid'");
        $mysqli->close();
        return $result;
    }

    //存储EMC数据，每一次存储，都是新增一条记录
    //时间的网格化是以3分钟为单位的，如果后期需要调整该定时，需要更新该函数
    public function db_EmcDataInfo_save($user, $deviceid, $timestamp, $value, $gps)
    {
        //建立连接
        $mysqli=new mysqli(WX_DBHOST, WX_DBUSER, WX_DBPSW, WX_DBNAME, WX_DBPORT);
        if (!$mysqli)
        {
            die('Could not connect: ' . mysqli_error($mysqli));
        }
        //找到数据库中已有序号最大的，也许会出现序号(6 BYTE)用满的情况，这时应该考虑更新该算法，短期内不需要考虑这么复杂的情况
        $result = $mysqli->query("SELECT `sid` FROM `emcdatainfo` WHERE 1");
        $sid =0;
        while($row = $result->fetch_array())
        {
            if ($row['sid'] > $sid)
            {
                $sid = $row['sid'];
            }
        }
        $sid = $sid+1;
        //存储新记录，如果发现是已经存在的数据，则覆盖，否则新增
        $date1 = intval(date("ymd", $timestamp));
        $tmp = getdate($timestamp);
        $hourminindex = intval(($tmp["hours"] * 60 + $tmp["minutes"])/3);  //固定三分钟为一个周期
        $result = $mysqli->query("SELECT `sid` FROM `emcdatainfo` WHERE ((`wxuser` = '$user' AND `deviceid` =
          '$deviceid') AND (`date` = '$date1' AND `hourminindex` = '$hourminindex'))");
        if (($result->num_rows)>0)   //重复，则覆盖
        {
            $res1=$mysqli->query("UPDATE `emcdatainfo` SET `emcvalue` = '$value'
              WHERE ((`wxuser` = '$user' AND `deviceid` = '$deviceid') AND (`date` = '$date1' AND `hourminindex` = '$hourminindex'))");
            $res2=$mysqli->query("UPDATE `emcdatainfo` SET `gps` = '$gps'
              WHERE ((`wxuser` = '$user' AND `deviceid` = '$deviceid') AND (`date` = '$date1' AND `hourminindex` = '$hourminindex'))");
            $result = $res1 OR $res2;
        }
        else   //不存在，新增
        {
            $result=$mysqli->query("INSERT INTO `emcdatainfo` (sid, wxuser, deviceid, date, hourminindex, emcvalue, gps)
          VALUES ('$sid', '$user', '$deviceid', '$date1', '$hourminindex','$value', '$gps')");
        }
        $mysqli->close();
        return $result;
    }

    //删除对应用户所有超过90天的数据
    //缺省做成90天，如果参数错误，导致90天以内的数据强行删除，则不被认可
    public function db_EmcDataInfo_delete_3monold($user, $deviceid, $days)
    {
        if ($days <90) $days = 90;  //不允许删除90天以内的数据
        //建立连接
        $mysqli=new mysqli(WX_DBHOST, WX_DBUSER, WX_DBPSW, WX_DBNAME, WX_DBPORT);
        if (!$mysqli)
        {
            die('Could not connect: ' . mysqli_error($mysqli));
        }
        //删除距离当前超过90天的数据，数据的第90天稍微有点截断，但问题不大
        //比较蠢的细节方法
        /*$result = $mysqli->query("SELECT `sid` FROM `emcdatainfo` WHERE `date` < (now()-$days)");
        while($row = $result->fetch_array())
        {
            $sidtmp = $row['sid'];
            $res = $mysqli->query("DELETE FROM `emcdatainfo` WHERE `sid` = '$sidtmp'");
        }*/
        //尝试使用一次性删除技巧，结果非常好!!!
        $result = $mysqli->query("DELETE FROM `emcdatainfo` WHERE ((`wxuser` = '$user' AND `deviceid` =
          '$deviceid') AND (TO_DAYS(NOW()) - TO_DAYS(`date`) > '$days'))");
        $mysqli->close();
        return $result;
    }

    //新增或者更新累计辐射剂量数据，每个用户一条记录，不得重复
    public function db_EmcAccumulationInfo_save($user, $deviceid)
    {
        //建立连接
        $mysqli = new mysqli(WX_DBHOST, WX_DBUSER, WX_DBPSW, WX_DBNAME, WX_DBPORT);
        if (!$mysqli) {
            die('Could not connect: ' . mysqli_error($mysqli));
        }
        $result = $mysqli->query("SELECT * FROM `emcaccumulationinfo` WHERE (`wxuser` = '$user' AND `deviceid` =
          '$deviceid')");
        $tag = 0;
        if (($result->num_rows)>0)   //更新数据而已，而且假设每个用户只有唯一的一条记录
        {
            $row = $result->fetch_array();
            $lastupdatedate = date("ymd", strtotime($row['lastupdatedate']));  //字符串
            $lastUpdateStart = date("ymd", strtotime($row['lastupdatedate'])-2*24*60*60);  //解决模2的边界问题
            if ($lastupdatedate != date("ymd")) {
                $tag = 1;
                $sid = $row['sid'];
                $lastUpdateStart = intval($lastUpdateStart);
            }
        }else  //如果是第一次创建
        {
            //先找到最大的SID系列号
            $res1 = $mysqli->query("SELECT `sid` FROM `emcaccumulationinfo` WHERE 1");
            $sid = 0;
            while ($row = $res1->fetch_array()) {
                if ($row['sid'] > $sid) {
                    $sid = $row['sid'];
                }
            }
            $sid = $sid + 1;
            //初始化各种数值
            $lastupdatedate = intval(date("ymd"));
            $avg30days = "0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0";  //使用;做数据之间的分割
            $avg3month = "0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0";
            $result = $mysqli->query("INSERT INTO `emcaccumulationinfo` (sid, wxuser, deviceid, lastupdatedate, avg30days, avg3month)
          VALUES ('$sid', '$user', '$deviceid', '$lastupdatedate', '$avg30days', '$avg3month')");
            $tag = 2;
        }
        if ($tag ==1 || $tag ==2)  //不同天的更新，或者新创时的全面计算
        {
            //从数据库中取出，进行处理
            $result = $mysqli->query("SELECT * FROM `emcaccumulationinfo` WHERE (`sid` = '$sid')");
            $row = $result->fetch_array();  //原则上只有唯一的一个记录
            $avg30days = $row['avg30days'];
            $avg3month = $row['avg3month'];
            $avgd1 = explode(";", $avg30days);
            $avgm1 = explode(";", $avg3month);
            for ($i=0;$i<32;$i++)
            {
                $avgd2 [$i] = intval($avgd1[$i]);
                $avgm2 [$i] = intval($avgm1[$i]);
                $daynum [$i] = 0;
                $monthnum [$i] = 0;
            }
            //全面做一次计算处理，先做当月的处理
            $day0 = intval(date("ymd"));
            if ($tag == 1) $day90 = $lastUpdateStart;  //两天的边界问题需要考虑在内
            if ($tag == 2) $day90 = intval(date("ymd", time()-90*24*60*60));

            $result = $mysqli->query("SELECT * FROM `emcdatainfo` WHERE (`wxuser` = '$user' AND `deviceid` = '$deviceid')");
            while($row = $result->fetch_array())
            {
                $getdate0 = date("ymd", strtotime($row['date']));
                $getdate = intval($getdate0);
                if (($getdate <= $day0) &&  ($getdate >= $day90))
                {
                    $tm = intval(substr($getdate0,2,2));
                    $td = intval(substr($getdate0,4,2));
                    $index1 = $tm*31 + $td;
                    $index = intval(($index1 - intval($index1/90)*90)/3);
                    $value = intval($row['emcvalue']);
                    //日加总
                    if ($daynum[$td] == 0){
                        $avgd2[$td] = $value;
                    }else{
                        $avgd2[$td] = $avgd2[$td] + $value;
                    }
                    $daynum[$td] = $daynum[$td] + 1;
                    //季加总平均
                    if ($monthnum[$index] == 0){
                        $avgm2[$index] = $value;
                    }else{
                        $avgm2[$index] = $avgm2[$index] + $value;
                    }
                    $monthnum[$index] = $monthnum[$index] + 1;
                }
            }
            for ($i=0;$i<32;$i++)
            {
                if ($daynum[$i] != 0)
                $avgd2 [$i] = intval($avgd2[$i] / $daynum[$i]);
                if ($monthnum[$i] != 0)
                    $avgm2 [$i] = intval($avgm2[$i] / $monthnum[$i]);
            }
            $avg30days = implode(";", $avgd2);
            $avg3month = implode(";", $avgm2);
            //再重新存入数据文件
            $res1=$mysqli->query("UPDATE `emcaccumulationinfo` SET `avg30days` = '$avg30days' WHERE (`sid` = '$sid')");
            $res2=$mysqli->query("UPDATE `emcaccumulationinfo` SET `avg3month` = '$avg3month' WHERE (`sid` = '$sid')");
            $res3=$mysqli->query("UPDATE `emcaccumulationinfo` SET `lastupdatedate` = '$day0' WHERE (`sid` = '$sid')");

            $result = $res1 OR $res2 OR $res3;
        }
        $mysqli->close();
        return $result;
    }


    //新增或者更新累计辐射剂量数据，每个用户一条记录，不得重复
    //返回双结构数据：数组的第一个包含了31天的平均值，30个采样点，第二个包含了90天的平均值（每三天平均一次），30个点的采样数据
    //数组是32个元素，DAY数据在1-31中，90天的均值数在0-29中
    //这样设计只是为了处理的方便，上层使用时自行处理边界问题
    public function db_EmcAccumulationInfo_inqury($user, $deviceid)
    {
        //建立连接
        $mysqli = new mysqli(WX_DBHOST, WX_DBUSER, WX_DBPSW, WX_DBNAME, WX_DBPORT);
        if (!$mysqli) {
            die('Could not connect: ' . mysqli_error($mysqli));
        }
        $result = $mysqli->query("SELECT * FROM `emcaccumulationinfo` WHERE (`wxuser` = '$user' AND `deviceid` = '$deviceid')");
        $row = $result->fetch_array();
        $avgd = $row['avg30days'];
        $avgm = $row['avg3month'];
        $avgd1 = explode(";", $avgd);
        $avgm1 = explode(";", $avgm);
        for ($i=0;$i<32;$i++)
        {
            $avgd2 [$i] = intval($avgd1[$i]);
            $avgm2 [$i] = intval($avgm1[$i]);
        }
        $result = array ("avg30days" => $avgd2,  "avg3month" => $avgm2);
        $mysqli->close();
        return $result;
    }


} //End of class_mysql_db

//Layer 3 aperation
class class_L3_Process_Func
{
    /* 硬件L3消息内容处理，以及涉及到菜单操作中必要的L3相关的交互消息处理
     * 返回：发送L3消息给智能硬件
     * 是否还需要设置耕作的输入参数，TBD
     * UTF-8 coding
     * HEAD {
     *      ushort(2B) magic = OxFECF;
     *      ushort(2B) version;
     *      ushort(2B) length;
     *      ushort(2B) cmdid;
     *      ushort(2B) seq;
     *      ushort(2B) errorcode;
     *      }
     * BODY {   //以电磁波辐射测量值为标准
     *      ushort(2B) emc_value;
     *      ushort(2B) emc_time;
     *      }
     *
     */

    //构造函数，初始化消息结构
    public function __construct()
    {
        //生成该结构，没用
        /*
        $head_magic = pack("n", 0xFECF);
        $head_version = pack("S", 0);
        $head_length = pack("S", 0);
        $head_cmdid = pack("S", 0);
        $head_seq = pack("S", 0);
        $head_errorcode = pack("S", 0);
        $this->L3msghead = array("head_magic", "head_version", "head_length", "head_cmdid", "head_seq", "head_errorcode");
        */
    }

    //解析成头和体之后消息处理
    //输入: $content = 64BaseDecode result，系列化之前的十六进制原始消息流
    //返回：$result = full response content / message，系列化之后的十六进制信息流
    public function L3_deviceMsgProcess ($optType, $content, $fromuser, $deviceid)
    {
        $respContent = "";
        switch ($optType)
        {
            case "device_text":
                $respContent = $this->L3_device_text_process($content, $fromuser, $deviceid);
                break;
            case "bind":
                $respContent = $this->L3_bind_process($content);
                break;
            case "unbind":
                $respContent = $this->L3_unbind_process($content);
                break;
            case "CLICK_LIGHT_ON":
                $respContent = $this->L3_light_on_process($content);
                break;
            case "CLICK_LIGHT_OFF":
                $respContent = $this->L3_light_off_process($content);
                break;
            default:
                $respContent = "";
                break;
        }
        return $respContent;
    }

    public function L3_bind_process($content)
    {
        //目前假设是空包，所以不处理
        return "";
    }

    public function L3_unbind_process($content)
    {
        //目前假设是空包，所以不处理
        return "";
    }

    //DEVICE_TEXT processing
    public function L3_device_text_process($content, $fromuser, $deviceid)
    {
        //反系列化处理
        $rev = $this->L3_msgParse($content);
        //进入不同数据内容处理阶段
        $cmdid = "";
        $resp = $rev["body"];
        switch ($rev["head"]["head_cmdid"])
        {
            case "CMDID_EMC_DATA_REQ":
                //取得数据结构体
                $emc_value = hexdec(substr($rev["body"], 0, 4))  & 0xFFFF;
                $emc_time = hexdec(substr($rev["body"], 0, 4))  & 0xFFFF;
                //存入数据库中
                $wxDbObj = new class_mysql_db();
                $wxDbObj->db_EmcDataInfo_save($fromuser, $deviceid, $emc_time, $emc_value, 0); //GPS not yet exist today, could be add in future.
                $wxDbObj->db_EmcDataInfo_delete_3monold($fromuser, $deviceid, 90);  //remove 90 days old data.
                $wxDbObj->db_EmcAccumulationInfo_save($fromuser, $deviceid); //累计值计算，如果不是初次接收数据，而且日期没有改变，则该过程将非常快
                $cmdid = CMDID_EMC_DATA_RESP;
                $resp = "";
                break;
            case "CMDID_EMC_DATA_REV":
                //不需要再回复消息，再考虑设计下这个工作流程设计
                $cmdid = CMDID_SEND_TEXT_RESP;
                break;
            case "CMDID_OCH_DATA_REQ":
                $cmdid = CMDID_OCH_DATA_RESP;
                break;
            default:
                $cmdid = CMDID_SEND_TEXT_RESP;
                break;
        }
        //再进入真正的处理阶段, 系列化
        $result = $this->L3_msgBuild($cmdid, $resp, $rev["head"]["head_seq"]);
        return $result;
    }

    public function L3_light_on_process($content)
    {
        $result = $this->L3_msgBuild(CMDID_OPEN_LIGHT_PUSH, null, 0);
        return $result;
    }

    public function L3_light_off_process($content)
    {
        $result = $this->L3_msgBuild(CMDID_CLOSE_LIGHT_PUSH, null, 0);
        return $result;
    }

    //L3消息解析，完成BYTE到消息结构体的解析
    public function L3_msgParse($input)
    {
        $buf["head_magic"] = hexdec(substr($input, 0, 4)) & 0xFFFF;
        if ($buf["head_magic"] != L3_MAGIC_BL) return false;
        $buf["head_version"] = hexdec(substr($input, 4, 4))  & 0xFFFF;
        $buf["head_length"] = hexdec(substr($input, 8, 4))  & 0xFFFF;
        $buf["head_cmdid"] = hexdec(substr($input, 12, 4))  & 0xFFFF;
        $buf["head_seq"] = hexdec(substr($input, 16, 4))  & 0xFFFF;
        $buf["head_errorcode"] = hexdec(substr($input, 20, 4))  & 0xFFFF;
        $content = substr($input, 24);
        $result = array("head" => $buf, "body" => $content);
        return $result;
    }

    //L3消息格式构造，而且直接完成消息体到二进制流的转化
    public function L3_msgBuild($cmdid, $resp, $seq)
    {
        $magic = $this->ushort2string(L3_MAGIC_BL);
        $version = $this->ushort2string(1);
        $length = $this->ushort2string(L3_HEAD_LENGTH + strlen($resp));
        $cmdid = $this->ushort2string($cmdid);
        $seq = $this->ushort2string($seq);
        $errorcode = $this->ushort2string(0);
        $output = $magic . $version . $length . $cmdid . $seq . $errorcode . $resp;;
        return $output;
    }

    //BYTE转换到字符串
    public function byte2string($n)
    {
        $out = "00";
        $a1 = strtoupper(dechex($n & 0xFF));
        return substr_replace($out, $a1, strlen($out)-strlen($a1), strlen($a1));
    }

    //2*BYTE转换到字符串
    public function ushort2string($n)
    {
        $out = "0000";
        $a1 = strtoupper(dechex($n & 0xFFFF));
        return substr_replace($out, $a1, strlen($out)-strlen($a1), strlen($a1));
    }

    //4*BYTE转换到字符串
    public function int2string($n)
    {
        $out = "00000000";
        $a1 = strtoupper(dechex($n & 0xFFFFFFFF));
        return substr_replace($out, $a1, strlen($out)-strlen($a1), strlen($a1));
    }

} //End of class_L3_Process_Func

?>
