<?php
namespace app\api\controller;
use think\facade\Request;
use think\Db;
   // 字符编码
header("Content-Type:text/html; charset=utf-8");
   
   // 微信接口类
  // 该方法摘自网上添加了小程序模板推送。
class WxMsg{
       private static $appid;
       private static $appsecret;
   
      function __construct(){
          self::$appid = 'wxcb9482fce6636420';      // 开发者ID(AppID)
         self::$appsecret = '9fa51504ad2b9c8ee80730321b2d70a8';  // 开发者密码(AppSecret)
      }
  
  
      // 微信授权地址
      public static function getAuthorizeUrl($url){
          $url_link = urlencode($url);
          return "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . self::$appid . "&redirect_uri={$url_link}&response_type=code&scope=snsapi_base&state=1#wechat_redirect";
      }
  
      // 获取TOKEN
      public static function getToken(){
        $access_tokens = cookie("access_token");
        if(empty($access_tokens)){
          $urla = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . self::$appid . "&secret=" . self::$appsecret;
          $outputa = self::curlGet($urla);
          $result = json_decode($outputa, true);
          cookie("access_token",$result['access_token'],'/',60*60*2);
          return $result['access_token'];
        }
   
          return  $access_tokens;
      }
  
      /**
       * getUserInfo 获取用户信息
       * @param  string $code         微信授权code
       * @param  string $weiwei_token Token
       * @return array
       */
      public static function getUserInfo($code, $weiwei_token){
          $access_token_url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . self::$appid . "&secret=" . self::$appsecret . "&code={$code}&grant_type=authorization_code";
          $access_token_json = self::curlGet($access_token_url);
          $access_token_array = json_decode($access_token_json, true);
          $openid = $access_token_array['openid'];
          $new_access_token = $weiwei_token;
  
          //全局access token获得用户基本信息
          $userinfo_url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$new_access_token}&openid={$openid}";
          $userinfo_json = self::curlGet($userinfo_url);
          $userinfo_array = json_decode($userinfo_json, true);
          return $userinfo_array;
      }
  
      /**
       * addLog 日志记录
       * @param string $log_content 日志内容
       */
      public static function addLog($log_content = ''){
          $data = "";
          $data .= "DATE: [ " . date('Y-m-d H:i:s') . " ]\r\n";
          $data .= "INFO: " . $log_content . "\r\n\r\n";
          file_put_contents('/wechat.log', $data, FILE_APPEND);
      }
  
      /**
       * 发送get请求
       * @param string $url 链接
       * @return bool|mixed
       */
      private static function curlGet($url){
          $curl = curl_init();
          curl_setopt($curl, CURLOPT_URL, $url);
         curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
         curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
         curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
         if(curl_errno($curl)){
             return 'ERROR ' . curl_error($curl);
         }
         curl_close($curl);
         return $output;
     }
 
     /**
      * 发送post请求
      * @param string $url 链接
      * @param string $data 数据
      * @return bool|mixed
      */
     private static function curlPost($url, $data = null){
         $curl = curl_init();
         curl_setopt($curl, CURLOPT_URL, $url);
         curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
         curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
         if(!empty($data)){
             curl_setopt($curl, CURLOPT_POST, 1);
             curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
         }
         curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
         $output = curl_exec($curl);
         curl_close($curl);
         return $output;
     }

     public function pushMsg($openid,$tdata){
	     // 公众号消息推送
	     return self::pushMessage([
	         'openid' => $openid, // 用户openid
	         'template_id' => $tdata['id'], // 填写你自己的消息模板ID
	         'data' => $tdata['data'],
	         'url' => $tdata['url'] // 消息跳转链接
	     ]);
		 
     }


     //微信公众号
    public static function pushMessage($data = [],$topcolor = '#0000'){
        $template = [
            'touser'      => $data['openid'],
            "template_id"=> $data['template_id'],
            "url"=>$data['url'],
            'data' => $data['data'],   
        ];
        $json_template = json_encode($template);
       // $url = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/uniform_send?access_token=". self::getToken();
        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=" . self::getToken();
        $result = self::curlPost($url, urldecode($json_template));
        $resultData = json_decode($result, true);
        return $resultData;
    }

    //微信公众号
    public static function pushMessage($data = [],$topcolor = '#0000'){
        $template = [
            'touser'      => $data['openid'],
            "template_id"=> $data['template_id'],   //微信公众号内template_id
            "url"=>$data['url'],
            'data' => $data['data'],   
        ];
        $json_template = json_encode($template);
        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=" . self::getToken();
        $result = self::curlPost($url, urldecode($json_template));
        $resultData = json_decode($result, true);
        return $resultData;
    }


    //微信小程序
    public static function pushMessages($data = [],$topcolor = '#0000'){
          $template = [
            'touser'      => $data['openid'],
            "weapp_template_msg"=>[
               "template_id"=> $data['template_id'],
               "page"=>$data['url'],
               "form_id"=>$data['form_id'],   //小程序提交form_id
               'data' => $data['data'],
            ]
          ];
          $json_template = json_encode($template);
          $url = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/uniform_send?access_token=". self::getToken();
          $result = self::curlPost($url, urldecode($json_template));
          $resultData = json_decode($result, true);
          return $resultData;
      }
    
    
 }
 
 /**
  * get_page_url 获取完整URL
  * @return url
  */
 // function get_page_url($type = 0){
 //     $pageURL = 'http';
 //     if($_SERVER["HTTPS"] == 'on'){
 //         $pageURL .= 's';
 //     }
 //     $pageURL .= '://';
 //     if($type == 0){
 //         $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
 //     }else{
 //         $pageURL .= $_SERVER["SERVER_NAME"];
 //     }
 //     return $pageURL;
 // }
 
 // 获取用户openid
 
 // 微信接口类
 // $WeChat = new WeChat();
 // if(empty($_GET['code']) || !isset($_GET['code'])){
 //     // 通过授权获取code
 //     $url = get_page_url();
 //     $authorize_url = $WeChat->getAuthorizeUrl($url);
 //     header("Location:{$authorize_url}"); // 重定向浏览器
 //     exit();
 // }else{
 //     // 获取微信用户信息
 //     $code = $_GET['code'];
 //     $weiwei_token = $WeChat->getToken(); // 获取微信token
 //     $user_info = $WeChat->getUserInfo($code, $weiwei_token);
 //     $openid = $user_info['openid'];
 //     # 公众号消息推送
 //     $WeChat::pushMessage([
 //         'openid' => $openid, // 用户openid
 //         'access_token' => $weiwei_token,
 //         'template_id' => "ONZapeZi5OzxHym7IaZw7q4eJHEV4L6lzdQrEIWBs60", // 填写你自己的消息模板ID
 //         'data' => [ // 模板消息内容，根据模板详情进行设置
 //             'first'    => ['value' => urlencode("尊敬的某某某先生，您好，您本期还款已成功扣收。"),'color' => "#743A3A"],
 //             'keyword1' => ['value' => urlencode("2476.00元"),'color'=>'blue'],
 //             'keyword2' => ['value' => urlencode("13期"),'color'=>'blue'],
 //             'keyword3' => ['value' => urlencode("15636.56元"),'color' => 'green'],
 //             'keyword4' => ['value' => urlencode("6789.23元"),'color' => 'green'],
 //             'remark'   => ['value' => urlencode("更多贷款详情，请点击页面进行实时查询。"),'color' => '#743A3A']
 //         ],
 //         'url_link' => 'https://www.cnblogs.com/' // 消息跳转链接
 //     ]);
 // }