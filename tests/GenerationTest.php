<?php

namespace Gautrot\Propel\Behavior\L10n\Tests;

use CoverageArticle;
use CoverageArticleQuery;
use Exception;
use Gautrot\Propel\Behavior\L10n\PropelL10n;
use PHPUnit\Framework\TestCase;

/**
 * # GenerationTest
 */
final class GenerationTest extends TestCase
{
    /**
     * @return void
     * @throws Exception
     */
    public function testGeneratedModels(): void
    {
        self::buildModels();
        $locale = PropelL10n::getLocale();
        try {
            PropelL10n::setLocale('en');
            $article = new CoverageArticle();
            $article->setCulture('fr')->setTitle('Bonjour')->setPublishedAt('2024-02-03');
            $article->setTitle('Hello', 'en');
            $article->save();
            $article->setCulture('fr');

            self::assertSame('fr', $article->getCulture());
            self::assertSame('Bonjour', $article->getTitle());
            self::assertSame('Hello', $article->getTitle('en'));
            self::assertSame('2024-02-03', $article->getPublishedAt('Y-m-d', 'fr'));
            self::assertSame('fr', $article->getCurrentTranslation()->getLocale());

            $query = CoverageArticleQuery::create()->setCulture('fr');
            self::assertSame('fr', $query->getCulture());
            $found = $query->findOneByTitle('Bonjour');
            self::assertNotNull($found);
            self::assertSame($article->getPrimaryKey(), $found->getPrimaryKey());
            self::assertCount(1, CoverageArticleQuery::create()->findByTitle('Hello', null, 'en'));
            self::assertCount(1, CoverageArticleQuery::create()->joinWithI18n('fr')->find());

            $article->removeTranslation('en');
            self::assertNull($article->getTitle('en', false));
            $article->clearAllReferences();
            self::assertNull($article->getLocale());
        } finally {
            PropelL10n::setLocale($locale);
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    public static function buildModels(): void
    {
        // Generation belongs inside the test so PHPUnit measures generators and templates.
        SchemaBuilder::buildSchema(<<<'XML'
<database name="l10n_generation">
    <table name="coverage_article">
        <column name="id" primaryKey="true" autoIncrement="true" type="integer" />
        <column name="title" type="varchar" />
        <column name="published_at" type="date" />
        <behavior name="l10n">
            <parameter name="i18n_columns" value="title,published_at" />
            <parameter name="locale_alias" value="Culture" />
            <parameter name="use_locale_chain" value="true" />
        </behavior>
    </table>
</database>
XML
        );
    }
}
