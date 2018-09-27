yii2-local-image
=========================

Simple Yii2 extension for resizing images and saving them to cache 


Installation
-------------
1. Add yii2-local-image to the require section of your composer.json file:
    ```json
    {
        "require": {
            "scanerrr/yii2-local-image": "dev-master"
        }
    }
    ```
2. run
    <pre>
    php composer.phar update
    </pre>

Or simply run
<pre>
php composer.phar require scanerrr/yii2-local-image
</pre>

Usage
-------------

```php
Html::img(Image::resize($pathToImage, 75, 75))
```