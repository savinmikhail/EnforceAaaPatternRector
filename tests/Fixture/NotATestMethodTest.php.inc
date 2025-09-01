<?php

final class NotATestMethodTest extends PHPUnit\Framework\TestCase
{
    public function foo(): void
    {
        $date = new DateTimeImmutable('2025-01-01');
        $formatted = $date->format('Y-m-d');
        $this->assertEquals('2025-01-01', $formatted);
    }
}
?>

-----
<?php

final class NotATestMethodTest extends PHPUnit\Framework\TestCase
{
    public function foo(): void
    {
        $date = new DateTimeImmutable('2025-01-01');
        $formatted = $date->format('Y-m-d');
        $this->assertEquals('2025-01-01', $formatted);
    }
}
?>

