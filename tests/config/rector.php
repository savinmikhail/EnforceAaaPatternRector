<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use SavinMikhail\EnforceAaaPatternRector\EnforceAaaPatternRector;

return RectorConfig::configure()
        ->withRules(rules: [
            EnforceAaaPatternRector::class,
        ]);
