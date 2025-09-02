<?php

namespace simplicateca\burtonsolo\services;

use Craft;
use craft\elements\Asset;
use craft\helpers\Assets as AssetsHelper;
use yii\base\Component;

class ImageService extends Component
{
    public function uploadFromUrls(array $urls, int $volumeId, int $folderId): array
    {
        $assetIds = [];

        foreach (array_unique($urls) as $imageUrl) {
            try {
                $tempPath = tempnam(sys_get_temp_dir(), 'rssimg_');
                file_put_contents($tempPath, file_get_contents($imageUrl));

                $filename = basename(parse_url($imageUrl, PHP_URL_PATH));
                $filename = AssetsHelper::cleanFilename($filename);

                $asset = new Asset();
                $asset->tempFilePath = $tempPath;
                $asset->filename = $filename;
                $asset->newFolderId = $folderId;
                $asset->volumeId = $volumeId;
                $asset->avoidFilenameConflicts = true;

                if (Craft::$app->elements->saveElement($asset)) {
                    $assetIds[] = $asset->id;
                } else {
                    Craft::error('Image save failed: ' . implode(', ', $asset->getErrorSummary(true)), __METHOD__);
                }
            } catch (\Throwable $e) {
                Craft::error("Image upload failed from $imageUrl: " . $e->getMessage(), __METHOD__);
            }
        }

        return $assetIds;
    }
}
