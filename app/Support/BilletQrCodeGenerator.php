<?php

namespace App\Support;

use App\Models\Billet;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class BilletQrCodeGenerator
{
    public function payload(Billet $billet): string
    {
        return $billet->ticket_code;
    }

    public function svg(Billet $billet, int $size = 200): string
    {
        $writer = new Writer(
            new ImageRenderer(
                new RendererStyle($size),
                new SvgImageBackEnd,
            ),
        );

        return $writer->writeString($this->payload($billet));
    }
}
