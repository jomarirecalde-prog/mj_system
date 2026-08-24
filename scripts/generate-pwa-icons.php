<?php

/**
 * One-time PWA icon generator.
 * Run: php scripts/generate-pwa-icons.php
 */

declare(strict_types=1);

$outDir = __DIR__ . '/../public/icons';
if (! is_dir($outDir) && ! mkdir($outDir, 0755, true) && ! is_dir($outDir)) {
    fwrite(STDERR, "Failed to create {$outDir}\n");
    exit(1);
}

$sizes = [72, 96, 128, 144, 152, 192, 384, 512];
$maskableSizes = [192, 512];

function drawIcon(GdImage $img, int $size, bool $maskable): void
{
    $bg = imagecolorallocate($img, 15, 118, 110); // #0f766e
    $light = imagecolorallocate($img, 69, 210, 191); // #45d2bf
    $white = imagecolorallocate($img, 255, 255, 255);
    $dark = imagecolorallocate($img, 17, 94, 89); // #115e59

    imagefilledrectangle($img, 0, 0, $size, $size, $bg);

    $pad = $maskable ? (int) round($size * 0.1) : (int) round($size * 0.12);
    $inner = $size - ($pad * 2);

    // Rounded card background
    $cardPad = (int) round($inner * 0.08);
    $cardSize = $inner - ($cardPad * 2);
    $cardX = $pad + $cardPad;
    $cardY = $pad + $cardPad;
    imagefilledrectangle($img, $cardX, $cardY, $cardX + $cardSize, $cardY + $cardSize, $dark);

    // QR grid (7x7 simplified pattern)
    $grid = 7;
    $cell = (int) floor($cardSize / ($grid + 2));
    $originX = $cardX + (int) (($cardSize - ($cell * $grid)) / 2);
    $originY = $cardY + (int) (($cardSize - ($cell * $grid)) / 2);

    $pattern = [
        [1, 1, 1, 0, 1, 1, 1],
        [1, 0, 1, 0, 1, 0, 1],
        [1, 0, 1, 1, 1, 0, 1],
        [0, 0, 1, 0, 1, 0, 0],
        [1, 1, 0, 1, 0, 1, 1],
        [0, 1, 1, 0, 1, 0, 1],
        [1, 1, 1, 0, 1, 1, 0],
    ];

    for ($row = 0; $row < $grid; $row++) {
        for ($col = 0; $col < $grid; $col++) {
            if (($pattern[$row][$col] ?? 0) !== 1) {
                continue;
            }
            $x1 = $originX + ($col * $cell);
            $y1 = $originY + ($row * $cell);
            $x2 = $x1 + $cell - max(1, (int) round($cell * 0.12));
            $y2 = $y1 + $cell - max(1, (int) round($cell * 0.12));
            imagefilledrectangle($img, $x1, $y1, $x2, $y2, $row < 2 || $col < 2 || $row > 4 || $col > 4 ? $white : $light);
        }
    }

    // Corner finder patterns
    foreach ([[0, 0], [0, 4], [4, 0]] as [$fr, $fc]) {
        $fx = $originX + ($fc * $cell);
        $fy = $originY + ($fr * $cell);
        $fw = $cell * 3 - max(1, (int) round($cell * 0.12));
        imagefilledrectangle($img, $fx, $fy, $fx + $fw, $fy + $fw, $white);
        $inset = (int) round($cell * 0.55);
        imagefilledrectangle($img, $fx + $inset, $fy + $inset, $fx + $fw - $inset, $fy + $fw - $inset, $dark);
        $core = (int) round($cell * 1.1);
        $cx = $fx + (int) (($fw - $core) / 2);
        $cy = $fy + (int) (($fw - $core) / 2);
        imagefilledrectangle($img, $cx, $cy, $cx + $core, $cy + $core, $white);
    }
}

function savePng(GdImage $img, string $path): void
{
    if (! imagepng($img, $path, 9)) {
        throw new RuntimeException("Failed to write {$path}");
    }
}

foreach ($sizes as $size) {
    $img = imagecreatetruecolor($size, $size);
    imagealphablending($img, true);
    imagesavealpha($img, true);
    drawIcon($img, $size, false);
    savePng($img, "{$outDir}/icon-{$size}.png");
    imagedestroy($img);
    echo "Created icon-{$size}.png\n";
}

foreach ($maskableSizes as $size) {
    $img = imagecreatetruecolor($size, $size);
    imagealphablending($img, true);
    imagesavealpha($img, true);
    drawIcon($img, $size, true);
    savePng($img, "{$outDir}/icon-maskable-{$size}.png");
    imagedestroy($img);
    echo "Created icon-maskable-{$size}.png\n";
}

// Apple touch icon (180x180 is standard)
$appleSize = 180;
$apple = imagecreatetruecolor($appleSize, $appleSize);
imagealphablending($apple, true);
imagesavealpha($apple, true);
drawIcon($apple, $appleSize, false);
savePng($apple, "{$outDir}/apple-touch-icon.png");
imagedestroy($apple);
echo "Created apple-touch-icon.png\n";

// Favicons
foreach ([32, 16] as $favSize) {
    $fav = imagecreatetruecolor($favSize, $favSize);
    imagealphablending($fav, true);
    imagesavealpha($fav, true);
    drawIcon($fav, $favSize, false);
    savePng($fav, "{$outDir}/favicon-{$favSize}.png");
    imagedestroy($fav);
    echo "Created favicon-{$favSize}.png\n";
}

// favicon.ico from 32px (simple PNG fallback also provided)
copy("{$outDir}/favicon-32.png", __DIR__ . '/../public/favicon.png');

echo "Done.\n";
