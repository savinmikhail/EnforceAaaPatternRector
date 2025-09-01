<?php

final class StaticAssertsTest extends PHPUnit\Framework\TestCase
{
    public function testFoo(): void
    {
        $date = new DateTimeImmutable('2025-01-01');
        $formatted = $date->format('Y-m-d');
        self::assertEquals('2025-01-01', $formatted);
    }
}
?>

-----
<?php

final class StaticAssertsTest extends PHPUnit\Framework\TestCase
{
    public function testFoo(): void
    {
        // Arrange
        $date = new DateTimeImmutable('2025-01-01');
        // Act
        $formatted = $date->format('Y-m-d');
        // Assert
        self::assertEquals('2025-01-01', $formatted);
    }
}
?>

