<?php

use Gautrot\Propel\Behavior\L10n\PropelL10n;
use Gautrot\Propel\Behavior\L10n\Tests\SchemaBuilder;
use PHPUnit\Framework\TestCase;
use Propel\Runtime\ActiveQuery\Criteria;

/**
 * # QuerySecurityTest
 */
final class QuerySecurityTest extends TestCase
{
    /**
     * @return void
     * @throws Exception
     */
    public static function setUpBeforeClass(): void
    {
        SchemaBuilder::buildSchema(<<<'XML'
<database name="l10n_security">
    <table name="security_article">
        <column name="id" primaryKey="true" autoIncrement="true" type="integer" />
        <column name="title" type="varchar" size="255" />
        <behavior name="l10n">
            <parameter name="i18n_columns" value="title" />
            <parameter name="locale_length" value="100" />
        </behavior>
    </table>
</database>
XML
        );
        PropelL10n::setLocale('en');
        (new SecurityArticle())->setTitle('Hello', 'en')->save();
        (new SecurityArticle())->setTitle("x' OR 1=1 --", 'en')->save();
    }

    /**
     * @return void
     */
    public function testScalarOperator(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SecurityArticleQuery::create()->filterByTitle('unmatched', ' = :p1 OR 1=1 OR 1 = ');
    }

    /**
     * @return void
     */
    public function testNullOperator(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SecurityArticleQuery::create()->filterByTitle(null, ' IS NULL OR 1=1 -- ');
    }

    /**
     * @return void
     */
    public function testArrayOperator(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SecurityArticleQuery::create()->filterByTitle(['Hello'], ' = :p1 OR 1=1 OR 1 = ');
    }

    /**
     * @return void
     */
    public function testFindOperator(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SecurityArticleQuery::create()->findByTitle('unmatched', ' = :p1 OR 1=1 OR 1 = ');
    }

    /**
     * @return void
     */
    public function testFindOneOperator(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SecurityArticleQuery::create()->findOneByTitle('unmatched', ' = :p1 OR 1=1 OR 1 = ');
    }

    /**
     * @return void
     */
    public function testJoinType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SecurityArticleQuery::create()->joinI18n('en', null, ' LEFT JOIN injected ON 1=1 -- ');
    }

    /**
     * @return void
     */
    public function testJoinAlias(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SecurityArticleQuery::create()->joinI18n('en', 't ON 1=1 --');
    }

    /**
     * @return void
     */
    public function testQueryJoinType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SecurityArticleQuery::create()->useI18nQuery('en', null, ' LEFT JOIN injected ON 1=1 -- ');
    }

    /**
     * @return void
     */
    public function testEagerJoinType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SecurityArticleQuery::create()->joinWithI18n('en', ' LEFT JOIN injected ON 1=1 -- ');
    }

    /**
     * @return void
     */
    public function testBoundValues(): void
    {
        $payload = "x' OR 1=1 --";
        $query = SecurityArticleQuery::create()->filterByTitle($payload);
        $parameters = [];
        self::assertStringNotContainsString($payload, $query->createSelectSql($parameters));
        self::assertContains($payload, array_column($parameters, 'value'));
        self::assertSame(1, $query->count());
        self::assertSame(1, SecurityArticleQuery::create()->filterByTitle('Hello', Criteria::EQUAL)->count());
        self::assertSame(1, SecurityArticleQuery::create()->filterByTitle(['Hello'])->count());
        self::assertSame(1, SecurityArticleQuery::create()->filterByTitle('Hel%', Criteria::LIKE)->count());
        self::assertSame(0, SecurityArticleQuery::create()->filterByTitle(null, Criteria::ISNULL)->count());
        self::assertSame(2, SecurityArticleQuery::create()->filterByTitle(null, Criteria::ISNOTNULL)->count());
        self::assertSame(2, SecurityArticleQuery::create()->joinI18n('en', 'translations', Criteria::INNER_JOIN)->count());
    }

    /**
     * @return void
     */
    public function testBoundLocale(): void
    {
        $payload = "en' OR 1=1 --";
        $query = SecurityArticleQuery::create()->joinI18n($payload);
        $parameters = [];
        self::assertStringNotContainsString($payload, $query->createSelectSql($parameters));
        self::assertContains($payload, array_column($parameters, 'value'));
    }

    /**
     * @return void
     */
    protected function setUp(): void
    {
        PropelL10n::setLocale('en');
    }
}
