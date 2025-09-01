<?php

declare(strict_types=1);

namespace SavinMikhail\EnforceAaaPatternRector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Stmt;
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
        return new RuleDefinition(
            description: 'Enforce AAA (Arrange-Act-Assert) pattern in PHPUnit test methods',
            codeSamples: [
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
                                // Arrange
                                $date = new DateTimeImmutable('2025-01-01');
                                // Act
                                $formatted = $date->format('Y-m-d');
                                // Assert
                                $this->assertEquals('2025-01-01', $formatted);
                            }
                        }
                        PHP_WRAP,
                ),
            ],
        );
    }

    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }

    private function isTestMethod(ClassMethod $classMethod): bool
    {
        $name = $this->getName(node: $classMethod);
        if ($name !== null && str_starts_with(haystack: $name, needle: 'test')) {
            return true;
        }

        $docComment = $classMethod->getDocComment();
        if ($docComment !== null && str_contains(haystack: strtolower(string: $docComment->getText()), needle: '@test')) {
            return true;
        }

        return false;
    }

    private function removeAaaComments(Node $stmt): void
    {
        $comments = $stmt->getComments();
        $filteredComments = [];

        foreach ($comments as $comment) {
            $text = strtolower(string: trim(string: $comment->getText()));
            // Remove existing AAA comments
            if (!str_contains(haystack: $text, needle: 'arrange')
                && !str_contains(haystack: $text, needle: 'act')
                && !str_contains(haystack: $text, needle: 'assert')) {
                $filteredComments[] = $comment;
            }
        }

        $stmt->setAttribute('comments', $filteredComments);
    }

    private function addAaaComment(Node $stmt, string $aaaComment): void
    {
        $existing = $stmt->getComments();
        $aaa = new Comment(text: '// ' . $aaaComment);
        $stmt->setAttribute('comments', array_merge([$aaa], $existing));
    }

    /**
     * @param Stmt[] $stmts
     */
    private function findFirstAssert(array $stmts): ?int
    {
        foreach ($stmts as $i => $stmt) {
            if (! $stmt instanceof Expression) {
                continue;
            }

            $expr = $stmt->expr;

            // $this->assert*
            if ($expr instanceof MethodCall
                && $expr->var instanceof Node\Expr\Variable
                && $this->isName(node: $expr->var, name: 'this')
            ) {
                $methodName = $this->getName(node: $expr->name);
                if ($methodName !== null && str_starts_with(haystack: $methodName, needle: 'assert')) {
                    return $i;
                }
            }

            // self::assert*
            if ($expr instanceof StaticCall
                && $expr->class instanceof Node\Name
                && $this->isName(node: $expr->class, name: 'self')
            ) {
                $methodName = $this->getName(node: $expr->name);
                if ($methodName !== null && str_starts_with(haystack: $methodName, needle: 'assert')) {
                    return $i;
                }
            }
        }

        return null;
    }

    /**
     * @param Stmt[] $stmts
     */
    private function findLastNonAssert(array $stmts, int $firstAssertIndex): int
    {
        // Find the last statement before the first assert that is not an assert statement
        for ($i = $firstAssertIndex - 1; $i >= 0; --$i) {
            if (!$this->isAssertStatement(stmt: $stmts[$i])) {
                return $i;
            }
        }

        return $firstAssertIndex - 1; // fallback
    }

    private function isAssertStatement(Stmt $stmt): bool
    {
        if (!$stmt instanceof Expression) {
            return false;
        }

        $expr = $stmt->expr;

        // $this->assert*
        if ($expr instanceof MethodCall
            && $expr->var instanceof Node\Expr\Variable
            && $this->isName(node: $expr->var, name: 'this')
        ) {
            $methodName = $this->getName(node: $expr->name);
            if ($methodName !== null && str_starts_with(haystack: $methodName, needle: 'assert')) {
                return true;
            }
        }

        // self::assert*
        if ($expr instanceof StaticCall
            && $expr->class instanceof Node\Name
            && $this->isName(node: $expr->class, name: 'self')
        ) {
            $methodName = $this->getName(node: $expr->name);
            if ($methodName !== null && str_starts_with(haystack: $methodName, needle: 'assert')) {
                return true;
            }
        }

        return false;
    }

    public function refactor(Node $node): ?Node
    {
        if (! $node instanceof ClassMethod) {
            return null;
        }

        if (! $this->isTestMethod(classMethod: $node)) {
            return null;
        }

        if ($node->stmts === null || $node->stmts === []) {
            return null;
        }

        $stmts = $node->stmts;

        $firstAssertIndex = $this->findFirstAssert(stmts: $stmts);

        if ($firstAssertIndex === null) {
            return null;
        }

        // Remove all existing AAA comments first
        foreach ($stmts as $stmt) {
            $this->removeAaaComments(stmt: $stmt);
        }

        $changed = true;

        // Handle simple case: only one statement before assert (treat as Act only)
        if ($firstAssertIndex === 1) {
            $this->addAaaComment(stmt: $stmts[0], aaaComment: 'Act');
        } else {
            // Multiple statements before assert
            // First statement gets "Arrange"
            if (isset($stmts[0])) {
                $this->addAaaComment(stmt: $stmts[0], aaaComment: 'Arrange');
            }

            // Last non-assert statement before first assert gets "Act"
            $lastActIndex = $this->findLastNonAssert(stmts: $stmts, firstAssertIndex: $firstAssertIndex);
            if ($lastActIndex >= 0 && $lastActIndex !== 0 && isset($stmts[$lastActIndex])) {
                $this->addAaaComment(stmt: $stmts[$lastActIndex], aaaComment: 'Act');
            }
        }

        // First assert gets "Assert"
        if (isset($stmts[$firstAssertIndex])) {
            $this->addAaaComment(stmt: $stmts[$firstAssertIndex], aaaComment: 'Assert');
        }

        return $changed ? $node : null;
    }
}
