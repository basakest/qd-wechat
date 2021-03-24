<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use EasyWeChat\Factory;
use Illuminate\Support\Facades\Log;
use App\Models\Message;
use App\Models\Menu;

class TestController extends Controller
{
    protected $app;

    /**
     * 创建公众号实例
     */
    public function __construct()
    {
        if (!($this->app instanceof \EasyWeChat\OfficialAccount\Application)) {
            $this->app = Factory::officialAccount([
                'app_id' => env('WECHAT_APP_ID'),
                'secret' => env('WECHAT_APP_SECRET'),
                'response_type' => env('WECHAT_RESPONSE_TYPE', 'array')
            ]);
        }
    }

    public function checkSignature(Request $request)
    {
        Log::info('request arrived.');
        if ($request->method() == 'POST') {
            $this->app->server->push(function ($message) {
                switch ($message['EventKey']) {
                    case 'test':
                        return Message::all()->random()->content;
                        break;
                    default:
                        return 'else';
                        break;
                }
                //return "hello world";
            });
            $response = $this->app->server->serve();
            return $response;
        } else {

        }
    }

    public function auth()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $echostr = $_GET["echostr"];

        $token = env('WECHAT_TOKEN');

        // 将token、timestamp、nonce三个参数进行字典序排序
        $tmpArr = array($nonce,$token,$timestamp);
        sort($tmpArr,SORT_STRING);

        // 将三个参数字符串拼接成一个字符串进行sha1加密
        $str = implode($tmpArr);
        $sign = sha1($str);

        // 开发者获得加密后的字符串可与signature对比，标识该请求来源于微信
        if ($sign == $signature) {
            return $echostr;
        }
    }

    public function api()
      {
        //get post data, May be due to the different environments
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];//php:input
        //写入日志  在同级目录下建立php_log.txt
        //chmod 777php_log.txt(赋权) chown wwwphp_log.txt(修改主)
        error_log(var_export($postStr,1),3,'php_log.txt');

        //extract post data
        if (!empty($postStr)){
               /* libxml_disable_entity_loader is to prevent XML eXternal Entity Injection, the best way is to check the validity of xml by yourself */
               libxml_disable_entity_loader(true);
               $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
               $fromUsername = $postObj->FromUserName;
               $toUsername = $postObj->ToUserName;
               $keyword = trim($postObj->Content);
               $time = time();
               $textTpl = "<xml>
                           <ToUserName><![CDATA[%s]]></ToUserName>
                           <FromUserName><![CDATA[%s]]></FromUserName>
                           <CreateTime>%s</CreateTime>
                           <MsgType><![CDATA[%s]]></MsgType>
                           <Content><![CDATA[%s]]></Content>
                           <FuncFlag>0</FuncFlag>
                           </xml>";
               //订阅事件
               if($postObj->Event=="subscribe")
               {
                   $msgType = "text";
                   $contentStr = "欢迎关注安子尘，微信babyanzichen";
                   $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                   echo $resultStr;
               }


               //语音识别
               if($postObj->MsgType=="voice"){
                   $msgType = "text";
                   $contentStr = trim($postObj->Recognition,"。");
                   $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                   echo  $resultStr;
               }

               //自动回复
               if(!empty( $keyword ))
               {
                     $msgType = "text";
                   $contentStr = "小朋友你好！";
                   $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                   echo $resultStr;
               }else{
                   echo "Input something...";
               }

       }else {
           echo "";
           exit;
       }
    }
}
