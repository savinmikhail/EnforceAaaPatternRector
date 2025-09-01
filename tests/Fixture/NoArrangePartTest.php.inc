<?php

final class NoArrangePartTest extends PHPUnit\Framework\TestCase
{
    public function testFoo(): void
    {
        $formatted = (new DateTimeImmutable('2025-01-01'))->format('Y-m-d');
        $this->assertEquals('2025-01-01', $formatted);
    }
}
?>

-----
<?php

final class NoArrangePartTest extends PHPUnit\Framework\TestCase
{
    public function testFoo(): void
    {
        // Act
        $formatted = (new DateTimeImmutable('2025-01-01'))->format('Y-m-d');
        // Assert
        $this->assertEquals('2025-01-01', $formatted);
    }
}
?>

