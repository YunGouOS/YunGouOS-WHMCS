<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
use WHMCS\Database\Capsule;

# Required File Includes
include("../../../init.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");
require_once dirname() . '../YunGouOS-PHP-SDK/util/PaySign.php';

$gatewaymodule = "yungouos"; # Enter your gateway module name here replacing template
$GATEWAY = getGatewayVariables($gatewaymodule);
if (!$GATEWAY["type"])
    die("Module Not Activated"); # Checks gateway module is active before accepting callback

//$order_data = $_POST;
$payKey = $GATEWAY['key'];

//支付结果（1、支付成功）
$code = trim($_POST['code']);
//系统订单号（YunGouOS系统内单号）
$orderNo = trim($_POST['orderNo']);
//商户订单号
$invoiceid = trim($_POST['outTradeNo']);
//支付单号（支付单号）
$transid = trim($_POST['payNo']);
//支付金额 单位：元
$money = trim($_POST['money']);
//商户号
$mchId = trim($_POST['mchId']);
//支付渠道（枚举值 wxpay、alipay）
$payChannel=trim($_POST['payChannel']);
//支付成功时间
$time = trim($_POST['time']);
//附加数据
$attach = trim($_POST['attach']);
//用户openId
$openId = trim($_POST['openId']);
//签名（见签名算法文档）
$sign = trim($_POST['sign']);

$paySign = new PaySign();

$fee = 0;

$signParam = array(
    "code" => $code,
    "orderNo" => $orderNo,
    "outTradeNo" => $invoiceid,
    "payNo" => $transid,
    "money" => $money,
    "mchId" => $mchId,
    "sign" => $sign,
);
//Array ( [payNo] => 2021011822001496421411213615 [code] => 1 [mchId] => 2088202395139042 [orderNo] => Y15041119713919 [money] => 300.00 [outTradeNo] => 3 [sign] => DA4BD235D5120436952526D0B065F26E [payChannel] => alipay [time] => 2021-01-18 15:04:18 )

$signUtil = new PaySign();
try {
    //此处不一定需要像异步回调那么严格，可以直接获取outTradeNo 您自己的订单号，查询您系统库内的订单状态即可

    $key = $payKey;
    //判断支付方式是支付宝还是微信 决定对应的加密密钥应该是什么值（密钥获取：登录 yungouos.com-》微信支付/支付宝-》商户管理-》独立密钥）
    //而不是使用聚合支付的key，发送请求的时候是用聚合key，但是回调的时候需要对商户类型进行判定，并使用对应的key才可以
    switch($payChannel){
        //此处因为没启用独立密钥 支付密钥支付宝与微信支付是一样的 （密钥获取：登录 yungouos.com-》我的账户-》商户管理-》商户密钥）
        case 'wxpay':
            $key = "";
            break;
        case 'alipay':
            $key = "";
            break;
        default:
            break;
    }
    //验证签名
    $result=$signUtil->checkNotifySign($_POST,$key);
    if (!$result) {
        //签名错误
        echo $key.'check sign faild';
        exit();
    }

    //签名验证成功
    if ($code == 1) {
        //支付成功 处理您自己的业务
        $invoiceid = checkCbInvoiceID($invoiceid, $GATEWAY["name"]);
        checkCbTransID($transid);
        addInvoicePayment($invoiceid, $transid, $money, 0, $gatewaymodule);//Capsule::table('tblinvoices')->where('id',$invoiceid)->update(['status'=>'Paid']);
        logTransaction($GATEWAY["name"], $_POST, "Successful");
        echo 'SUCCESS';
        exit;
    }


} catch (Exception $e) {
    echo('<script type="text/javascript">alert("' . $e->getMessage() . '");window.close();</script>');
}
?>

