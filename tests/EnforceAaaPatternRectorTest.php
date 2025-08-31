<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\EnforceAaaPatternRector;

use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;
use SavinMikhail\EnforceAaaPatternRector\EnforceAaaPatternRector;

final class EnforceAaaPatternRectorTest extends AbstractRectorTestCase
{
    #[DataProvider('provideCases')]
    public function test(string $filePath): void
    {
        $this->doTestFile($filePath);
    }

    /**
     * @return Iterator<array<int, string>>
     */
    public static function provideCases(): Iterator
    {
        return self::yieldFilesFromDirectory(__DIR__ . '/Fixture');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/rector.php';
    }

    protected function getRectorClass(): string
    {
        return EnforceAaaPatternRector::class;
    }
}
