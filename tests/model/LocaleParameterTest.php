<?php

namespace model;

use CultureAliasArticle;
use CultureAliasArticleQuery;
use Gossi\Propel\Behavior\L10n\PropelL10n;
use PHPUnit\Framework\TestCase;
use Gossi\Propel\Behavior\L10n\Tests\SchemaBuilder;
use ReflectionException;
use ReflectionMethod;

/**
 * # LocaleParameterTest
 */
class LocaleParameterTest extends TestCase
{
    /**
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        $schema = <<<XML
<database name="l10n_locale_parameters">
    <table name="default_locale_article">
        <column name="id" primaryKey="true" autoIncrement="true" type="integer" />
        <column name="title" type="varchar" />
        <column name="published_at" type="date" />
        <behavior name="l10n">
            <parameter name="locale_length" value="35" />
            <parameter name="i18n_columns" value="title,published_at" />
        </behavior>
    </table>
    <table name="culture_alias_article">
        <column name="id" primaryKey="true" autoIncrement="true" type="integer" />
        <column name="title" type="varchar" />
        <column name="published_at" type="date" />
        <behavior name="l10n">
            <parameter name="locale_length" value="35" />
            <parameter name="i18n_columns" value="title,published_at" />
            <parameter name="locale_alias" value="Culture" />
            <parameter name="use_locale_chain" value="false" />
        </behavior>
    </table>
    <table name="culture_column_article">
        <column name="id" primaryKey="true" autoIncrement="true" type="integer" />
        <column name="title" type="varchar" />
        <column name="published_at" type="date" />
        <behavior name="l10n">
            <parameter name="locale_length" value="35" />
            <parameter name="i18n_columns" value="title,published_at" />
            <parameter name="locale_column" value="culture" />
            <parameter name="use_locale_chain" value="true" />
        </behavior>
    </table>
</database>
XML;

        SchemaBuilder::buildSchema($schema);
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function testObjectParameterNamesFollowAliasOrColumn(): void
    {
        $models = [
            'DefaultLocaleArticle' => 'locale',
            'CultureAliasArticle' => 'culture',
            'CultureColumnArticle' => 'culture',
        ];
        $methods = [
            'resolveLocale' => 0,
            'getTranslation' => 0,
            'getCurrentTranslation' => 0,
            'removeTranslation' => 0,
            'getTitle' => 0,
            'setTitle' => 1,
            'getPublishedAt' => 1,
            'setPublishedAt' => 1,
        ];

        foreach ($models as $className => $expectedParameter) {
            foreach ($methods as $methodName => $parameterIndex) {
                $this->assertLocaleParameter($className, $methodName, $parameterIndex, $expectedParameter);
            }

            $setterName = $className === 'CultureColumnArticle' ? 'setCulture' : 'setLocale';
            $this->assertLocaleParameter($className, $setterName, 0, $expectedParameter);
        }

        $this->assertLocaleParameter('CultureAliasArticle', 'setCulture', 0, 'culture');
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function testLocaleChainDefault(): void
    {
        PropelL10n::setFallback('en');
        $models = [
            'DefaultLocaleArticle' => false,
            'CultureAliasArticle' => false,
            'CultureColumnArticle' => true,
        ];

        foreach ($models as $className => $useLocaleChain) {
            $titleGetter = new ReflectionMethod($className, 'getTitle');
            $dateGetter = new ReflectionMethod($className, 'getPublishedAt');

            self::assertSame($useLocaleChain, $titleGetter->getParameters()[1]->getDefaultValue());
            self::assertSame($useLocaleChain, $dateGetter->getParameters()[2]->getDefaultValue());

            $article = new $className();
            $article->setTitle('Hello', 'en');
            $article->setPublishedAt('2024-02-03', 'en');

            self::assertSame($useLocaleChain ? 'Hello' : null, $article->getTitle('it'));
            self::assertSame('Hello', $article->getTitle('it', true));
            self::assertNull($article->getTitle('it', false));
            self::assertSame(
                $useLocaleChain ? '2024-02-03' : null,
                $article->getPublishedAt('Y-m-d', 'it')
            );
            self::assertSame('2024-02-03', $article->getPublishedAt('Y-m-d', 'it', true));
            self::assertNull($article->getPublishedAt('Y-m-d', 'it', false));
        }
    }

    /**
     * @param string $className
     * @param string $methodName
     * @param int $parameterIndex
     * @param string $expectedParameter
     * @return void
     * @throws ReflectionException
     */
    private function assertLocaleParameter(string $className, string $methodName, int $parameterIndex, string $expectedParameter): void
    {
        $method = new ReflectionMethod($className, $methodName);
        $parameter = $method->getParameters()[$parameterIndex];
        $context = $className . '::' . $methodName;

        self::assertSame($expectedParameter, $parameter->getName(), $context);
        self::assertStringContainsString('$' . $expectedParameter, $method->getDocComment(), $context);
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function testQueryParameterNamesFollowAliasOrColumn(): void
    {
        $queries = [
            'DefaultLocaleArticleQuery' => 'locale',
            'CultureAliasArticleQuery' => 'culture',
            'CultureColumnArticleQuery' => 'culture',
        ];
        $methods = [
            'resolveLocale' => 0,
            'joinI18n' => 0,
            'joinWithI18n' => 0,
            'useI18nQuery' => 0,
            'filterByTitle' => 2,
            'findByTitle' => 2,
            'findOneByTitle' => 2,
        ];

        foreach ($queries as $className => $expectedParameter) {
            foreach ($methods as $methodName => $parameterIndex) {
                $this->assertLocaleParameter($className, $methodName, $parameterIndex, $expectedParameter);
            }

            $setterName = $className === 'CultureColumnArticleQuery' ? 'setCulture' : 'setLocale';
            $this->assertLocaleParameter($className, $setterName, 0, $expectedParameter);
        }

        $this->assertLocaleParameter('CultureAliasArticleQuery', 'setCulture', 0, 'culture');
    }

    /**
     * @return void
     */
    public function testCultureAliasTranslationAndQueries(): void
    {
        PropelL10n::setLocale('en');
        PropelL10n::setFallback('en');
        $article = new CultureAliasArticle();
        $article->setCulture('fr');
        $article->setTitle('Bonjour');
        $article->setTitle('Hello', 'en');
        $article->setPublishedAt('2024-02-03', 'fr');

        self::assertSame('fr', $article->getCulture());
        self::assertSame('Bonjour', $article->getTitle());
        self::assertSame('Hello', $article->getTitle('en'));
        self::assertSame('2024-02-03', $article->getPublishedAt('Y-m-d', 'fr'));
        self::assertSame('en', $article->getCurrentTranslation('en')->getLocale());

        $article->save();
        $id = $article->getPrimaryKey();

        $frenchArticle = CultureAliasArticleQuery::create()
            ->findOneByTitle('Bonjour', null, 'fr');
        $englishArticles = CultureAliasArticleQuery::create()
            ->findByTitle('Hello', null, 'en');
        $currentCultureArticle = CultureAliasArticleQuery::create()
            ->setCulture('fr')
            ->findOneByTitle('Bonjour');

        self::assertNotNull($frenchArticle);
        self::assertSame($id, $frenchArticle->getPrimaryKey());
        self::assertCount(1, $englishArticles);
        self::assertNotNull($currentCultureArticle);
        self::assertSame($id, $currentCultureArticle->getPrimaryKey());

        $article->removeTranslation('en');

        self::assertNull($article->getTranslation('en')->getTitle());
    }
}
