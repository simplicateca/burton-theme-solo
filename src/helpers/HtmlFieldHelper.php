<?php

namespace simplicateca\burtonsolo\helpers;

use Craft;
use yii\base\Event;
use craft\ckeditor\Field as Field;
use craft\ckeditor\events\ModifyConfigEvent;

class HtmlFieldHelper
{
    public static function listeners() : void
    {
        Craft::info('CKEditor Config Watcher module initialized', __METHOD__);

        Event::on(
            Field::class,
            Field::EVENT_MODIFY_CONFIG,
            function (ModifyConfigEvent $event) {
                $configName = $event->ckeConfig->handle ?? '[unknown config]';

                Craft::info(
                    "Modifying CKEditor Config: {$configName}\n" .
                    "Base Config:\n" . print_r($event->baseConfig, true) .
                    "CKE Config:\n" . print_r($event->ckeConfig, true) .
                    "\nToolbar:\n" . print_r($event->toolbar, true),
                    __METHOD__
                );

                // Example: Add a custom button to the toolbar
                //$event->toolbar[] = 'MyCustomButton';
            }
        );

    }
}