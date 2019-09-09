<?php
use WHMCS\Database\Capsule;

require_once dirname() . '/yungouos/sdk/Pay/WxPay.php';

function yungouos_config()
{
    $configarray = array(
        "FriendlyName" => array(
            "Type" => "System",
            "Value" => "YunGouOS微信个人支付接口"
        ),
        "mchid" => array(
            "FriendlyName" => "微信支付商户号",
            "Type" => "text",
            "Size" => "32",
        ),
        "key" => array(
            "FriendlyName" => "微信支付密钥",
            "Type" => "password",
            "Size" => "32",
        ),
        "callback" => array(
            "FriendlyName" => "通知地址(Callback)",
            "Type" => "text",
            "Description"=>'http(s)://你的域名/modules/gateways/yungouos/callback.php'
        ),
    );

    return $configarray;
}

function yungouos_form($params)
{
    $n1 = $_SERVER['PHP_SELF'];
    if (stristr($n1, 'viewinvoice')) {
    } else {
        return '<img style="width: 150px" src="' . $systemurl . '/modules/gateways/yungouos/wechat.png" alt="微信支付"  />';
    }
    $systemurl = $params['systemurl'];
    $invoiceid = $params['invoiceid'];
    list($price, $rate) = [$params['amount'], 1];
    $mchid = $params['mchid'];


    $wxpay = new WxPay();
    $result = $wxpay->nativePay($invoiceid, $price, $mchid, $params['description'], 2, null, $params['callback'], null, $params['key']);

    if ($result['code'] != 0) {
        return "API调用失败" . $result;
    }
    $code = '<div class="yungouos"><center><div id="yungouosimg" style="border: 1px solid #AAA;border-radius: 4px;overflow: hidden;margin-bottom: 5px;width: 202px;"><img class="img-responsive pad" src="' . $result . '" style="width: 250px; height: 200px;"></div>';
    $code_ajax = '<a href="#" target="_blank" id="yungouosDiv" class="btn btn-success" style="width: auto; ">使用手机微信扫描上面二维码进行支付<br>
	</a><br><span class="hidden-lg hidden-md">' . $result . '</span></center></div>';
    $code_ajax = $code_ajax . '
	<script>	
    setInterval(function(){load()}, 2000);
    function load(){
        var xmlhttp;
        if (window.XMLHttpRequest){
            xmlhttp=new XMLHttpRequest();
        }else{
            xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
        }
        xmlhttp.onreadystatechange=function(){
            if (xmlhttp.readyState==4 && xmlhttp.status==200){
                trade_state=xmlhttp.responseText;
                if(trade_state=="SUCCESS"){
                    document.getElementById("yungouosimg").style.display="none";
                    document.getElementById("yungouosDiv").innerHTML="支付成功";
                    //延迟 2 秒执行 tz() 方法
                    setTimeout(function(){tz()}, 2000);
                    function tz(){
                        window.location.href="' . $systemurl . '/viewinvoice.php?id=' . $invoiceid . '";
                    }
                }
            }
        }
        xmlhttp.open("get","' . $systemurl . '/modules/gateways/yungouos/invoice_status.php?invoiceid=' . $invoiceid . '",true);
        xmlhttp.send();
    }
</script>';

    $code = $code . $code_ajax;
    $n1 = $_SERVER['PHP_SELF'];
    if (stristr($n1, 'viewinvoice')) {
        return $code;
    } else {
        return '<img style="width: 150px" src="' . $systemurl . '/modules/gateways/yungouos/wechat.png" alt="微信支付"  />';
    }

}

function yungouos_link($params)
{
    return yungouos_form($params);
}

if (!function_exists("autogetamount")) {
    function autogetamount($params)
    {
        $amount = $params['amount'];
        $currencyId = $params['currencyId'];
        $currencys = localAPI("GetCurrencies", [], yungouos_getAdminname());
        if ($currencys['result'] == 'success' and $currencys['totalresults'] >= 1) {

        } else {
            var_dump($currencys);
            throw new \Exception('货币设置错误、API请求错误');
            //如果api请求错误或者货币数量小于1
        }
        //获取货币。
        $currencys = $currencys['currencies']['currency'];
        foreach ($currencys as $currency) {
            if ($currencyId == $currency['id']) {
                $from = $currency;
                break;
            }
        }
        if (!$from) {
            throw new \Exception("货币错误，找不到起始货币。");
        }
        foreach ($currencys as $currency) {
            $hb = strtoupper($currency['code']);
            if ($hb == 'CNY' or $hb == 'RMB') {
                $cny = $currency;
                break;
            }
        }
        if (!$cny) {
            throw new \Exception("找不到人民币货币，请确认后台货币中存在货币代码为CNY的货币！");
        }
        $rate = $cny['rate'] / $from['rate'];
        return [round((double)$rate * $amount, 2), round((double)$rate, 2)];
    }
}
if (!function_exists("yungouos_getAdminname")) {
    function yungouos_getAdminname()
    {
        $admin = Capsule::table('tbladmins')->first();
        return $admin->username;
    }
}
