<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;
class IndexController extends Controller {
    public function index(){
        define("TOKEN", "MilFun123");
        //echo TOKEN;
        if (!isset($_GET['echostr'])) {
            
            return $this->responseMsg();
        }else{
            return $this->valid();
        }

        /*  第二种方法，功能都写在Milfun.class.php里,不需要数据库服务情况下用；
        define("TOKEN", "MilFun");
        import("@.ORG.Api.Milfun");
        $milfun = new \MilFun();
        if (!isset($_GET['echostr'])) {
            return $milfun->responseMsg();
        }else{
            return $milfun->valid();
        }  */
    }

    public function addMsgNum($string,$object){
        $user=M('User');
        $openid="".$object->FromUserName;
        if($user->where(array('openid' =>$openid))->find()){
          $res=$user->where(array('openid' =>$openid))->setInc($string);
        }else{
          $das['openid']=$openid;
          $das['wxid']="".$object->ToUserName;
          $das['first_sub']=date('Y-m-d h:i:s');
          $user->add($das);
        }
        
    }

    //验证签名
    public function valid()
    {
        $echoStr = $_GET["echostr"];
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if($tmpStr == $signature){
            header('content-type:text');
            echo $echoStr;
            exit;
        }
    }
    


    //响应消息
    public function responseMsg()
    {
       // $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        $postStr =  file_get_contents("php://input");
        if (!empty($postStr)){
            $this->logger("R ".$postStr);
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $RX_TYPE = trim($postObj->MsgType);
            //消息类型分离
            switch ($RX_TYPE)
            {
                case "event":
                    $result = $this->receiveEvent($postObj);
                    break;
                case "text":
                    $result = $this->receiveText($postObj);
                    break;
                case "image":
                    $result = $this->receiveImage($postObj);
                    break;
                case "location":
                    $result = $this->receiveLocation($postObj);
                    break;
                case "voice":
                    $result = $this->receiveVoice($postObj);
                    break;
                case "video":
                    $result = $this->receiveVideo($postObj);
                    break;
                case "link":
                    $result = $this->receiveLink($postObj);
                    break;
                default:
                    $result = "unknown msg type: ".$RX_TYPE;
                    break;
            }
            $this->logger("T ".$result);
            echo $result;
        }else {
            echo "";
            exit;
        }
    }
    //接收事件消息
    private function receiveEvent($object)
    {
        $user=M('User');
        $openid="".$object->FromUserName;
        $wxid = ''.$object->ToUserName;
        $content = "";
        switch ($object->Event)
        {
            case "subscribe":
                 $cc=12000+$user->count();
                if($user->where(array('openid' =>$openid))->find()){
                    
                    $res=$user->where(array('openid' =>$openid))->setInc('subtime');
                    $das['status']=1;
                    $user->where(array('openid' =>$openid))->save($das);
                }else{

                    $das['openid']=$openid;
                    $das['wxid']=$wxid;
                    $das['first_sub']=date('Y-m-d h:i:s');
                    $user->add($das);
                }
                $kw=M('Keyword');
                $resu = $kw->where( array('keyword' => 'milfun','wxid' => $wxid))->select();

                if($resu[0]['sort']==1){//回复图文
                    $content = array();
                    $content[] = array("Title"=>'您是玩转编程第'.$cc.'位粉丝', "Description"=>$resu[0]['desc'], "PicUrl"=>'https://mmbiz.qpic.cn/mmbiz_jpg/8gOJUiakA2UUmTWUque6CmOp5AIxMFU9JXvNuK8vQ3m03WpR29rsrBS59eYVYfQwpspOmwvNygibx6eY9Tbu49yg/0?wx_fmt=jpeg', "Url" =>'https://mp.weixin.qq.com/s/FRYA5IrMDceb3R0oyBs7bA');
 $content[] = array("Title"=>'『米饭篇』六招妙计轻松愉快过大年～', "Description"=>'', "PicUrl"=>'https://mmbiz.qpic.cn/mmbiz_jpg/8gOJUiakA2UUmTWUque6CmOp5AIxMFU9JWlDRUQWxGAUdKcyah8SKYicGP0iaIAVuXdGiacpRDLAglhKdjib8zYVAvQ/0?wx_fmt=jpeg', "Url" =>' http://mp.weixin.qq.com/s/gZ9r4yDdGwkJWgPDG1AawQ ');
$content[] = array("Title"=>'『礼包篇』史上最高大上的超大礼包～', "Description"=>'', "PicUrl"=>'https://mmbiz.qpic.cn/mmbiz_jpg/8gOJUiakA2UUmTWUque6CmOp5AIxMFU9JWlDRUQWxGAUdKcyah8SKYicGP0iaIAVuXdGiacpRDLAglhKdjib8zYVAvQ/0?wx_fmt=jpeg', "Url" =>'http://milfun.fun/s/goods.php');
$content[] = array("Title"=>'『大神篇』带你学尽米饭大学黑科技～', "Description"=>'', "PicUrl"=>'https://mmbiz.qpic.cn/mmbiz_jpg/8gOJUiakA2UUmTWUque6CmOp5AIxMFU9JWlDRUQWxGAUdKcyah8SKYicGP0iaIAVuXdGiacpRDLAglhKdjib8zYVAvQ/0?wx_fmt=jpeg', "Url" =>'http://mp.weixin.qq.com/mp/homepage?__biz=MzA3NTk4MDE0MQ==&hid=2&sn=864029b607ce3a6a2c4abdc230bf8aa1&scene=18#wechat_redirect');
$content[] = array("Title"=>'『至尊篇』米饭联盟の人才招募计划～', "Description"=>'', "PicUrl"=>'https://mmbiz.qpic.cn/mmbiz_jpg/8gOJUiakA2UUmTWUque6CmOp5AIxMFU9JWlDRUQWxGAUdKcyah8SKYicGP0iaIAVuXdGiacpRDLAglhKdjib8zYVAvQ/0?wx_fmt=jpeg', "Url" =>'https://mp.weixin.qq.com/s/xqJMwbZ3UUq6oYyF-HT_YA');
                }elseif($resu[0]['sort']==2) {//回复文字
                    $content = $resu[0]['desc'];
                }
               
                break;
            case "unsubscribe":
                //降低用户等级，不会加
                $das['status']=0;
                $user->where(array('openid' =>$openid))->setDec('level',1);
                $user->where(array('openid' =>$openid))->save($das);
                $content = "取消关注";
                break;
            case "SCAN":
                $content = "扫描场景 ".$object->EventKey;
                break;
            case "CLICK":
                switch ($object->EventKey)
                {
                    case "COMPANY":
            $content = array();
                        $content[] = array("Title"=>"多图文1标题", "Description"=>"", "PicUrl"=>"http://discuz.comli.com/weixin/weather/icon/cartoon.jpg", "Url" =>"http://m.cnblogs.com/?u=txw1958");
                        break;
                    default:
                        $content = "点击菜单：".$object->EventKey;
                        break;
                }
                break;
            case "LOCATION":
                $content = "上传位置：纬度 ".$object->Latitude.";经度 ".$object->Longitude;
                break;
            case "VIEW":
                $content = "跳转链接 ".$object->EventKey;
                break;
            case "MASSSENDJOBFINISH":
                $content = "消息ID：".$object->MsgID."，结果：".$object->Status."，粉丝数：".$object->TotalCount."，过滤：".$object->FilterCount."，发送成功：".$object->SentCount."，发送失败：".$object->ErrorCount;
                break;
            default:
                $content = "receive a new event: ".$object->Event;
                break;
        }
        if(is_array($content)){
            if (isset($content[0])){
                $result = $this->transmitNews($object, $content);
            }else if (isset($content['MusicUrl'])){
                $result = $this->transmitMusic($object, $content);
            }
        }else{
            $result = $this->transmitText($object, $content);
        }

        return $result;
    }

public function add_material($file_info){
  $access_token=$this->get_access_token('wxc76f3d2aee4fb6a3','4c21342f81bd6571a563926c331a6744');
  $url="https://api.weixin.qq.com/cgi-bin/material/add_material?access_token={$access_token}&type=image";
  $ch1 = curl_init ();
  $timeout = 5;
  $real_path="{$_SERVER['DOCUMENT_ROOT']}{$file_info['filename']}";
  //$real_path=str_replace("/", "\\", $real_path);
  $data= array("media"=>"@{$real_path}",'form-data'=>$file_info);
  curl_setopt ( $ch1, CURLOPT_URL, $url );
  curl_setopt ( $ch1, CURLOPT_POST, 1 );
  curl_setopt ( $ch1, CURLOPT_RETURNTRANSFER, 1 );
  curl_setopt ( $ch1, CURLOPT_CONNECTTIMEOUT, $timeout );
  curl_setopt ( $ch1, CURLOPT_SSL_VERIFYPEER, FALSE );
  curl_setopt ( $ch1, CURLOPT_SSL_VERIFYHOST, false );
  curl_setopt ( $ch1, CURLOPT_POSTFIELDS, $data );
  $result = curl_exec ( $ch1 );
  curl_close ( $ch1 );
  if(curl_errno()==0){
    $result=json_decode($result,true);
    //var_dump($result);
    return $result['media_id'];
  }else {
    return false;
  }
}


    //接收文本消息
    private function receiveText($object)
    {

        $this->addMsgNum('msg_num',$object);
        $keyword = trim($object->Content);
        $wxid = ''.$object->ToUserName;
        $fid=''.$object->FromUserName;
        $kw=M('Keyword');
        $goods=M('Goods');//米饭书屋
        $gooods=M('Gooods');//米饭书屋支持记录
        if($kw->where( array('keyword' => $keyword))->find()){
            $resu = $kw->where( array('keyword' => $keyword,'wxid' => $wxid))->select();
            if($resu[0]['sort']==1){//回复图文
              $data = array();
              $data[] = array("Title"=>$resu[0]['title'], "Description"=>$resu[0]['desc'], "PicUrl"=>$resu[0]['pic'], "Url" =>$resu[0]['link']);
              $content = $data;
            }elseif ($resu[0]['sort']==2) {//回复文字
              $content = $resu[0]['desc'];
            }elseif ($resu[0]['sort']==3) {//回复公众号等级
              $user=M('User');
              $openid="".$object->FromUserName;
              $info = $user->where(array('openid' =>$openid))->find();
              $content ='您当前等级为：'. $info['level'].',总共发了：'. $info['msg_num'].'条消息！目前积分为：'. $info['points'];
            }else{
              $content = '米饭也不懂你发了什么，你的消息已经传送到火星上了！';
            }
            
        }elseif($keyword == '幸运彩票'){
            $content =$this->get_ticket();
        }elseif($keyword == '好友印象'){
            $link ='http://milfun.cc/s/impress/index.php?mfid='.$object->FromUserName;
            $data[] = array("Title"=>"想知道别人对你的印象是什么吗？", "Description"=>"", "PicUrl"=>'https://mmbiz.qpic.cn/mmbiz_jpg/8gOJUiakA2UW0nhFOa9ibHFbvHUhyYibX9rNwm6PlpCy5xSH70x2hkxxicFa1ota1EI9Os4eAiaRDNQqcibBu5uHiaQfw/0?wx_fmt=jpeg', "Url" =>$link );
            $content = $data;
        }elseif($keyword == '签到'){
            $link ='http://milfun.fun/s/milfun/index.php?mfid='.$object->FromUserName;
            $data[] = array("Title"=>"你能每天坚持签到吗？", "Description"=>"", "PicUrl"=>'https://mmbiz.qlogo.cn/mmbiz/8gOJUiakA2UUuYuyMdbZKGrBosODXPdDjybf6ekfwI97Tf6PVIX9g9BRWDMibdib1VFE3n1IAnccD6P67IHVQ5WFQ/0?wx_fmt=png', "Url" =>$link );
            $content = $data;
        }elseif($goods->where( array('gid' => $keyword))->find()){//米饭书屋
            $comd['gid']=$keyword;
            $comd['uid']=$fid;
            if($gooods->where($comd)->find()){
                $content ='MilFun提醒你:每人同一个资源只能支持一次哦！';
            }else{
                $dat2['gid']=$keyword;
                $dat2['uid']=$fid;
                $dat2['date']=date('Y-m-d H:i:s');
                $gooods->add($dat2);
                $goods->where( array('gid' => $keyword))->setInc('now',1);
                $inf=$goods->where( array('gid' => $keyword))->find();
                if($inf['now']==$inf['need']){
                    $dat['isdown']=1;
                    $goods->where( array('gid' => $keyword))->save($dat);
                }
                $content ='MilFun提醒你:该资源已经获得'.$inf['now'].'人支持，达到'.$inf['need'].'人，才可以下载哦！';
        
               }
            
            
        }elseif($keyword == '苹果'){
            $data=array();
            $link ='http://milfun.cc/wechat/iphone/'.file_get_contents('http://milfun.cc/wechat/iphone/milfun.php');
            $data[] = array("Title"=>"iphone7预定成功！", "Description"=>"恭喜你预定成功点击查看详情", "PicUrl"=>$link , "Url" =>$link );
            $content = $data;
        }else{//用图灵机器人回复
            $content =file_get_contents('http://www.tuling123.com/openapi/api?key=f9f8197a1a1816aade3a8fcaf0420b92&info='.$keyword);
            $content=json_decode($content,true);
            //新闻类
            if($content['code']=='302000'){
            //$content=$content['text']."地址:".$content['list'][0]['detailurl'];
                $data=array();
                $data[] = array("Title"=>$content['list'][0]['article'], "Description"=>$content['list'][0]['source'], "PicUrl"=>"http://fun.dbc2u.com/milfun/images/logo.jpg", "Url" =>$content['list'][0]['detailurl']);
                $data[] = array("Title"=>$content['list'][1]['article'], "Description"=>$content['list'][1]['source'], "PicUrl"=>"http://fun.dbc2u.com/milfun/images/logo.jpg", "Url" =>$content['list'][1]['detailurl']);
            //列车类
            }elseif ($content['code']=='200000') {
                $data=array();
                $data[] = array("Title"=>"MilFun已帮你找到班次，点击查看！", "Description"=>"", "PicUrl"=>"http://fun.dbc2u.com/milfun/images/logo.jpg", "Url" =>$content['url']);
            
            //菜谱 
            }elseif ($content['code']=='308000') {
                $data=array();
                $data[] = array("Title"=>$content['list'][0]['name'], "Description"=>$content['list'][0]['info'], "PicUrl"=>$content['list'][0]['icon'], "Url" =>$content['list'][0]['detailurl']);
            }else{
                $data=$content['text'];
            }
            $content=array();
            $content=$data;
       }
        

        if(is_array($content)){
            if (isset($content[0]['PicUrl'])){
                $result = $this->transmitNews($object, $content);
            }else if(isset($content['MediaId'])){
                $result = $this->transmitImage($object, $content);
}
else if (isset($content['MusicUrl'])){
                $result = $this->transmitMusic($object, $content);
            }
        }else{
            $result = $this->transmitText($object, $content);

            
        }

        return $result;
        //return 
    }

    //接收图片消息
    private function receiveImage($object)
    {
        $this->addMsgNum('msg_num',$object);
      
            //新闻类
        $content = $object->MediaId;
        $result = $this->transmitText($object, $content);
        return $result;
    }

    //接收位置消息
    private function receiveLocation($object)
    {
        $this->addMsgNum('msg_num',$object);
        $content = "MilFun告诉你，你发送的是位置，纬度为：".$object->Location_X."；经度为：".$object->Location_Y."；缩放级别为：".$object->Scale."；位置为：".$object->Label;
        $result = $this->transmitText($object, $content);
        return $result;
    }

    //接收语音消息
    private function receiveVoice($object)
    {
        $this->addMsgNum('msg_num',$object);
        if (isset($object->Recognition) && !empty($object->Recognition)){
            $content = "MilFun告诉你，你刚才说的是：".$object->Recognition;
            $result = $this->transmitText($object, $content);
        }else{
            $content = array("MediaId"=>$object->MediaId);
            $result = $this->transmitVoice($object, $content);
        }

        return $result;
    }

    //接收视频消息
    private function receiveVideo($object)
    {
        $this->addMsgNum('msg_num',$object);
        $content = array("MediaId"=>$object->MediaId, "ThumbMediaId"=>$object->ThumbMediaId, "Title"=>"", "Description"=>"");
        $result = $this->transmitVideo($object, $content);
        return $result;
    }

    //接收链接消息
    private function receiveLink($object)
    {
        $this->addMsgNum('msg_num',$object);
        $content = "MilFun告诉你，你发送的是链接，标题为：".$object->Title."；内容为：".$object->Description."；链接地址为：".$object->Url;
        $result = $this->transmitText($object, $content);
        return $result;
    }

    //回复文本消息
    private function transmitText($object, $content)
    {
        $xmlTpl = "<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[%s]]></Content>
</xml>";
        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), $content);
        return $result;
    }

    //回复图片消息
    private function transmitImage($object, $imageArray)
    {
        $itemTpl = "<Image>
    <MediaId><![CDATA[%s]]></MediaId>
</Image>";

        $item_str = sprintf($itemTpl, $imageArray['MediaId']);

        $xmlTpl = "<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[image]]></MsgType>
$item_str
</xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }

    //回复语音消息
    private function transmitVoice($object, $voiceArray)
    {
        $itemTpl = "<Voice>
    <MediaId><![CDATA[%s]]></MediaId>
</Voice>";

        $item_str = sprintf($itemTpl, $voiceArray['MediaId']);

        $xmlTpl = "<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[voice]]></MsgType>
$item_str
</xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }

    //回复视频消息
    private function transmitVideo($object, $videoArray)
    {
        $itemTpl = "<Video>
    <MediaId><![CDATA[%s]]></MediaId>
    <ThumbMediaId><![CDATA[%s]]></ThumbMediaId>
    <Title><![CDATA[%s]]></Title>
    <Description><![CDATA[%s]]></Description>
</Video>";

        $item_str = sprintf($itemTpl, $videoArray['MediaId'], $videoArray['ThumbMediaId'], $videoArray['Title'], $videoArray['Description']);

        $xmlTpl = "<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[video]]></MsgType>
$item_str
</xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }

    //回复图文消息
    private function transmitNews($object, $newsArray)
    {
        if(!is_array($newsArray)){
            return;
        }
        $itemTpl = "    <item>
        <Title><![CDATA[%s]]></Title>
        <Description><![CDATA[%s]]></Description>
        <PicUrl><![CDATA[%s]]></PicUrl>
        <Url><![CDATA[%s]]></Url>
    </item>
";
        $item_str = "";
        foreach ($newsArray as $item){
            $item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']);
        }
        $xmlTpl = "<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[news]]></MsgType>
<ArticleCount>%s</ArticleCount>
<Articles>
$item_str</Articles>
</xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), count($newsArray));
        return $result;
    }

    //回复音乐消息
    private function transmitMusic($object, $musicArray)
    {
        $itemTpl = "<Music>
    <Title><![CDATA[%s]]></Title>
    <Description><![CDATA[%s]]></Description>
    <MusicUrl><![CDATA[%s]]></MusicUrl>
    <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
</Music>";

        $item_str = sprintf($itemTpl, $musicArray['Title'], $musicArray['Description'], $musicArray['MusicUrl'], $musicArray['HQMusicUrl']);

        $xmlTpl = "<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[music]]></MsgType>
$item_str
</xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }

    //回复多客服消息
    private function transmitService($object)
    {
        $xmlTpl = "<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[transfer_customer_service]]></MsgType>
</xml>";
        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }

    //日志记录
    private function logger($log_content)
    {
        if(isset($_SERVER['HTTP_APPNAME'])){   //SAE
            sae_set_display_errors(false);
            sae_debug($log_content);
            sae_set_display_errors(true);
        }else if($_SERVER['REMOTE_ADDR'] != "127.0.0.1"){ //LOCAL
            $max_size = 10000;
            $log_filename = "log.xml";
            if(file_exists($log_filename) and (abs(filesize($log_filename)) > $max_size)){unlink($log_filename);}
            file_put_contents($log_filename, date('H:i:s')." ".$log_content."\r\n", FILE_APPEND);
        }
    }

    public function get_access_token($appid,$secret){
        if (empty ( $appid ) || empty ( $secret )) {
            return 0;
        }
        $key = 'access_token_apppid_' . $appid . '_' . $secret;
        $res = S ( $key );
        if ($res !== false)
            return $res;
        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appid . '&secret=' . $secret;
        $tempArr = json_decode ( file_get_contents ( $url ), true );
        if (@array_key_exists ( 'access_token', $tempArr )) {
            S ( $key, $tempArr ['access_token'], $tempArr ['expires_in'] );
            return $tempArr ['access_token'];
        } else {
            return 0;
        }
    }

    public function get_user_info($appid,$secret,$openid){
        $key = 'access_token_apppid_' . $appid . '_' . $secret;
        $token = S ( $key );
        $url='https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$token.'&openid='.$openid;
        $tempArr = json_decode ( file_get_contents ( $url ), true );
        return $tempArr;
    }

    public function get_ticket(){
        $c = $c2 =array();
        //初始化及随机打乱
        for ($i=0; $i <35 ; $i++) { 
            $a[$i] = $i + 1;
        }
        shuffle($a);
        for ($k=0; $k < 12; $k++) { 
            $b[$k] = $k + 1;
        }
        shuffle($b);
        //选取前5个数
        for ($ca=0; $ca < 5; $ca++) { 
            do {
              $num = mt_rand(0,34);
            } while (in_array($a[$num], $c));
            $c[$ca] =$a[$num];
        }
        //选取后2个数
        for ($cb=0; $cb < 2; $cb++) { 
            do {
              $num2 = mt_rand(0,11);
            } while (in_array($b[$num2], $c2));
            $c2[$cb] =$b[$num2] ;
        }
        //输出
        $result= '';
        sort($c);sort($c2);//排序
        foreach ($c as $value) {
            $result = $result.$value.'-';
        }
        foreach ($c2 as $value) {
            $result = $result.$value.'-';
        }
        return $result;
    }
}