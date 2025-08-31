<?php

final class NeededLineCommentedTest extends PHPUnit\Framework\TestCase
{
    public function testFoo(): void
    {
        // here we have some important info
        $date = new DateTimeImmutable('2025-01-01');
        // here we have some important info
        $formatted = $date->format('Y-m-d');
        // here we have some important info
        $this->assertEquals('2025-01-01', $formatted);
    }
}
?>

-----
<?php

final class NeededLineCommentedTest extends PHPUnit\Framework\TestCase
{
    public function testFoo(): void
    {
        // Arrange
        // here we have some important info
        $date = new DateTimeImmutable('2025-01-01');
        // Act
        // here we have some important info
        $formatted = $date->format('Y-m-d');
        // Assert
        // here we have some important info
        $this->assertEquals('2025-01-01', $formatted);
    }
}
?>

