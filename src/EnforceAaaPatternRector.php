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

use function array_slice;

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

    private function normalizeAaaComment(Node $stmt, string $expected): bool
    {
        $comments = $stmt->getComments();

        if ($comments === []) {
            $this->prependAaaComment(stmt: $stmt, aaaComment: $expected);

            return true;
        }

        $firstText = strtolower(string: trim(string: $comments[0]->getText()));

        if (str_contains(haystack: $firstText, needle: strtolower(string: $expected))) {
            return false; // already correct
        }

        if (str_contains(haystack: $firstText, needle: 'arrange') || str_contains(haystack: $firstText, needle: 'act') || str_contains(haystack: $firstText, needle: 'assert')) {
            // wrong AAA → replace
            $rest = array_slice(array: $comments, offset: 1);
            $stmt->setAttribute('comments', [new Comment(text: '// ' . $expected), ...$rest]);

            return true;
        }

        // unrelated comment → prepend expected AAA
        $this->prependAaaComment(stmt: $stmt, aaaComment: $expected);

        return true;
    }

    private function prependAaaComment(Node $stmt, string $aaaComment): void
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

        $assertIndex = $this->findFirstAssert(stmts: $stmts);

        if ($assertIndex === null) {
            return null;
        }

        $changed = false;

        // Detect shortcut case: only one stmt before assert
        if ($assertIndex === 1) {
            // treat it as Act
            $changed |= $this->normalizeAaaComment(stmt: $stmts[0], expected: 'Act');
        } else {
            if (isset($stmts[0])) {
                $changed |= $this->normalizeAaaComment(stmt: $stmts[0], expected: 'Arrange');
            }
            $actIndex = $assertIndex - 1;
            if ($actIndex >= 0 && isset($stmts[$actIndex])) {
                $changed |= $this->normalizeAaaComment(stmt: $stmts[$actIndex], expected: 'Act');
            }
        }

        // assert
        if (isset($stmts[$assertIndex])) {
            $changed |= $this->normalizeAaaComment(stmt: $stmts[$assertIndex], expected: 'Assert');
        }

        return $changed ? $node : null;
    }
}
