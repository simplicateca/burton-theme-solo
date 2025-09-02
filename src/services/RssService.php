<?php

namespace simplicateca\burtonsolo\services;

use Craft;
use craft\base\Component;
use craft\elements\Entry;
use craft\elements\MatrixBlock;
use craft\fields\Matrix;

class RssService extends Component
{
    public function sync(): void
    {
        Craft::info('Starting RSS sync...', __METHOD__);

        // Find the News Feed entry
        $entry = Entry::find()
            ->section('pages')
            ->title('News Feed')
            ->one();

        if (!$entry) {
            Craft::error('News Feed entry not found.', __METHOD__);
            return;
        }

        // Placeholder: Fetch and parse RSS feed here
        $items = [
            [
                'title' => 'Placeholder Hot Take Title',
                'summary' => 'This is an example summary from a feed item.',
            ],
        ];

        /** @var Matrix $field */
        $field = $entry->getFieldValue('records');
        $existingBlocks = $field->all();

        foreach ($items as $item) {
            $block = new MatrixBlock();
            $block->fieldId = Craft::$app->fields->getFieldByHandle('records')->id;
            $block->typeId = Craft::$app->matrix->getBlockTypeByHandle('hotTake')->id;
            $block->ownerId = $entry->id;
            $block->enabled = true;

            $block->setFieldValue('headline', $item['title']);
            $block->setFieldValue('summary', $item['summary']);

            $existingBlocks[] = $block;
        }

        $entry->setFieldValue('records', $existingBlocks);

        if (!Craft::$app->elements->saveElement($entry)) {
            Craft::error('Failed to save entry with new Matrix blocks.', __METHOD__);
        } else {
            Craft::info('RSS sync completed successfully.', __METHOD__);
        }
    }
}
