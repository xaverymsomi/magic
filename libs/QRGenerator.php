<?php

namespace Libs;

//use PHPQRCode\QRcode;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\QrCode;

class QRGenerator {

    private $qr_data;
    private $filename;
    private $qr_name;

    function __construct($data, $qr_name) {
        $this->qr_data = $data;
        $this->qr_name = $qr_name;
    }

//    function generateQR() {
//        $this->filename = MX17_APP_ROOT .'/assets/public/' . $this->qr_name . ".png";
//        $qrstring = json_encode($this->qr_data);
//        QRcode::png($qrstring, $this->filename, "L", 7, 2);
//    }
    function generateQR() {
        $this->filename = MX17_APP_ROOT .'/assets/public/' . $this->qr_name . ".png";
        $qrstring = json_encode($this->qr_data);
        $writer = new PngWriter();
        $qrcode = QrCode::create($qrstring)
            ->setEncoding(new Encoding('ISO-8859-1'))
            ->setSize(500)
            ->setMargin(0)
            ->setRoundBlockSizeMode(new RoundBlockSizeModeMargin('enlarge'))
            ->setForegroundColor(new Color(0, 0, 0))
            ->setBackgroundColor(new Color(255, 255, 255));
        $result = $writer->write($qrcode);
        $result->saveToFile($this->filename);
    }

    function displayQRCode() {
        return $this->filename;
    }
}
