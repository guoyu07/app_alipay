<?php

/**
 * Class AppAliPay
 */

class AppAliPay
{

    public static function checkParamEmpty($filed, $param)
    {
        if (empty($param)) {
            throw new Exception($filed. '字段不能为空', 500);
        }
        return $param;
    }

    public static function createAliPayCode($aliPrivateKey, $aliPayId, $aliPayEmail, $orderId, $fee, $notifyUrl, $subject = '', $body = '')
    {
        try {
            $returnData = [
                'partner' => self::checkParamEmpty('aliPayId', $aliPayId),
                'seller_id' => self::checkParamEmpty('aliPayEmail', $aliPayEmail),
                'out_trade_no' => self::checkParamEmpty('orderId', $orderId),
                'subject' => $subject ? : time(),
                'body' => $body ? : time(),
                'total_fee' => self::checkParamEmpty('fee', $fee),
                'notify_url' => self::checkParamEmpty('notifyUrl', $notifyUrl),
                'service' => "mobile.securitypay.pay",
                'payment_type' => "1",
                '_input_charset' => "utf-8",
                'it_b_pay' => "30m",
                'show_url' => "m.alipay.com",
            ];

            $signStr = self::createKeyValue($returnData);
            ksort($signStr);

            $returnData['sign'] = self::createRsaSign($signStr, self::checkParamEmpty('aliPrivateKey', $aliPrivateKey));
            $returnData['sign_type'] = 'RSA';

            $data = [
                'code' => 200,
                'data' => self::createKeyValue($returnData)
            ];
            return $data;
        } catch (Exception $e) {
            return [
                'code' => $e->getCode(),
                'msg' => $e->getMessage(),
            ];
        }
    }


    /**
     * @param $query
     * @return mixed
     * 以key value 按 & 的形式连接字串
     */
    public static function createKeyValue($query)
    {
        //重新组装参数
        $params = array();
        foreach($query as $key => $value){
            $params[] = $key .'='. $value;
        }
        $data = implode('&', $params);

        return $data;
    }

    /**
     * @param $signStr
     * @param $privateKey
     * @return mixed
     * 生成支付宝签名
     */
    public static function createRsaSign($signStr, $privateKey)
    {
        // 私钥密码
        $passPhrase = '';

        $privateKey = "-----BEGIN RSA PRIVATE KEY-----\n" . $privateKey ;
        $privateKey = $privateKey ."\n-----END RSA PRIVATE KEY-----";

        //私钥
        $private_id = openssl_pkey_get_private( $privateKey , $passPhrase);

        // 签名
        $signature = '';
        openssl_sign($signStr, $signature, $private_id, OPENSSL_ALGO_SHA1 );

        openssl_free_key( $private_id );

        //加密后的内容通常含有特殊字符，需要编码转换下
        $signature = base64_encode($signature);

        return urlencode( $signature );
    }
}