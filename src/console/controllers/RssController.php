<?php

namespace simplicateca\burtonsolo\console\controllers;

use Craft;
use craft\console\Controller;
use craft\elements\Entry;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use yii\console\ExitCode;
use yii\helpers\Console;

use simplicateca\burton\twigextensions\CollectionBase;
use simplicateca\burton\twigextensions\CardBase;
use simplicateca\burton\services\ImageService;

class RssController extends Controller
{
    public function actionUpdate(): int
    {
        $this->stdout("Starting feed update...\n", Console::FG_GREEN);

        // Define the volume for uploaded images
        $volumeHandle = 'rssImages';
        $volume = Craft::$app->volumes->getVolumeByHandle($volumeHandle);
        if (!$volume) {
            $this->stderr("Missing volume: {$volumeHandle}\n", Console::FG_RED);
            return ExitCode::CONFIG;
        }

        // Get all feed entries
        $feeds = Entry::find()
            ->section('feeds')
            ->type('feeds')
            ->all();

        $collectionBase = new CollectionBase();
        $imageService = Craft::$container->get(ImageService::class);

        foreach ($feeds as $entry) {
            $this->stdout("Processing entry: {$entry->title}\n", Console::FG_YELLOW);

            // Get parsed feed items
            try {
                $items = $collectionBase->collection($entry);
            } catch (\Throwable $e) {
                $this->stderr("  Failed to load feed: {$e->getMessage()}\n", Console::FG_RED);
                continue;
            }

            // Get existing URLs in the matrix field to avoid duplicates
            $existingUrls = [];
            foreach ($entry->history->all() as $block) {
                $existingUrls[] = (string)$block->link;
            }

            $newBlocks = [];

            foreach ($items as $item) {
                try {
                    $card = CardBase::make($item);
                } catch (\Throwable $e) {
                    $this->stderr("  Failed to parse item: {$e->getMessage()}\n", Console::FG_RED);
                    continue;
                }

                if (in_array($card->link, $existingUrls)) {
                    continue;
                }

                // Upload all images and get asset IDs
                $imageAssetIds = $imageService->uploadFromUrls(
                    $card->images,
                    $volume->id,
                    $volume->folderId
                );

                // Prepare matrix block fields
                $fields = [
                    'title' => $card->title,
                    'headline' => $card->headline,
                    'summary' => $card->summary,
                    'link' => $card->link,
                    'published' => $card->published ?? new \DateTime(),
                    'json' => Json::encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                ];

                if (!empty($imageAssetIds)) {
                    $fields['images'] = $imageAssetIds;
                }

                $newBlocks[] = [
                    'type' => 'historyEntry',
                    'enabled' => true,
                    'fields' => $fields,
                ];
            }

            if (empty($newBlocks)) {
                $this->stdout("  No new entries found.\n", Console::FG_CYAN);
                continue;
            }

            $this->stdout("  Adding " . count($newBlocks) . " new entries.\n", Console::FG_GREEN);

            // Merge and save
            $history = $entry->history->getRawBlocks();
            $history = array_merge($history, $newBlocks);
            $entry->setFieldValue('history', $history);

            if (!Craft::$app->elements->saveElement($entry)) {
                $this->stderr("  Failed to save entry: " . implode(', ', $entry->getErrorSummary(true)) . "\n", Console::FG_RED);
            } else {
                $this->stdout("  Entry updated successfully.\n", Console::FG_GREEN);
            }
        }

        $this->stdout("Feed update complete.\n", Console::FG_GREEN);
        return ExitCode::OK;
    }
}
