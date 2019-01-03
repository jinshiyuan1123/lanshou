<?php
/**
 * 原生支付（扫码支付）及公众号支付的异步回调通知
 * 说明：需要在native.php或者jsapi.php中的填写回调地址。例如：http://www.xxx.com/wx/notify.php
 * 付款成功后，微信服务器会将付款结果通知到该页面
 */
header('Content-type:text/html; Charset=utf-8');
$mchid = '1516685681';          //微信支付商户号 PartnerID 通过微信支付商户资料审核后邮件发送
$appid = 'wx624f7eef92fcc2ab';  //公众号APPID 通过微信支付商户资料审核后邮件发送
$apiKey = '2d8a1a54eb1c4170e452c22a6e052a67';   //https://pay.weixin.qq.com 帐户设置-安全设置-API安全-API密钥-设置API密钥
$wxPay = new WxpayService($mchid,$appid,$apiKey);
$result = $wxPay->notify();
date_default_timezone_set('PRC');
// $timestamp = date('Y-m-d H:i:s',time());
// $fp = fopen('./file.txt', 'a+b');

// fwrite($fp, var_export($result, true));
 
// fclose($fp);

if($result){
    //完成你的逻辑
    //例如连接数据库，获取付款金额$result['cash_fee']，获取订单号$result['out_trade_no']，修改数据库中的订单状态等;
        
        $dbms='mysql';     //数据库类型
        $host='127.0.0.1'; //数据库主机名
        $dbName='lanshou';    //使用的数据库
        $user='root';      //数据库连接用户名
        $pass='Xg63876608';          //对应的密码
        $dsn="$dbms:host=$host;dbname=$dbName";


        try {
        $pdo = new PDO($dsn, $user, $pass); //初始化一个PDO对象
        
        $pdo = null;
        } catch (PDOException $e) {
        die ("Error!: " . $e->getMessage() . "<br/>");
        }
        //默认这个不是长连接，如果需要数据库长连接，需要最后加一个参数：array(PDO::ATTR_PERSISTENT => true) 变成这样：
        $db = new PDO($dsn, $user, $pass, array(PDO::ATTR_PERSISTENT => true));
        if($result['out_trade_no']){
           $sql2 = "update wx_payment set success='success' where out_trade_no='{$result['out_trade_no']}'"; 
       }else{
            $sql2 = "update wx_payment set success='fail' where out_trade_no='{$result['out_trade_no']}'";
       }
        
        $rowlist = $db->exec($sql2);

        // echo $notifyUrl;

        // $sql="insert into wx_payment values('','1551','1991','11','111','111','111','111','111','222','success','33','55')";
        // $sql = "insert into wx_payment values('','{$unified['appid']}','{$unified['attach']}','{$unified['body']}','{$unified['mch_id']}','{$unified['nonce_str']}','{$unified['notify_url']}','{$unified['out_trade_no']}','{$unified['spbill_create_ip']}','{$totalFee}','success','{$unified['trade_type']}','{$timestamp}')";
        // var_dump($sql);
        // $data = $db->exec($sql);
    }else{
    
        $sql = "update wx_payment set success='fail' where out_trade_no='{$result['out_trade_no']}'";
        $data = $db->exec($sql);
    // echo '付款失败！';
    }
class WxpayService
{
    protected $mchid;
    protected $appid;
    protected $apiKey;
    public function __construct($mchid, $appid, $key)
    {
        $this->mchid = $mchid;
        $this->appid = $appid;
        $this->apiKey = $key;
    }

    public function notify()
    {
        $config = array(
            'mch_id' => $this->mchid,
            'appid' => $this->appid,
            'key' => $this->apiKey,
        );
        $postStr = file_get_contents('php://input');
        $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($postObj === false) {
            die('parse xml error');
        }
        if ($postObj->return_code != 'SUCCESS') {
            die($postObj->return_msg);
        }
        if ($postObj->result_code != 'SUCCESS') {
            die($postObj->err_code);
        }
        $arr = (array)$postObj;
        unset($arr['sign']);
        if (self::getSign($arr, $config['key']) == $postObj->sign) {
            echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
            return $arr;
        }
    }

    /**
     * 获取签名
     */
    public static function getSign($params, $key)
    {
        ksort($params, SORT_STRING);
        $unSignParaString = self::formatQueryParaMap($params, false);
        $signStr = strtoupper(md5($unSignParaString . "&key=" . $key));
        return $signStr;
    }
    protected static function formatQueryParaMap($paraMap, $urlEncode = false)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if (null != $v && "null" != $v) {
                if ($urlEncode) {
                    $v = urlencode($v);
                }
                $buff .= $k . "=" . $v . "&";
            }
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }
}
