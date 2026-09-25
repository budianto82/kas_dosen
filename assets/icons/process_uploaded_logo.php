<?php
/**
 * Process uploaded UNPAM logo into PWA icons
 */

$srcPath = __DIR__ . '/../img/logo_unpam.png';
if (!file_exists($srcPath)) {
    die("Source logo not found: $srcPath\n");
}

$src = imagecreatefrompng($srcPath);
if (!$src) {
    die("Failed to load source image\n");
}

$w = imagesx($src);
$h = imagesy($src);

// Generate 192 and 512 icons
foreach ([192, 512] as $size) {
    $dst = imagecreatetruecolor($size, $size);
    
    // Transparent or white background
    $white = imagecolorallocate($dst, 255, 255, 255);
    imagefilledrectangle($dst, 0, 0, $size, $size, $white);
    
    // Copy and resample maintaining aspect ratio centered
    $ratio = min($size / $w, $size / $h);
    $newW = (int)($w * $ratio);
    $newH = (int)($h * $ratio);
    $dstX = (int)(($size - $newW) / 2);
    $dstY = (int)(($size - $newH) / 2);

    imagecopyresampled($dst, $src, $dstX, $dstY, 0, 0, $newW, $newH, $w, $h);
    imagepng($dst, __DIR__ . "/icon-{$size}.png", 9);
    imagedestroy($dst);
    echo "Generated icon-{$size}.png\n";
}

imagedestroy($src);
echo "SUCCESS\n";
