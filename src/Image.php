<?php

namespace Scanerrr;

use Yii;
use yii\base\Exception;
use yii\helpers\FileHelper;

/**
 *  A image tool class
 *
 * @author scanerrr
 */
class Image
{

    protected static $width = 150;
    protected static $height = 150;

    protected static $cachePath = 'cache/';

    protected static $placeholder = 'https://placeholder.pics/svg/';

    /**
     * @param string $file
     * @param null|float $width
     * @param null|float $height
     * @return string|boolean
     */
    public static function resize($file, $width = null, $height = null)
    {
        $rootFilePath = self::getRootPath($file);

        if (!is_file($rootFilePath)) return self::$placeholder . self::getSizesAsString('x');

        list($origWidth, $origHeight) = getimagesize($file);

        self::$width = $width ?? self::$width;
        self::$height = $height
            ? self::$height
            : ($origHeight * self::$width) / $origWidth;

        $cachedFile = self::formatCachedName($file);

        if (is_file($cachedFile)) return self::getFile($cachedFile);

        $imageType = exif_imagetype($rootFilePath);

        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($rootFilePath);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($rootFilePath);
                break;
            default:
                return false;
        }

        $scaleW = self::$width / $origWidth;
        $scaleH = self::$height / $origHeight;

        $scale = min($scaleW, $scaleH);

        if ($scale == 1 && $scaleH == $scaleW && $imageType != IMAGETYPE_PNG) {
            copy($rootFilePath, self::getRootPath($cachedFile));
            return self::getFile($cachedFile);
        }

        $newWidth = (int)($origWidth * $scale);
        $newHeight = (int)($origHeight * $scale);
        $xPos = (int)((self::$width - $newWidth) / 2);
        $yPos = (int)((self::$height - $newHeight) / 2);
        $imageOld = $image;

        $image = imagecreatetruecolor(self::$width, self::$height);

        if ($imageType == IMAGETYPE_PNG) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            $background = imagecolorallocatealpha($image, 255, 255, 255, 127);
            imagecolortransparent($image, $background);
        } else {
            $background = imagecolorallocate($image, 255, 255, 255);
        }

        imagefilledrectangle($image, 0, 0, self::$width, self::$height, $background);
        imagecopyresampled($image, $imageOld, $xPos, $yPos, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
        imagedestroy($imageOld);

        if (is_resource($image)) {
            if ($imageType == IMAGETYPE_JPEG) {
                imagejpeg($image, $cachedFile, 90);
            } elseif ($imageType == IMAGETYPE_PNG) {
                imagepng($image, $cachedFile);
            }
            imagedestroy($image);
            return self::getFile($cachedFile);
        }
    }

    protected static function getSizesAsString($separator = '_')
    {
        return floor(self::$width) . $separator . floor(self::$height);
    }

    protected static function getFile($file)
    {
        if (is_file($file)) return self::getWebPath($file);
        return self::$placeholder . self::getSizesAsString('x');
    }

    /**
     *
     * For example "name_150_150.jpg"
     *
     * @param $file
     * @return string
     */
    protected static function formatCachedName($file)
    {
        $info = pathinfo($file);
        try {
            FileHelper::createDirectory(self::$cachePath . $info['dirname'], 0775, true);
        } catch (Exception $e) {
        }
        return self::$cachePath . $info['dirname'] . '/' . $info['filename'] . '_' . self::getSizesAsString() . '.' . $info['extension'];
    }

    protected static function getWebPath($path)
    {
        return Yii::getAlias('@web/') . $path;
    }

    protected static function getRootPath($path)
    {
        return Yii::getAlias('@webroot/') . $path;
    }
}