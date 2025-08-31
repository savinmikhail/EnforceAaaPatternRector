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

        if ($node->stmts === null || count($node->stmts) === 0) {
            return null;
        }

        $stmts = $node->stmts;
        $assertIndex = null;

        // Find first assert statement
        foreach ($stmts as $i => $stmt) {
            if (! $stmt instanceof Expression) {
                continue;
            }

            $expr = $stmt->expr;
            if ($expr instanceof MethodCall
                && $expr->var instanceof Node\Expr\Variable
                && $this->isName($expr->var, 'this')
            ) {
                $methodName = $this->getName($expr->name);
                if ($methodName !== null && str_starts_with($methodName, 'assert')) {
                    $assertIndex = $i;
                    break;
                }
            }
        }

        if ($assertIndex === null) {
            return null; // no assert found
        }

        $changed = false;

        // Arrange: first statement
        if (isset($stmts[0]) && count($stmts[0]->getComments() ?? []) === 0) {
            $stmts[0]->setAttribute('comments', [new Comment('// Arrange')]);
            $changed = true;
        }

        // Act: statement before first assert
        $actIndex = $assertIndex - 1;
        if ($actIndex >= 0 && isset($stmts[$actIndex]) && count($stmts[$actIndex]->getComments() ?? []) === 0) {
            $stmts[$actIndex]->setAttribute('comments', [new Comment('// Act')]);
            $changed = true;
        }

        // Assert: first assert statement
        if (isset($stmts[$assertIndex]) && count($stmts[$assertIndex]->getComments() ?? []) === 0) {
            $stmts[$assertIndex]->setAttribute('comments', [new Comment('// Assert')]);
            $changed = true;
        }

        if (! $changed) {
            return null; // nothing to modify
        }

        $node->stmts = $stmts;
        return $node;
    }
}
