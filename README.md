# filecoin-tx
Filecoin transaction library in PHP.

# Install

```
composer require adamyu1024/filecoin-tx
```

# Usage

#### sign

Returns signed of transaction data.

`sign(array $message, string $privateKey)`

String privateKey - hexed private key with zero prefixed.

###### Example

* Sign the transaction data.

```php
use adamyu1024\FilecoinTx\Sign;

        $message = [
            'version' => 0,
            'from' => "t1hb4737umuzzbcfd3xxk3bdtwezgistj7dycypvi",
            'to' => "t1dynqskhlixt5eswpff3a72ksprqmeompv3pbesy",
            'value' => "1000000000000000000", // 此参数必须是字符串 1 FIL
            'method' => 0, // 表示send
            'nonce' => 0, // 交易序号，用接口 MpoolGetNonce 获取
            'params' => "", // base64 编码数据
            'gasLimit' => 7948138, // 可用接口估算 GasEstimateGasLimit
            'gasPremium' => "2347948138", // 此参数必须是字符串，可用接口估算 GasEstimateGasPremium
            'gasFeeCap' => "2347948138" // 此参数必须是字符串，可用接口估算 GasEstimateFeeCap
        ];
        
        $sign = new Sign();
        $signData = $sign->sign($message,"ee2868ca9485673b36c38ba4f18551be25d08dd9be9bd24c44cd626b37cadae4");

        $signMessageData = [
            'message'=>$message,
            'signature'=>[
                'data'=>$signData,
                'type'=>1 //SECP256K1=1
            ]
        ];
```

# License
MIT