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

    private function hasAaaComment(Node $stmt): bool
    {
        foreach ($stmt->getComments() as $comment) {
            $text = strtolower(string: trim(string: $comment->getText()));
            if (str_contains(haystack: $text, needle: 'arrange') || str_contains(haystack: $text, needle: 'act') || str_contains(haystack: $text, needle: 'assert')) {
                return true;
            }
        }

        return false;
    }

    private function prependAaaComment(Node $stmt, string $aaaComment): void
    {
        $existing = $stmt->getComments();
        $aaa = new Comment(text: '// ' . $aaaComment);

        // если первый коммент уже AAA — не дублируем
        if ($existing !== []) {
            $firstText = strtolower(string: trim(string: $existing[0]->getText()));
            if (str_contains(haystack: $firstText, needle: strtolower(string: $aaaComment))) {
                return;
            }
        }

        $stmt->setAttribute('comments', array_merge([$aaa], $existing));
    }

    /**
     * @param Stmt[] $stmts
     */
    private function findFirstAssert(array $stmts): null|int|string
    {
        $assertIndex = null;

        // ищем первый assert
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

            if ($expr instanceof StaticCall
                && $expr->class instanceof Node\Name
                && $this->isName(node: $expr->class, name: 'self')
            ) {
                $methodName = $this->getName(node: $expr->name);
                if ($methodName !== null && str_starts_with(haystack: $methodName, needle: 'assert')) {
                    $assertIndex = $i;
                    break;
                }
            }
        }


        return $assertIndex;
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

        // arrange
        if (isset($stmts[0]) && ! $this->hasAaaComment(stmt: $stmts[0])) {
            $this->prependAaaComment(stmt: $stmts[0], aaaComment: 'Arrange');
            $changed = true;
        }

        // act
        $actIndex = $assertIndex - 1;
        if ($actIndex >= 0 && isset($stmts[$actIndex]) && ! $this->hasAaaComment(stmt: $stmts[$actIndex])) {
            $this->prependAaaComment(stmt: $stmts[$actIndex], aaaComment: 'Act');
            $changed = true;
        }

        // assert
        if (isset($stmts[$assertIndex]) && ! $this->hasAaaComment(stmt: $stmts[$assertIndex])) {
            $this->prependAaaComment(stmt: $stmts[$assertIndex], aaaComment: 'Assert');
            $changed = true;
        }

        if (! $changed) {
            return null;
        }

        $node->stmts = $stmts;

        return $node;
    }

    private function isTestMethod(ClassMethod $classMethod): bool
    {
        $name = $this->getName(node: $classMethod);
        if ($name !== null && str_starts_with(haystack: $name, needle: 'test')) {
            return true;
        }

        $docComment = $classMethod->getDocComment();

        return $docComment !== null && str_contains(haystack: strtolower(string: $docComment->getText()), needle: '@test');
    }
}
