<?php


namespace adamyu1024\FilecoinTx;

use SKleeschulte\Base32;

/**
 * byte数组与字符串转化类
 */
class Bytes
{
    /**
     * 将fil地址转换为bytes
     *
     * @param string $address
     * @return string
     * @throws \Exception
     */
    public static function addressToBytes(string $address)
    {
        $type = $address[1];
        switch ($type) {
            case 0:
                if (strlen($address) > 18) {
                    throw new \Exception("FIL id '{$address}' error");
                }
                try {
                    $decode = Leb128::uencode(substr($address, 2));
                    $decode = "0" . 0 . bin2hex($decode);
                } catch (\Exception $e) {
                    throw new \Exception("FIL id '{$address}' error");
                }
                break;
            case 1:
            case 2:
            case 3:
                try {
                    $decode = Base32::decodeToByteStr(strtoupper(substr($address, 2)), true);
                    if (($type != 3 && strlen($decode) != 24) || ($type == 3 && strlen($decode) != 52)) {
                        throw new \Exception("FIL address '{$address}' error");
                    }
                    $decode = "0" . $type . substr(bin2hex($decode), 0, -8);
                } catch (\Exception $e) {
                    throw new \Exception("FIL address '{$address}' error");
                }
                break;
        }
        return $decode;
    }

    /**
     * 转为十六进制.
     * @param number|string $value 十进制的数
     * @param bool $mark 是否加0x头
     * @return string
     */
    public static function decToHex($value, $mark = true)
    {
        $hexvalues = [
            '0', '1', '2', '3', '4', '5', '6', '7',
            '8', '9', 'a', 'b', 'c', 'd', 'e', 'f',
        ];
        $hexval = '';
        while ($value != '0') {
            $hexval = $hexvalues[bcmod($value, '16')] . $hexval;
            $value = bcdiv($value, '16', 0);
        }

        return $mark ? '0x' . $hexval : $hexval;
    }
}