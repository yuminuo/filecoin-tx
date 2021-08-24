<?php


namespace adamyu1024\FilecoinTx;

use deemru\Blake2b;
use Elliptic\EC;
use SKleeschulte\Base32;

/**
 * FIL 签名类
 * Author:Adam Yu
 */
class Sign
{

    protected $result = [];
    private $messageId;
    private $cid;

    /**
     * Sign constructor.
     */
    public function __construct()
    {
    }

    /**
     * 交易签名
     *
     * @param array $transaction 签名数据
     * @param string $privKey 私钥
     * @return string
     * @throws \Exception
     */
    public function sign(array $transaction, string $privKey)
    {
        $transaction['to'] = Bytes::addressToBytes($transaction['to']);
        $transaction['from'] = Bytes::addressToBytes($transaction['from']);

        $this->_object($transaction);
        $this->_number($transaction['version']);
        $this->_hash($transaction['to']);
        $this->_hash($transaction['from']);
        $this->_number($transaction['nonce']);
        $this->_stringNumber($transaction['value']);
        $this->_number($transaction['gasLimit']);
        $this->_stringNumber($transaction['gasFeeCap']);
        $this->_stringNumber($transaction['gasPremium']);
        $this->_number($transaction['method']);
        $this->_hash($transaction['params'] ? bin2hex(base64_decode($transaction['params'])) : "");
        $unsignedMessage = implode("", $this->result);
        $blake2b = new Blake2b();

        $hash = $blake2b->hash(hex2bin($unsignedMessage));
        $cid = $blake2b->hash(hex2bin("0171a0e40220" . bin2hex($hash)));
        $ecc = new EC("secp256k1");

        $sign = $ecc->sign(bin2hex($cid), $privKey, ['canonical' => true]);
        $signature = $sign->r->toString('hex') . $sign->s->toString('hex') . bin2hex(implode('', array_map('chr', [$sign->recoveryParam])));
        /**
         * 计算messageID以及mpoolPush返回的CID
         */
        $unsignedMessageBytes = $this->toBytes($unsignedMessage);//将message转换为byte数组
        $cid_bin = $blake2b->hash(hex2bin($unsignedMessage));
        $this->messageId = 'b' . strtolower(Base32::encodeByteStr(hex2bin("0171a0e40220" . strtolower(bin2hex($cid_bin))), true));
        array_unshift($unsignedMessageBytes, 130);//将130添加进byte数组首位
        array_push($unsignedMessageBytes, 88);//将88添加进byte数组末尾
        $signatureBytes = $this->toBytes($signature);//将signature转换为byte数组
        array_push($unsignedMessageBytes, count($signatureBytes) + 1);//将计算出来的数字添加进byte数组末尾
        array_push($unsignedMessageBytes, 1);//将1添加进byte数组末尾
        $mergeBytes = array_merge($unsignedMessageBytes, $signatureBytes);//合并两个byte数组
        $push_hash = $blake2b->hash(hex2bin(strtolower($this->toStr($mergeBytes))));
        $this->cid = 'b' . strtolower(Base32::encodeByteStr(hex2bin("0171a0e40220" . strtolower(bin2hex($push_hash))), true));
        return base64_encode(hex2bin($signature));
    }

    public function toBytes($string)
    {
        $bytes = array();
        for ($i = 0; $i < strlen($string) / 2; $i++)
            $bytes[$i] = hexdec($string[$i * 2] . $string[$i * 2 + 1]);
        return $bytes;
    }

    public function toStr($bytes)
    {
        $str = "";
        for ($i = 0; $i < count($bytes); $i++)
            $str .= sprintf("%02X", $bytes[$i]);

        return $str;
    }

    /**
     * @param array $array
     */
    private function _object(array $array)
    {
        $majorType = 0b100;
        $length = count($array);
        $this->_chr($majorType, $length);
    }

    /**
     * @param int $number
     * @throws \Exception
     */
    private function _number(int $number = 0)
    {
        $majorType = 0b000;
        switch (true) {
            case $number < 24:
                $this->_chr($majorType, $number);
                break;
            case $number < 0xff:
                $this->_chr($majorType, 24);
                $this->_pushResult($this->serializeBigNum($number));
                break;
            case $number < 0xffff:
                $this->_chr($majorType, 25);
                $this->_pushResult($this->serializeBigNum($number));
                break;
            case $number < 0xffffffff:
                $this->_chr($majorType, 26);
                $this->_pushResult($this->serializeBigNum($number));
                break;
            case $number < 0x1fffffffffffff:
                $this->_hash(Bytes::decToHex($number, false));
                break;
            default:
                throw new \Exception('The number is too large');
        }
    }

    /**
     * @param string $hash
     */
    private function _hash(string $hash)
    {
        $majorType = 0b010;
        $hash = hex2bin($hash);
        $length = strlen($hash);
        $this->_chr($majorType, $length);
        $this->_pushResult(bin2hex($hash));
    }

    /**
     * @param string $string
     */
    private function _stringNumber(string $string)
    {
        $hash = $this->serializeBigNum($string);
        $this->_hash($hash);
    }

    /**
     * @param $majorType
     * @param $additionalInformation
     */
    private function _chr($majorType, $additionalInformation)
    {
        $hash = bin2hex(chr($majorType << 5 | $additionalInformation));
        $this->_pushResult($hash);
    }

    /**
     * @param $hash
     */
    private function _pushResult($hash)
    {
        if ($hash) {
            $this->result[] = $hash;
        }
    }

    /**
     * @param string $value
     * @return string
     */
    private function serializeBigNum(string $value)
    {
        $value = Bytes::decToHex($value, false);
        if (strlen($value) % 2 == 0) {
            return "00" . $value;
        } else {
            return "000" . $value;
        }
    }

    /**
     * @return mixed
     */
    public function getMessageId()
    {
        return $this->messageId;
    }

    /**
     * @return mixed
     */
    public function getCid()
    {
        return $this->cid;
    }

}