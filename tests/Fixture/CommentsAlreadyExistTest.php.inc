<?php

final class CommentsAlreadyExists extends PHPUnit\Framework\TestCase
{
    public function testFoo(): void
    {
        // Arrange
        $date = new DateTimeImmutable('2025-01-01');
        // Act
        $formatted = $date->format('Y-m-d');
        // Assert
        $this->assertEquals('2025-01-01', $formatted);
    }
}
?>

-----
<?php

final class CommentsAlreadyExists extends PHPUnit\Framework\TestCase
{
    public function testFoo(): void
    {
        // Arrange
        $date = new DateTimeImmutable('2025-01-01');
        // Act
        $formatted = $date->format('Y-m-d');
        // Assert
        $this->assertEquals('2025-01-01', $formatted);
    }
}
?>

