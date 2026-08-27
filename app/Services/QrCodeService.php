<?php

namespace App\Services;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;

class QrCodeService
{
    public function dataUri(string $content, int $size = 280): string
    {
        $qrCode = new QrCode($content, size: $size);

        return (new SvgWriter)->write($qrCode)->getDataUri();
    }
}