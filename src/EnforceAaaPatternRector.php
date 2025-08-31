<?php

declare(strict_types=1);

namespace SavinMikhail\EnforceAaaPatternRector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\Exception\PoorDocumentationException;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class EnforceAaaPatternRector extends AbstractRector
{
    /**
     * @throws PoorDocumentationException
     */
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Convert all arguments to named arguments', codeSamples: [
            new CodeSample(
                badCode: <<<'PHP_WRAP'
                    final class FooTest extends PHPUnit\Framework\TestCase
                    {
                        public function testFoo(): void
                        {
                            $date = new DateTimeImmutable('2025-01-01');
                            $formatted = $date->format('Y-m-d');
                            $this->assertEquals('2025-01-01', $formatted);
                        }
                    }
                    PHP_WRAP,
                goodCode: <<<'PHP_WRAP'
                    final class FooTest extends PHPUnit\Framework\TestCase
                    {
                        public function testFoo(): void
                        {
                            // arrange
                            $date = new DateTimeImmutable('2025-01-01');
                            // act
                            $formatted = $date->format('Y-m-d');
                            // assert
                            $this->assertEquals('2025-01-01', $formatted);
                        }
                    }
                    PHP_WRAP,
            ),
        ]);
    }

    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }

    public function refactor(Node $node)
    {
        if (! $node instanceof ClassMethod) {
            return null;
        }

        if ($node->stmts === null) {
            return null;
        }

        $stmts = $node->stmts;

        $assertIndex = null;

        foreach ($stmts as $i => $stmt) {
            if (! $stmt instanceof Expression) {
                continue;
            }

            $expr = $stmt->expr;
            if ($expr instanceof MethodCall
                && $expr->var instanceof Node\Expr\Variable
                && $this->isName(node: $expr->var, name: 'this')
            ) {
                $methodName = $this->getName(node: $expr->name);
                if ($methodName !== null && str_starts_with(haystack: $methodName, needle: 'assert')) {
                    $assertIndex = $i;
                    break;
                }
            }

        }

        if ($assertIndex === null) {
            return null; // no assert in this method
        }

        // arrange: first statement
        if (isset($stmts[0])) {
            $stmts[0]->setAttribute(key: 'comments', value: [new Comment(text: '// Arrange')]);
        }

        // act: the statement right before the assert part
        $actIndex = $assertIndex - 1;
        if ($actIndex >= 0 && isset($stmts[$actIndex])) {
            $stmts[$actIndex]->setAttribute(key: 'comments', value: [new Comment(text: '// Act')]);
        }

        // assert: the first assert
        $stmts[$assertIndex]->setAttribute(key: 'comments', value: [new Comment(text: '// Assert')]);

        $node->stmts = $stmts;

        return $node;
    }
}
