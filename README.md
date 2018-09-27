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
Html::img(Image::resize($pathToImage, 75, 75))
```