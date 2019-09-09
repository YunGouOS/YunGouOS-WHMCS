<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
use WHMCS\Database\Capsule;

# Required File Includes
include("../../../init.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");
require_once dirname() . '/sdk/util/WxPaySign.php';

$gatewaymodule = "yungouos"; # Enter your gateway module name here replacing template
$GATEWAY = getGatewayVariables($gatewaymodule);
if (!$GATEWAY["type"])
    die("Module Not Activated"); # Checks gateway module is active before accepting callback

$order_data = $_POST;
$payKey = $GATEWAY['key'];

//支付结果（1、支付成功）
$status = trim($order_data['code']);
//系统订单号（YunGouOS系统内单号）
$orderNo = trim($order_data['orderNo']);
//商户订单号
$invoiceid = trim($order_data['outTradeNo']);
//微信支付单号（微信支付单号）
$transid = trim($order_data['payNo']);
//支付金额 单位：元
$amount = trim($order_data['money']);
//商户号
$mchId = trim($order_data['mchId']);
//支付成功时间
$time = trim($order_data['time']);
//附加数据
$attach = trim($order_data['attach']);
//用户openId
$openId = trim($order_data['openId']);
//签名（见签名算法文档）
$sign = trim($order_data['sign']);

$fee = 0;

$signParam = array(
    "code" => $status,
    "orderNo" => $orderNo,
    "outTradeNo" => $invoiceid,
    "payNo" => $transid,
    "money" => $amount,
    "mchId" => $mchId,
    "sign"=>$sign
);

$signUtil = new WxPaySign();

if (!$signUtil->checkSing($signParam, $payKey)) {
    echo 'check sign faild';
    exit;
}

if ($status == '1') {
    $invoiceid = checkCbInvoiceID($invoiceid, $GATEWAY["name"]);
    checkCbTransID($transid);
    addInvoicePayment($invoiceid, $transid, Capsule::table('tblinvoices')->where('id', $invoiceid)->get()->total, 0, $gatewaymodule);//Capsule::table('tblinvoices')->where('id',$invoiceid)->update(['status'=>'Paid']);
    logTransaction($GATEWAY["name"], $_POST, "Successful");
    echo 'SUCCESS';
    exit;
}
echo 'faild';
