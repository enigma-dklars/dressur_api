<?php

namespace App\Twig;

use App\Services\ImageHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MyTwigExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('convertImageToBase64', [ImageHelper::class, 'convertImageToBase64']),
        ];
    }
}
