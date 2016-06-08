<?php
define("TOKEN", "weixin");
$wechatObj = new wechatCallbackapiTest();
//if (isset($_GET['echostr'])) {
if (0){
	$wechatObj->valid();
}else{
	$wechatObj->responseMsg();
}
class wechatCallbackapiTest
{
    public function valid()
    {
        $echoStr = $_GET["echostr"];

        //valid signature , option
        if($this->checkSignature()){
	    header('content-type:text');
            echo $echoStr;
            exit;
        }
    }
    
    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];    
                
        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        
        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }
    // 接受用户输入信息
    public function responseMsg()
    {
        //get post data, May be due to the different environments
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        //extract post data
        if (!empty($postStr)){
                
                $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
                $RX_TYPE = trim($postObj->MsgType);
                switch($RX_TYPE)
                {
                    case "text":
                        $resultStr = $this->handleUserMessage($postObj);
                        break;
                    case "event":
                        $resultStr = $this->handleEvent($postObj);
                        break;
                    default:
                        $resultStr = "Unknow msg type: ".$RX_TYPE;
                        break;
                }
                echo $resultStr;
        }else {
            echo "";
            exit;
        }
    }
	
    
    // 处理用户输入消息，判断用户需求类型
    public function handleUserMessage($postObj)
    {
        $fromUsername = $postObj->FromUserName;
        $toUsername = $postObj->ToUserName;
        $keyword = trim($postObj->Content);
        $time = time();
        
        
        if($keyword == '0'){
             
             $user_state="";
             $this->delete($fromUsername);
             $contentStr = "感谢您关注【我为球狂】"."\n"."回复1，笑一笑十年少"."\n"."回复2，新闻"."\n"."回复3，音乐"."\n"."回复4 智能聊天"."\n"."回复5 天气预报"."\n"."回复6 翻译"."\n"."回复0 退出当前功能"."\n"."AI 官方提供，如有雷同纯属抄袭!";
             $this->sendText($postObj,$contentStr);
             break;
        }
        
        
        
        //判断用户状态
        $user_state = $this->query($fromUsername);
        
        //判断用户是否要改变状态
        if(trim($keyword) <> $user_state && is_numeric($keyword)){
            $user_state="";
            $this->delete($fromUsername);
        }
        
        if(empty($user_state)){
            
            switch($keyword)
            {
                case 1: 
                
                $this->insert($fromUsername,1);
                $contentStr = "回复笑话 ，有惊喜\n 回复0退出";
                $this->sendText($postObj,$contentStr);
                
                break;
                
                case 2://图文回复
                // 这个方法可以封装
                $this->insert($fromUsername,2);
                
                $contentStr = "查看身边新鲜事，回复如：体育新闻，世界杯新闻，娱乐新闻..  \n 回复0退出";
                $this->sendText($postObj,$contentStr);
                
                break;
                
                case 3://音乐
                $this->insert($fromUsername,3);
                
                    $contentStr = "音乐,回复泪桥,伍佰为你歌唱！  \n 回复0退出";
                	$this->sendText($postObj,$contentStr);
                
                break;
                
                case 4://智能聊天
                $this->insert($fromUsername,4);
                $contentStr = "让我们聊起来吧！ ;)  \n 回复0退出";
                $this->sendText($postObj,$contentStr);
                
                    
                break;
                
                case 5://天气预报
                
                $this->insert($fromUsername,5);//保存用户查询天气状态
                
                $contentStr = "请输入要查询天气的城市：如北京、上海、苏州";
                $this->sendText($postObj,$contentStr);
                break;
                
                case 6://翻译功能
                
                $this->insert($fromUsername,6);
                
                $contentStr = "请输入要翻译的内容：如：早上好、good morning、おはよう";
                
                $this->sendText($postObj,$contentStr);
                
                break;
                
                case 7://小说
                
                $this->insert($fromUsername,7);
                
                $contentStr = "请输入我想看小说，\n回复0退出";
                
                $this->sendText($postObj,$contentStr);
                
                
                
                break;
                
                
                
                
                default: //输入其他内给出帮助菜单
                
				$contentStr = "感谢您关注【我为球狂】"."\n"."回复1，笑一笑十年少"."\n"."回复2，新闻"."\n"."回复3，音乐"."\n"."回复4 智能聊天"."\n"."回复5 天气预报"."\n"."回复6 翻译"."\n"."回复0 退出当前功能"."\n"."AI 官方提供，如有雷同纯属抄袭!";
         		$this->sendText($postObj,$contentStr);
               
               
                break;
                
                
            
            }
        }else{
            //状态不为空的时候
            switch ($user_state){
                
                case 1://简单消息
                
                
                if($keyword == '笑话'){
                	$json=file_get_contents("http://www.tuling123.com/openapi/api?key=4432609d4426ca8250dae8491a41d3c1&info=".urlencode($keyword)."");
                	$data = json_decode($json);
                    if($data->code == 100000){
                    //成功
                    $this->sendText($postObj,$data->text);
                    }
                    
                }else{
                  $contentStr = "回复笑话 ，有惊喜\n 回复0退出";
                	$this->sendText($postObj,$contentStr);
                }
                
                break;
                
                case 2://图文(世界杯新闻)
                
                $str = mb_substr($keyword,-2,2,"UTF-8");
                
                if($str != '新闻'){
                	$contentStr = "查看身边新鲜事，回复如：体育新闻，世界杯新闻，娱乐新闻..  \n 回复0退出";
                	$this->sendText($postObj,$contentStr);
                	break;
                }
                
                 $key = urlencode($keyword);
                
                 $json=file_get_contents("http://www.tuling123.com/openapi/api?key=4432609d4426ca8250dae8491a41d3c1&info=".$key);
                 $data =  json_decode($json,true);
                if($data[code] != 302000){
                 $contentStr = "哥，".$keyword."新闻没查到( ⊙ o ⊙ )！回复如：体育新闻，世界杯新闻，娱乐新闻..  \n 回复0退出";
                 $this->sendText($postObj,$contentStr);
                 break;
                }
                 $data = $data['list'];
                 $size = count($data);
                        
                 if($size > 10){
                    $size = 10;
                 }
                        
                 $item_ary = array();
                        
                 for($i=0 ; $i<$size; $i++){
                     $temp_ary = array(
                       "picUrl"=>$data[$i][icon],
                        "url" => $data[$i][detailurl],
                        "title"=> $data[$i][article]
                        );
                            
                      $item_ary[$i] = $temp_ary;
                 }
                    
                 $this->sendImgTextForNews($postObj,$item_ary);
                
                
                break;
                
                case 3://音乐
                 $musicTpl = "<xml>
                                 <ToUserName><![CDATA[%s]]></ToUserName>
                                 <FromUserName><![CDATA[%s]]></FromUserName>
                                 <CreateTime>%s</CreateTime>
                                 <MsgType><![CDATA[music]]></MsgType>
                                     <Music>
                                     <Title><![CDATA[%s]]></Title>
                                     <Description><![CDATA[%s]]></Description>
                                     <MusicUrl><![CDATA[%s]]></MusicUrl>
                                     <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
                                     </Music>
                                 </xml>";           
                    
                    $contentStr = "音乐";
                    $title = "泪桥";
                    $description = "就像站在烈日骄阳大桥上,眼泪狂奔滴落在我的脸膀";
                    $musicUrl="http://1.woweiqiukuang.sinaapp.com/%E6%B3%AA%E6%A1%A5.mp3";
                    $HDmusicUrl="http://1.woweiqiukuang.sinaapp.com/%E6%B3%AA%E6%A1%A5.mp3";
                    $resultStr = sprintf($musicTpl,$fromUsername,$toUsername,$time,$title,$description,$musicUrl,$HDmusicUrl);
                    echo $resultStr;
                break;
                
                case 4://智能聊天
                
                //$this->sendText($postObj,"http://www.tuling123.com/openapi/api?key=4432609d4426ca8250dae8491a41d3c1&info=".$keyword);
                //http://www.tuling123.com/openapi/api?key=4432609d4426ca8250dae8491a41d3c1&info=%E4%BD%A0
                
                
                
                $json=file_get_contents("http://www.tuling123.com/openapi/api?key=4432609d4426ca8250dae8491a41d3c1&info=".urlencode($keyword));
                $data =  json_decode($json);
                
                
                
                if($data->code == 100000){
                //成功
                    
                $this->sendText($postObj,$data->text);
                
                }else{
                  //失败
                  $this->sendText($postObj,"baby,好好唠嗑，放心保证不打死你！");
                
                }
                
                break;
                
                case 5://天气预报
                $data = $this->weather($keyword);//获得数据
                
                
                if($data[status] == 'success'){
                    
                    
                    //添加数据
                    $dayInfoAry = $data[results][0][weather_data];
                    $item_ary = array();
                    for($i=0 ; $i<count($dayInfoAry); $i++){
                        $temp_ary = array(
                         "picUrl"=>$dayInfoAry[$i][dayPictureUrl],
                        "title" => $dayInfoAry[$i][date].$dayInfoAry[$i][weather].$dayInfoAry[$i][wind].$dayInfoAry[$i][temperature],
                         "description"=> $dayInfoAry[$i][weather]
                         );
                        $item_ary[$i] = $temp_ary;
                    }
                    
                    //发送图文
                    $this->sendImgText($postObj,$item_ary);
                 
                
                }else{
                    $contentStr = "未查询到'".$keyword."'的天气信息，请确认您输入的信息是城市如： 北京，上海！";
                    $this->sendText($postObj,$contentStr);
                }
                
                break;
                case 6:// 翻译
                
                	
                	$data = $this->youdaoDic($keyword);
					$this->sendText($postObj,$data);
                
                break;
                
                case 7://小说
                
                $json=file_get_contents("http://www.tuling123.com/openapi/api?key=4432609d4426ca8250dae8491a41d3c1&info=我想看小说");
                $data =  json_decode($json,true);
                
                
                $data = $data['list'];
                 $size = count($data);
                        
                 if($size > 10){
                    $size = 10;
                 }
                        
                 $item_ary = array();
                        
                 for($i=0 ; $i<$size; $i++){
                     $temp_ary = array(
                       "picUrl"=>$data[$i][icon],
                        "url" => $data[$i][detailurl],
                         "title"=> $data[$i][name].",作者:".$data[$i][author]
                        );
                            
                      $item_ary[$i] = $temp_ary;
                 }
                    
                 $this->sendImgTextForNews($postObj,$item_ary);
                
                
                break;
            
            
            
            }
        
        }
    }
    
    
    //处理关注事件
    public function handleEvent($object)
    {
        $contentStr = "";
        switch ($object->Event)
        {
            case "subscribe":
                $contentStr = "感谢您关注【我为球狂】"."\n"."回复1，笑一笑十年少"."\n"."回复2，新闻"."\n"."回复3，音乐"."\n"."回复4 智能聊天"."\n"."回复5 天气预报"."\n"."回复6 翻译"."\n"."AI 官方提供，如有雷同纯属抄袭!";
                break;
	    case "CLICK":
		switch ($object->EventKey)
		{
		    case "V1001_TODAY_NEWS":
		        $contentStr = "This is atmel winc1500 cortus sdk demo for weixin";
			break;
		    case "V1001_GOOD":
			$contentStr = "Thank you for your support";
			break;
		    default:
			$contentStr = "ohohohoh.....";
			break;
		}
		break;
	    case "bind":
		break;
            default :
                $contentStr = "Unknow Event: ".$object->Event;
                break;
        }
        $resultStr = $this->responseText($object, $contentStr);
        return $resultStr;
    }
    
    public function responseText($object, $content, $flag=0)
    {
        $textTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[text]]></MsgType>
                    <Content><![CDATA[%s]]></Content>
                    <FuncFlag>%d</FuncFlag>
                    </xml>";
        $resultStr = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time(), $content, $flag);
        return $resultStr;
    }
    
    
   /*
    *获得天气json数据
    *
    */
    public function weather($city){
        if(!empty($city)){
        $json=file_get_contents("http://api.map.baidu.com/telematics/v3/weather?location=".urlencode($city)."&output=json&ak=l2MbyQiOoD7gtz0K4pioszcp");
        return json_decode($json,true);
        
        } else {
            return null;
        }
	}
    
    
   /*
    *  有道翻译
    *
    */
    
   public function youdaoDic($word){
        $keyfrom = "zhaoqiuti";    //申请APIKEY时所填表的网站名称的内容
        $apikey = "1423482314";  //从有道申请的APIKEY
        
        //有道翻译-json格式
        $url_youdao = 'http://fanyi.youdao.com/fanyiapi.do?keyfrom='.$keyfrom.'&key='.$apikey.'&type=data&doctype=json&version=1.1&q='.$word;
        
        $jsonStyle = file_get_contents($url_youdao);
        $result = json_decode($jsonStyle,true);
        
        $errorCode = $result['errorCode'];
        
        $trans = '';
        if(isset($errorCode)){
            switch ($errorCode){
                case 0:
                    $trans = $result['translation']['0'];
                    break;
                case 20:
                    $trans = '要翻译的文本过长';
                    break;
                case 30:
                    $trans = '无法进行有效的翻译';
                    break;
                case 40:
                    $trans = '不支持的语言类型';
                    break;
                case 50:
                    $trans = '无效的key';
                    break;
                default:
                    $trans = '出现异常';
                    break;
            }
        }
        return $trans;
        
    }
    
    /*
    *  发送纯文本消息
    *
    */
    
    public function sendText($postObj,$contentStr){
        $textTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[%s]]></MsgType>
                    <Content><![CDATA[%s]]></Content>
                    <FuncFlag>0</FuncFlag>
                    </xml>";   
        $fromUsername = $postObj->FromUserName;
        $toUsername = $postObj->ToUserName;
        $keyword = trim($postObj->Content);
        $time = time();
        $msgType = "text";
    	$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
        echo $resultStr;
    }
    
   /*
    *  显示帮助菜单
    *
    */
    
    function showHelper(){
         $contentStr = "感谢您关注【我为球狂】"."\n"."回复1，笑一笑十年少"."\n"."回复2，图文消息"."\n"."回复3，音乐"."\n"."回复4 智能聊天"."\n"."回复5 天气预报"."\n"."回复6 翻译"."\n"."回复0 退出当前功能"."\n"."AI 官方提供，如有雷同纯属抄袭!";
         $this->sendText($postObj,$contentStr);
    }
    
   /*
    *  发送多图文消息
    *
    */
    
    public function sendImgText($postObj,$body_item){
            
        
        
        $newsTplHead = "<xml>
                <ToUserName><![CDATA[%s]]></ToUserName>
                <FromUserName><![CDATA[%s]]></FromUserName>
                <CreateTime>%s</CreateTime>
                <MsgType><![CDATA[news]]></MsgType>
                <ArticleCount>%s</ArticleCount>
                <Articles>";
        $header = sprintf($newsTplHead, $postObj->FromUserName, $postObj->ToUserName,time(),count($body_item));
        
        
        
		$newsTplBody = "<item>
                <Title><![CDATA[%s]]></Title> 
                <Description><![CDATA[%s]]></Description>
                <PicUrl><![CDATA[%s]]></PicUrl>
                </item>";
        foreach($body_item as $key => $value){
       		$body .= sprintf($newsTplBody, $value['title'], $value['description'], $value['picUrl']);
        }
        
        
        
		$newsTplFoot = "</Articles>
                <FuncFlag>%s</FuncFlag>
                </xml>";
        $FuncFlag = 1;
		$footer = sprintf($newsTplFoot, $FuncFlag);
        
        
        
        $result=$header.$body.$footer;
        
        echo $result;
        
    } 
    
    /*
    *  发送多图文消息(新闻)
    *
    */
    
    public function sendImgTextForNews($postObj,$news_item){
            
        
        
        $newsTplHead = "<xml>
                <ToUserName><![CDATA[%s]]></ToUserName>
                <FromUserName><![CDATA[%s]]></FromUserName>
                <CreateTime>%s</CreateTime>
                <MsgType><![CDATA[news]]></MsgType>
                <ArticleCount>%s</ArticleCount>
                <Articles>";
        $header = sprintf($newsTplHead, $postObj->FromUserName, $postObj->ToUserName,time(),count($news_item));
        
        
        
		$newsTplBody = "<item>
                <Title><![CDATA[%s]]></Title> 
                <PicUrl><![CDATA[%s]]></PicUrl>
                <Url><![CDATA[%s]]></Url>
                </item>";
        foreach($news_item as $key => $value){
       		$body .= sprintf($newsTplBody, $value['title'], $value['picUrl'], $value['url']);
        }
        
        
        
		$newsTplFoot = "</Articles>
                <FuncFlag>%s</FuncFlag>
                </xml>";
        $FuncFlag = 1;
		$footer = sprintf($newsTplFoot, $FuncFlag);
        
        
        
        $result=$header.$body.$footer;
        
        echo $result;
        
    } 
    
    
    
    // -----------------------------------------------------------------------------------  数据库操作  ------------------------------------------------------------------------
	
    
    
    /*
    *  保存数据
    *
    */
    public function insert ($user_name,$state){
        $sql = "INSERT INTO  `app_woweiqiukuang`.`user_state` (
                        `id` ,
                        `user_name` ,
                        `state` 
                        )
                        VALUES (
                        NULL ,  '".$user_name."',  '".$state."'
                        );";
        $mysql = new SaeMysql();
        $mysql->runSql($sql);
        $mysql->closeDb();
    } 
    
	/*
    *  根据用户名获得用户状态
    *
    */
    public function query($user_name){
        $mysql = new SaeMysql();
        $sql = "SELECT * FROM user_state WHERE user_name = '".$user_name."';";
        
        $data =  $mysql->getData( $sql );
        
        if(empty($data)){
        	return null;
        }else{
            $array = $data[0];
            return $array[state];
        }
    }
    
    /*
    *  根据用户名删除用户状态
    *
    */
    public function delete($user_name){
        $mysql = new SaeMysql();
        $sql = "DELETE  FROM user_state WHERE user_name = '".$user_name."';";
        
        $mysql->runSql($sql);
        $mysql->closeDb();
        
    }
    
    
    
}
?>
