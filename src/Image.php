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

    protected static $initialWidth = 150;
    protected static $initialHeight = 150;

    protected static $width;
    protected static $height;

    protected static $cachePath = 'cache/';

    protected static $placeholder = 'https://placeholder.pics/svg/';

    /**
     * reset initial properties
     */
    protected static function init()
    {
        self::$width = self::$initialWidth;
        self::$height = self::$initialHeight;
    }

    /**
     * resize
     *
     * @param string $file
     * @param null|float $width
     * @param null|float $height
     * @return string
     * @throws Exception
     */
    public static function resize(string $file, float $width = null, float $height = null): string
    {
        // reset values to default after using method
        self::init();

        $rootFilePath = self::getRootPath($file);

        // return placeholder image if file not exist
        if (!is_file($rootFilePath)) return self::getPlaceholder();

        list($origWidth, $origHeight) = getimagesize($file);

        // set width and calculate height by aspect ratio
        self::$width = $width ?? self::$width;
        self::$height = $height
            ? self::$height
            : ($origHeight * self::$width) / $origWidth;

        // return image from cache
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
                return self::getPlaceholder();
        }

        $scaleW = self::$width / $origWidth;
        $scaleH = self::$height / $origHeight;

        $scale = min($scaleW, $scaleH);

        // copy file to
        if ($scale == 1 && $scaleH == $scaleW && $imageType !== IMAGETYPE_PNG) {
            copy($rootFilePath, self::getRootPath($cachedFile));
            return self::getFile($cachedFile);
        }

        $newWidth = (int)($origWidth * $scale);
        $newHeight = (int)($origHeight * $scale);
        $xPos = (int)((self::$width - $newWidth) / 2);
        $yPos = (int)((self::$height - $newHeight) / 2);
        $imageOld = $image;

        $image = imagecreatetruecolor(self::$width, self::$height);

        if ($imageType === IMAGETYPE_PNG) {
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

        if (!is_resource($image)) return self::getPlaceholder();
        if ($imageType === IMAGETYPE_JPEG) {
            imagejpeg($image, $cachedFile, 90);
        } elseif ($imageType === IMAGETYPE_PNG) {
            imagepng($image, $cachedFile);
        }
        imagedestroy($image);

        return self::getFile($cachedFile);
    }

    /**
     * get string by width and height
     *
     * @example $width = 150, $height = 150
     * @example return 150_150
     *
     * @param string $separator
     * @return string
     */
    protected static function getSizesAsString(string $separator = '_'): string
    {
        return floor(self::$width) . $separator . floor(self::$height);
    }

    /**
     *
     * @param $file
     * @return string
     */
    protected static function getFile($file)
    {
        if (is_file($file)) return self::getWebPath($file);
        return self::getPlaceholder();
    }

    /**
     *
     * For example "name_150_150.jpg"
     *
     * @param $file
     * @return string
     * @throws Exception
     */
    protected static function formatCachedName(string $file): string
    {
        $info = pathinfo($file);
        FileHelper::createDirectory(self::$cachePath . $info['dirname'], 0775, true);
        return self::$cachePath . $info['dirname'] . '/' . $info['filename'] . '_' . self::getSizesAsString() . '.' . $info['extension'];
    }

    protected static function getWebPath(string $path): string
    {
        return Yii::getAlias('@web/') . $path;
    }

    protected static function getRootPath(string $path): string
    {
        return Yii::getAlias('@webroot/') . $path;
    }

    /**
     * @return string
     */
    public static function getPlaceholder(): string
    {
        return self::$placeholder . self::getSizesAsString('x');
    }
}