<?php

final class MultipleArrangeTest extends PHPUnit\Framework\TestCase
{
    public function testFoo(): void
    {
        $date = new DateTimeImmutable('2025-01-01');
        $date1 = new DateTimeImmutable('2025-01-01');
        $formatted = $date->format('Y-m-d');
        $this->assertEquals('2025-01-01', $formatted);
    }
}
?>

-----
<?php

final class MultipleArrangeTest extends PHPUnit\Framework\TestCase
{
    public function testFoo(): void
    {
        // Arrange
        $date = new DateTimeImmutable('2025-01-01');
        $date1 = new DateTimeImmutable('2025-01-01');
        // Act
        $formatted = $date->format('Y-m-d');
        // Assert
        $this->assertEquals('2025-01-01', $formatted);
    }
}
?>

