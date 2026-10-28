<?php

global $Wcms;

$Wcms->addListener('settings', function ($args) {
    global $Wcms;

    $CACHE_BASE = 'cache';
    $CACHE_DIR = $Wcms->dataPath . '/' . $CACHE_BASE;
    if (! file_exists($CACHE_DIR) && ! is_dir($CACHE_DIR)) {
        mkdir($CACHE_DIR);
        file_put_contents("$CACHE_DIR/.htaccess", "<IfModule mod_headers.c>\nHeader set Cache-Control \"max-age=31536000\"\n</IfModule>\n");
    }

    preg_match_all('/https:\/\/raw\.githubusercontent\.com\/(.*?)\/(.*?)\/master\/preview\.jpg/m', $args[0], $matches, PREG_SET_ORDER, 0);
    foreach ($matches as $match) {
        $file = "{$match[1]}-{$match[2]}.jpg";

        if (! file_exists("$CACHE_DIR/$file")) {
            file_put_contents("$CACHE_DIR/$file.tmp", file_get_contents($match[0]));

            $width = 200;
            $height = 150;

            list($width_orig, $height_orig) = getimagesize("$CACHE_DIR/$file.tmp");

            $ratio_orig = $width_orig/$height_orig;

            if ($width/$height > $ratio_orig) {
                $width = $height*$ratio_orig;
            } else {
                $height = $width/$ratio_orig;
            }

            $image_p = imagecreatetruecolor($width, $height);
            $image = false;
            $type = exif_imagetype("$CACHE_DIR/$file.tmp");
            if (in_array($type, [1, 2, 3, 6])) {
                switch ($type) {
                    case 1:
                        $image = imagecreatefromgif("$CACHE_DIR/$file.tmp"); break;
                    case 2:
                        $image = imagecreatefromjpeg("$CACHE_DIR/$file.tmp"); break;
                    case 3:
                        $image = imagecreatefrompng("$CACHE_DIR/$file.tmp"); break;
                    case 6:
                        $image = imagecreatefrombmp("$CACHE_DIR/$file.tmp"); break;
                }
            }
            if ($image) {
                imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
                imagejpeg($image_p, "$CACHE_DIR/$file", 80);
                unlink("$CACHE_DIR/$file.tmp");
            } else {
                rename("$CACHE_DIR/$file.tmp", "$CACHE_DIR/$file");
            }
        }
        $args[0] = str_replace($match[0], $Wcms->url("data/$CACHE_BASE/$file"), $args[0]);
    }

    return $args;
});
