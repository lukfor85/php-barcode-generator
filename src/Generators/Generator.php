<?php


namespace Lukfor85\Barcode\Generators;


use Lukfor85\Barcode\Exceptions\InvalidCharacterException;
use Lukfor85\Barcode\Exceptions\UnknownTypeException;

class Generator
{
    public const TYPE_INTERLEAVED_2_5 = 'I25';

    protected function getBarcodeData(string $code, string $type)
    {
        switch (strtoupper($type)) {
            case self::TYPE_INTERLEAVED_2_5:
            { // Interleaved 2 of 5
                $arrcode = $this->barcode_i25($code, false);
                break;
            }
            default:
            {
                throw new UnknownTypeException('');
                break;
            }
        }

        if (!isset($arrcode['maxWidth'])) {
            $arrcode = $this->convertBarcodeArrayToNewStyle($arrcode);
        }

        return $arrcode;
    }

    protected function checksum_s25(string $code)
    {
        $len = strlen($code);
        $sum = 0;
        for ($i = 0; $i < $len; $i += 2) {
            $sum += $code[$i];
        }
        $sum *= 3;
        for ($i = 1; $i < $len; $i += 2) {
            $sum += ($code[$i]);
        }
        $r = $sum % 10;
        if ($r > 0) {
            $r = (10 - $r);
        }

        return $r;
    }

    protected function barcode_i25(string $code, bool $checksum = false)
    {
        $chr['0'] = '11221';
        $chr['1'] = '21112';
        $chr['2'] = '12112';
        $chr['3'] = '22111';
        $chr['4'] = '11212';
        $chr['5'] = '21211';
        $chr['6'] = '12211';
        $chr['7'] = '11122';
        $chr['8'] = '21121';
        $chr['9'] = '12121';
        $chr['A'] = '11';
        $chr['Z'] = '21';
        if ($checksum) {
            // add checksum
            $code .= $this->checksum_s25($code);
        }
        if ((strlen($code) % 2) != 0) {
            // add leading zero if code-length is odd
            $code = '0' . $code;
        }
        // add start and stop codes
        $code = 'AA' . strtolower($code) . 'ZA';

        $bararray = ['code' => $code, 'maxw' => 0, 'maxh' => 1, 'bcode' => []];
        $k = 0;
        $clen = strlen($code);
        for ($i = 0; $i < $clen; $i = ($i + 2)) {
            $char_bar = $code[$i];
            $char_space = $code[$i + 1];
            if (!isset($chr[$char_bar]) || !isset($chr[$char_space])) {
                throw new InvalidCharacterException('');
            }
            // create a bar-space sequence
            $seq = '';
            $chrlen = strlen($chr[$char_bar]);
            for ($s = 0; $s < $chrlen; $s++) {
                $seq .= $chr[$char_bar][$s] . $chr[$char_space][$s];
            }
            $seqlen = strlen($seq);
            for ($j = 0; $j < $seqlen; ++$j) {
                if (($j % 2) == 0) {
                    $t = true; // bar
                } else {
                    $t = false; // space
                }
                $w = $seq[$j];
                $bararray['bcode'][$k] = ['t' => $t, 'w' => $w, 'h' => 1, 'p' => 0];
                $bararray['maxw'] += $w;
                ++$k;
            }
        }

        return $bararray;
    }

    protected function convertBarcodeArrayToNewStyle($oldBarcodeArray)
    {
        $newBarcodeArray = [];
        $newBarcodeArray['code'] = $oldBarcodeArray['code'];
        $newBarcodeArray['maxWidth'] = $oldBarcodeArray['maxw'];
        $newBarcodeArray['maxHeight'] = $oldBarcodeArray['maxh'];
        $newBarcodeArray['bars'] = [];
        foreach ($oldBarcodeArray['bcode'] as $oldbar) {
            $newBar = [];
            $newBar['width'] = $oldbar['w'];
            $newBar['height'] = $oldbar['h'];
            $newBar['positionVertical'] = $oldbar['p'];
            $newBar['drawBar'] = $oldbar['t'];
            $newBar['drawSpacing'] = !$oldbar['t'];

            $newBarcodeArray['bars'][] = $newBar;
        }

        return $newBarcodeArray;
    }
}