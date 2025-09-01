<?php

final class DubiousCommentsTest extends PHPUnit\Framework\TestCase
{
    public function testFoo(): void
    {
        // first ACTIVE
        // 2) Страница с историей, но без активного статуса → active_seconds = 0
        // this is some useful comment, must stay here
        $date = new DateTimeImmutable('2025-01-01');
        // arrange
        $date = new DateTimeImmutable('2025-01-01');
        //act
        $formatted = $date->format('Y-m-d');
        $formatted = $date->format('Y-m-d');
        $this->assertEquals('2025-01-01', $formatted);
        // Assert
        $this->assertEquals('2025-01-01', $formatted);
    }
}
?>

-----
<?php

final class DubiousCommentsTest extends PHPUnit\Framework\TestCase
{
    public function testFoo(): void
    {
        // Arrange
        // first ACTIVE
        // 2) Страница с историей, но без активного статуса → active_seconds = 0
        // this is some useful comment, must stay here
        $date = new DateTimeImmutable('2025-01-01');
        $date = new DateTimeImmutable('2025-01-01');
        $formatted = $date->format('Y-m-d');
        // Act
        $formatted = $date->format('Y-m-d');
        // Assert
        $this->assertEquals('2025-01-01', $formatted);
        $this->assertEquals('2025-01-01', $formatted);
    }
}
?>

