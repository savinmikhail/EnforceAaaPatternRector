[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/savinmikhail/EnforceAaaPatternRector/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/savinmikhail/EnforceAaaPatternRector/?branch=main)
[![Code Coverage](https://scrutinizer-ci.com/g/savinmikhail/EnforceAaaPatternRector/badges/coverage.png?b=main)](https://scrutinizer-ci.com/g/savinmikhail/EnforceAaaPatternRector/?branch=main)
![dist-size status](https://img.shields.io/endpoint?url=https://raw.githubusercontent.com/savinmikhail/EnforceAaaPatternRector/main/dist-size-status.json)

# EnforceAaaPatternRector

The **EnforceAaaPatternRector** rule helps maintain consistent **Arrange–Act–Assert (AAA) structure** in PHPUnit tests. It automatically annotates your test methods with `// Arrange`, `// Act`, and `// Assert` comments, improving readability and making the test structure explicit.

### Example

```diff
 final class FooTest extends PHPUnit\Framework\TestCase
 {
     public function testFoo(): void
     {
-        $date = new DateTimeImmutable('2025-01-01');
-        $formatted = $date->format('Y-m-d');
-        $this->assertEquals('2025-01-01', $formatted);
+        // Arrange
+        $date = new DateTimeImmutable('2025-01-01');
+        // Act
+        $formatted = $date->format('Y-m-d');
+        // Assert
+        $this->assertEquals('2025-01-01', $formatted);
     }
 }
```

This tool simply add comments and removing misplaced ones. Logic is simple: the line just above the first `Assert` call is commented as `Assert`, the first statement above it considered as `Act` and finally the very first line of the test method declaration commented as `Arrange`. So if the comment seems to you misplaced, consider rearrange your test accordingly. Nevertheless contributing is always welcomed

---

### Installation

You can install the package via Composer:

```bash
composer require --dev savinmikhail/enforce-aaa-pattern-rector
```

---

### Usage

To enable the rule, add it to your Rector configuration (`rector.php`):

```php
<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use SavinMikhail\EnforceAaaPatternRector\EnforceAaaPatternRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(EnforceAaaPatternRector::class);
};
```

___

### Tests as Documentation

The package includes PHPUnit-based Rector tests demonstrating the rule in action. 

Check the `tests/Fixture` directory to see examples of AAA annotation in various scenarios.

---

### Contributing

Contributions, feedback, and suggestions are welcome! Feel free to open issues or submit pull requests.
