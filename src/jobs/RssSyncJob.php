<?php

namespace simplicateca\burtonsolo\jobs;

use Craft;
use craft\queue\BaseJob;

class RssSyncJob extends BaseJob
{
    public function execute($queue): void
    {
        Craft::info('Running RSS sync job...', __METHOD__);

        //Craft::$app->getPlugin('burtonsolo')->rssSync->sync();
    }

    protected function defaultDescription(): string
    {
        return 'Sync RSS feed into News Feed entry';
    }
}
