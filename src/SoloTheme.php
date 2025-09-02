<?php

namespace simplicateca\burtonsolo;

use Craft;
use simplicateca\burton\base\BurtonThemeBase;

class SoloTheme extends BurtonThemeBase
{
    protected ?string $_consoleNamespace = 'simplicateca\\burtonsolo\\console\\controllers';

    protected array $_listeners = [
        \simplicateca\burtonsolo\helpers\OpenAiHelper::class,
        \simplicateca\burtonsolo\helpers\HtmlFieldHelper::class,
    ];

    protected array $_components = [
        'rss'   => \simplicateca\burtonsolo\services\RssService::class,
        'image' => \simplicateca\burtonsolo\services\ImageService::class,
    ];

    protected array $_extensions = [
        \simplicateca\burtonsolo\twigextensions\ToolboxTwig::class,
    ];

    protected array $_siteTemplatePath = [
        '_theme' => __DIR__ . DIRECTORY_SEPARATOR . 'templates',
    ];

    protected array $_translations = [
        'burtonsolo' => [
            'class' => \craft\i18n\PhpMessageSource::class,
            'sourceLanguage' => 'en-CA',
            'basePath' => 'simplicateca/burtonsolo/translations',
            'forceTranslation' => true,
            'allowOverrides' => true,
        ],
    ];

    protected array $_siteUrlRules = [
        '/api/generate' => 'burton-solo/default/endpoint',
    ];
}
