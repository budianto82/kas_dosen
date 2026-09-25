<?php
/**
 * Generate App Icons for PWA
 */

function generateIcon(int $size, string $filePath): void {
    if (!extension_loaded('gd')) {
        return;
    }

    $img = imagecreatetruecolor($size, $size);
    
    // Colors
    $navy = imagecolorallocate($img, 11, 47, 100);       // #0B2F64 UNPAM Primary
    $gold = imagecolorallocate($img, 245, 158, 11);      // #F59E0B Accent Gold
    $white = imagecolorallocate($img, 255, 255, 255);
    $softBlue = imagecolorallocate($img, 30, 78, 150);

    // Background
    imagefilledrectangle($img, 0, 0, $size, $size, $navy);

    // Rounded inner circle border
    $center = (int)($size / 2);
    $radius = (int)($size * 0.42);
    imagearc($img, $center, $center, $radius * 2, $radius * 2, 0, 360, $gold);
    imagearc($img, $center, $center, ($radius * 2) - 2, ($radius * 2) - 2, 0, 360, $gold);

    // Inner subtle circle
    imagefilledellipse($img, $center, $center, (int)($size * 0.72), (int)($size * 0.72), $softBlue);

    // Text: "SI" and "UNPAM"
    // Draw simple geometric wallet/coin or letters
    $font = 5; // Built-in font
    $text1 = "KAS DOSEN";
    $text2 = "SI UNPAM";

    $len1 = strlen($text1) * imagefontwidth($font);
    $len2 = strlen($text2) * imagefontwidth($font);

    imagestring($img, $font, (int)($center - ($len1 / 2)), (int)($center - 20), $text1, $gold);
    imagestring($img, $font, (int)($center - ($len2 / 2)), (int)($center + 5), $text2, $white);

    imagepng($img, $filePath);
    imagedestroy($img);
}

$dir = __DIR__;
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

generateIcon(192, $dir . '/icon-192.png');
generateIcon(512, $dir . '/icon-512.png');

echo "Icons generated successfully!\n";
