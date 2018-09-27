yii2-local-image
=========================

Simple Yii2 extension for resizing images and saving them to cache 


Installation
-------------
Add yii2-local-image to the require section of your composer.json file:
```json
{
    "require": {
        "scanerrr/yii2-local-image": "dev-master"
    }
}
```

Or simply run
```bash
php composer.phar require scanerrr/yii2-local-image
```

Usage
-------------

```php
Html::img(Image::resize($pathToImage, $width, $height = null));
```

Resize image with saving aspect ratio, simply miss "height"
```php
Html::img(Image::resize($pathToImage, 120))

/* 
* Original image with resolution 1920x1080
* Result image gonna be 120x67
*/
```