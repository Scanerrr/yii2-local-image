<?php

namespace Scanerrr\Image;

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

    protected static $cachePath = 'cache';

    /**
     * @param string $file
     * @param null|float $width
     * @param null|float $height
     * @return string|boolean
     */
    public static function resize($file, $width = null, $height = null)
    {
        if (!self::checkFile(Yii::getAlias('@webroot/') . $file)) return '';

        self::$width = $width ?? self::$width;
        self::$height = $height ?? self::$height;

        $cachedFile = self::formatCachedName($file);

        // if cached return file path
        if (file_exists($cachedFile)) return self::checkFile($cachedFile) ? Yii::getAlias('@web/') . $cachedFile : '';
        $filePath = $file;
        $file = Yii::$app->urlManager->createAbsoluteUrl($file);

        list($origWidth, $origHeight) = getimagesize($file);

        $imageType = exif_imagetype($file);

        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($file);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($file);
                break;
            default:
                return false;
        }

        $scaleW = self::$width / $origWidth;
        $scaleH = self::$height / $origHeight;

        $scale = min($scaleW, $scaleH);

        if ($scale == 1 && $scaleH == $scaleW && $imageType != IMAGETYPE_PNG) {
            copy(Yii::getAlias('@webroot/') . $filePath, Yii::getAlias('@webroot/') . $cachedFile);
            return Yii::getAlias('@web/') . $cachedFile;
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
            return Yii::getAlias('@web/') . $cachedFile;
        }
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
        return self::$cachePath . $info['dirname'] . '/' . $info['filename'] . '_' . self::$width . '_' . self::$height . '.' . $info['extension'];
    }

    /**
     * @param string $file absolute path
     * @return bool
     */
    protected static function checkFile($file) {
        return is_file($file);
    }
}