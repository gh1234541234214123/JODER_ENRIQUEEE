<?php
declare(strict_types=1);

// Favicon generation helpers. Produces an SVG favicon (and a PNG fallback when
// the GD extension is available) from a letter, background colour and shape.

function favicon_letter(string $name): string
{
    $name = trim($name);
    if ($name === '') {
        return '?';
    }
    if (function_exists('mb_substr')) {
        $ch = mb_substr($name, 0, 1, 'UTF-8');
        $ch = function_exists('mb_strtoupper') ? mb_strtoupper($ch, 'UTF-8') : strtoupper($ch);
    } else {
        $ch = strtoupper(substr($name, 0, 1));
    }
    return $ch === '' ? '?' : $ch;
}

function favicon_hex(string $color, string $fallback = '#4f46e5'): string
{
    $color = trim($color);
    return preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? strtolower($color) : $fallback;
}

function favicon_shape(string $shape): string
{
    return in_array($shape, ['rounded', 'circle', 'square'], true) ? $shape : 'rounded';
}

function favicon_svg(string $letter, string $bg, string $shape): string
{
    $bg     = favicon_hex($bg);
    $shape  = favicon_shape($shape);
    $letter = htmlspecialchars($letter, ENT_QUOTES | ENT_XML1, 'UTF-8');

    if ($shape === 'circle') {
        $bgShape = '<circle cx="32" cy="32" r="32" fill="' . $bg . '"/>';
    } elseif ($shape === 'square') {
        $bgShape = '<rect width="64" height="64" fill="' . $bg . '"/>';
    } else {
        $bgShape = '<rect width="64" height="64" rx="14" ry="14" fill="' . $bg . '"/>';
    }

    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64">'
        . $bgShape
        . '<text x="32" y="33" font-family="Arial, Helvetica, sans-serif" font-size="34" font-weight="bold" '
        . 'fill="#ffffff" text-anchor="middle" dominant-baseline="central">' . $letter . '</text>'
        . '</svg>';
}

function favicon_png(string $letter, string $bg, string $shape): ?string
{
    if (!extension_loaded('gd') || !function_exists('imagecreatetruecolor')) {
        return null;
    }

    $size = 64;
    $img  = imagecreatetruecolor($size, $size);
    if ($img === false) {
        return null;
    }

    imagealphablending($img, false);
    imagesavealpha($img, true);
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefilledrectangle($img, 0, 0, $size, $size, $transparent);
    imagealphablending($img, true);

    $hex = favicon_hex($bg);
    $r = (int) hexdec(substr($hex, 1, 2));
    $g = (int) hexdec(substr($hex, 3, 2));
    $b = (int) hexdec(substr($hex, 5, 2));
    $fill = imagecolorallocate($img, $r, $g, $b);

    $shape = favicon_shape($shape);
    if ($shape === 'circle') {
        imagefilledellipse($img, 32, 32, $size, $size, $fill);
    } else {
        $radius = $shape === 'square' ? 0 : 14;
        imagefilledrectangle($img, $radius, 0, $size - $radius, $size, $fill);
        imagefilledrectangle($img, 0, $radius, $size, $size - $radius, $fill);
        if ($radius > 0) {
            imagefilledellipse($img, $radius, $radius, $radius * 2, $radius * 2, $fill);
            imagefilledellipse($img, $size - $radius, $radius, $radius * 2, $radius * 2, $fill);
            imagefilledellipse($img, $radius, $size - $radius, $radius * 2, $radius * 2, $fill);
            imagefilledellipse($img, $size - $radius, $size - $radius, $radius * 2, $radius * 2, $fill);
        }
    }

    $white = imagecolorallocate($img, 255, 255, 255);

    $font = null;
    foreach ([
        'C:/Windows/Fonts/arialbd.ttf',
        '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
    ] as $candidate) {
        if (is_file($candidate)) {
            $font = $candidate;
            break;
        }
    }

    if ($font !== null && function_exists('imagettftext')) {
        $bbox = imagettfbbox(30, 0, $font, $letter);
        $tw = $bbox[2] - $bbox[0];
        $th = $bbox[1] - $bbox[7];
        $x = (int) ((($size - $tw) / 2) - $bbox[0]);
        $y = (int) ((($size - $th) / 2) - $bbox[7]);
        imagettftext($img, 30, 0, $x, $y, $white, $font, $letter);
    } else {
        $fw = imagefontwidth(5);
        $fh = imagefontheight(5);
        imagestring($img, 5, (int) (($size - $fw * strlen($letter)) / 2), (int) (($size - $fh) / 2), $letter, $white);
    }

    ob_start();
    imagepng($img);
    $data = ob_get_clean();
    imagedestroy($img);

    return $data === false ? null : $data;
}

function favicon_write(string $dir, string $letter, string $bg, string $shape): array
{
    $dir = rtrim($dir, '/\\');
    favicon_clear($dir);

    $written = [];

    if (@file_put_contents($dir . '/favicon.svg', favicon_svg($letter, $bg, $shape)) !== false) {
        $written[] = 'favicon.svg';
    }

    $png = favicon_png($letter, $bg, $shape);
    if ($png !== null && @file_put_contents($dir . '/favicon.png', $png) !== false) {
        $written[] = 'favicon.png';
    }

    return $written;
}

// Remove every generated/uploaded favicon file so only one format remains.
function favicon_clear(string $dir): void
{
    $dir = rtrim($dir, '/\\');
    foreach (['svg', 'png', 'ico', 'gif', 'webp', 'jpg', 'jpeg'] as $ext) {
        $file = $dir . '/favicon.' . $ext;
        if (is_file($file)) {
            @unlink($file);
        }
    }
}

// Existing favicon files, in preference order (href + MIME type).
function favicon_existing(string $dir): array
{
    $dir = rtrim($dir, '/\\');
    $map = [
        'svg'  => 'image/svg+xml',
        'png'  => 'image/png',
        'ico'  => 'image/x-icon',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
    ];

    $found = [];
    foreach ($map as $ext => $mime) {
        if (is_file($dir . '/favicon.' . $ext)) {
            $found[] = ['href' => 'favicon.' . $ext, 'type' => $mime];
        }
    }
    return $found;
}

// MIME type => saved extension for uploaded images.
function favicon_supported_uploads(): array
{
    return [
        'image/png'                => 'png',
        'image/jpeg'               => 'jpg',
        'image/gif'                => 'gif',
        'image/webp'               => 'webp',
        'image/svg+xml'            => 'svg',
        'image/x-icon'             => 'ico',
        'image/vnd.microsoft.icon' => 'ico',
    ];
}

// Store an uploaded image as the favicon. Throws RuntimeException on failure.
function favicon_upload(string $dir, array $file): array
{
    $dir = rtrim($dir, '/\\');

    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('No se recibió ningún archivo.');
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Error al subir el archivo (código ' . (int) ($file['error'] ?? -1) . ').');
    }
    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        throw new RuntimeException('La imagen no puede superar los 2 MB.');
    }

    $mime = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = (string) finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
    }

    if ($mime === '' || $mime === 'text/plain' || $mime === 'text/xml' || $mime === 'application/xml') {
        $head = (string) @file_get_contents($file['tmp_name'], false, null, 0, 512);
        if (stripos($head, '<svg') !== false) {
            $mime = 'image/svg+xml';
        }
    }

    $map = favicon_supported_uploads();
    if (!isset($map[$mime])) {
        throw new RuntimeException('Formato no soportado. Usa PNG, JPG, GIF, WEBP, SVG o ICO.');
    }

    $ext    = $map[$mime];
    $target = $dir . '/favicon.' . $ext;

    favicon_clear($dir);

    if (!@move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('No se pudo guardar la imagen.');
    }

    return ['favicon.' . $ext];
}
