<?php

use Gautrot\Propel\Behavior\L10n\QueryValidator;
use PHPUnit\Framework\TestCase;
use Propel\Runtime\ActiveQuery\Criteria;

/**
 * # QueryValidatorTest
 */
final class QueryValidatorTest extends TestCase
{
    /**
     * @return void
     */
    public function testComparisons(): void
    {
        self::assertNull(QueryValidator::comparison(null));
        $operators = [
            Criteria::EQUAL, Criteria::NOT_EQUAL, Criteria::ALT_NOT_EQUAL,
            Criteria::LESS_THAN, Criteria::LESS_EQUAL, Criteria::GREATER_THAN, Criteria::GREATER_EQUAL,
            Criteria::LIKE, Criteria::NOT_LIKE, Criteria::ILIKE, Criteria::NOT_ILIKE,
            Criteria::IN, Criteria::NOT_IN, Criteria::ISNULL, Criteria::ISNOTNULL,
        ];
        foreach ($operators as $operator) {
            self::assertSame($operator, QueryValidator::comparison(' ' . strtolower($operator) . ' '));
        }
    }

    /**
     * @return void
     */
    public function testJoinTypes(): void
    {
        self::assertNull(QueryValidator::joinType(null));
        $joinTypes = [Criteria::LEFT_JOIN, Criteria::RIGHT_JOIN, Criteria::INNER_JOIN];
        foreach ($joinTypes as $joinType) {
            self::assertSame($joinType, QueryValidator::joinType(' ' . strtolower($joinType) . ' '));
        }
    }

    /**
     * @return void
     */
    public function testEmptyComparison(): void
    {
        $this->expectException(InvalidArgumentException::class);
        QueryValidator::comparison('');
    }

    /**
     * @return void
     */
    public function testAliasBoundaries(): void
    {
        $aliases = ['', '0translations', 'translations.name', "translations\n", 'translations; DROP TABLE article'];
        foreach ($aliases as $alias) {
            try {
                QueryValidator::alias($alias);
                self::fail('Invalid SQL alias accepted: ' . $alias);
            } catch (InvalidArgumentException $exception) {
                self::assertSame('A localization join alias must be a SQL identifier.', $exception->getMessage());
            }
        }
        $aliases = [null, 'translations', '_translations2', 't0'];
        foreach ($aliases as $alias) {
            QueryValidator::alias($alias);
        }
    }
}
