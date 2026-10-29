<?php

namespace model;

use Gossi\Propel\Behavior\L10n\PropelL10n;
use Map\ProductTableMap;
use PHPUnit\Framework\TestCase;
use Product;
use ProductI18nQuery;
use ProductQuery;
use Gossi\Propel\Behavior\L10n\Tests\SchemaBuilder;
use Propel\Runtime\Exception\PropelException;

/**
 * # ProductTest
 */
class ProductTest extends TestCase
{
    /**
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        if (!class_exists(Product::class)) {
            $schema = <<<XML
<database name="l10n_behavior">
	<table name="product">
		<column name="id" required="true" primaryKey="true" autoIncrement="true" type="integer" />
		<column name="title" type="varchar" required="true" />
		<column name="published_at" type="date" />
		
		<behavior name="l10n">
            <parameter name="locale_length" value="35" />
			<parameter name="i18n_columns" value="title,published_at" />
			<parameter name="locale_alias" value="language" />
			<parameter name="use_locale_chain" value="true" />
		</behavior>
	</table>
</database>
XML;

            SchemaBuilder::buildSchema($schema);
        }

        PropelL10n::setLocale('en'); // just reset, may be changed in other tests
        PropelL10n::setFallback('en');
        PropelL10n::setDependencies([
            'de-CH' => 'de-DE',
            'de-AT' => 'de-DE',
            'de-DE' => 'en-US',
            'ja' => 'en-US'
        ]);
    }

    /**
     * @return void
     * @throws PropelException
     */
    public function testDefaultLocale(): void
    {
        $p = new Product();

        self::assertNull($p->getLocale());

        $p->setTitle('delicious');
        self::assertEquals('delicious', $p->getTitle());
        self::assertEquals('en', $p->getCurrentTranslation()->getLocale());
        self::assertNull($p->getLocale());
    }

    public function testTranslationAccessPreservesCurrentLocale(): void
    {
        foreach ([null, 'de'] as $locale) {
            $p = new Product();
            if ($locale !== null) {
                $p->setLocale($locale);
            }

            $translation = $p->getTranslation('it');

            self::assertSame('it', $translation->getLocale());
            self::assertSame($locale, $p->getLocale());
            self::assertSame($translation, $p->getTranslation('it'));
            self::assertSame($translation, $p->getCurrentTranslation('it'));
            self::assertSame($locale, $p->getLocale());
        }
    }

    public function testPersistedTranslationAccessPreservesCurrentLocale(): void
    {
        $p = new Product();
        $p->setTitle('delicious', 'en');
        $p->setTitle('bene', 'it');
        $p->save();
        $id = $p->getPrimaryKey();

        foreach ([null, 'de'] as $locale) {
            ProductTableMap::clearInstancePool();
            $p = ProductQuery::create()->findPk($id);
            self::assertNotNull($p);
            if ($locale !== null) {
                $p->setLocale($locale);
            }

            self::assertSame('bene', $p->getTranslation('it')->getTitle());
            self::assertSame($locale, $p->getLocale());
            self::assertSame('fr', $p->getTranslation('fr')->getLocale());
            self::assertSame($locale, $p->getLocale());
        }
    }

    public function testTranslationWithoutLocaleUsesCurrentOrGlobalLocale(): void
    {
        $p = new Product();

        self::assertSame('en', $p->getTranslation()->getLocale());
        self::assertSame('en', $p->getCurrentTranslation()->getLocale());
        self::assertNull($p->getLocale());

        $p->setLocale('de');

        self::assertSame('de', $p->getTranslation()->getLocale());
        self::assertSame('de', $p->getCurrentTranslation()->getLocale());
        self::assertSame('de', $p->getLocale());
    }

    public function testFallbackAccessPreservesCurrentLocale(): void
    {
        $p = new Product();
        $p->setTitle('delicious', 'en');
        $p->setLocale('it');

        self::assertSame('delicious', $p->getTitle());
        self::assertSame('it', $p->getLocale());
        self::assertSame('delicious', $p->getTitle('fr'));
        self::assertSame('it', $p->getLocale());

        $p->setTitle('bene');

        self::assertSame('bene', $p->getTranslation('it')->getTitle());
        self::assertSame('delicious', $p->getTranslation('en')->getTitle());
    }

    /**
     * @return void
     * @throws PropelException
     */
    public function testDependency(): void
    {
        $p = new Product();
        $p->setLocale('de-DE');
        $p->setTitle('lecker');

        self::assertEquals('lecker', $p->getTitle('de-CH'));
    }

    /**
     * @return void
     * @throws PropelException
     */
    public function testPrimaryLanguage(): void
    {
        $p = new Product();
        $p->setLocale('ja');
        $p->setTitle('おいしい');

        self::assertEquals('おいしい', $p->getTitle('ja-JP'));
    }

    /**
     * @return void
     * @throws PropelException
     */
    public function testFallback(): void
    {
        $p = new Product();
        $p->setLocale('en');
        $p->setTitle('delicious');
        $p->setLocale('de');
        $p->setTitle('lecker');

        self::assertEquals('delicious', $p->getTitle('it'));
    }

    /**
     * @return void
     */
    public function testGetterWithoutLocaleChain(): void
    {
        $p = new Product();
        $p->setTitle('delicious', 'en');
        $p->setTitle('lecker', 'de-DE');

        self::assertSame('lecker', $p->getTitle('de-DE', false));
        self::assertSame('lecker', $p->getTitle('de-CH', true));
        self::assertNull($p->getTitle('de-CH', false));
        self::assertSame('lecker', $p->getTitle('de-DE-1996'));
        self::assertNull($p->getTitle('de-DE-1996', false));
        self::assertSame('delicious', $p->getTitle('it'));
        self::assertNull($p->getTitle('it', false));
        self::assertNull($p->getLocale());
    }

    /**
     * @return void
     */
    public function testGetterWithoutLocaleChainResolvesCurrentOrGlobalLocale(): void
    {
        $p = new Product();
        $p->setTitle('delicious', 'en');

        self::assertSame('delicious', $p->getTitle(null, false));

        $p->setLocale('it');

        self::assertSame('delicious', $p->getTitle());
        self::assertNull($p->getTitle(null, false));
        self::assertSame('it', $p->getLocale());

        $p->setTitle('bene');

        self::assertSame('bene', $p->getTitle(null, false));
    }

    /**
     * @return void
     */
    public function testDateGetterWithoutLocaleChain(): void
    {
        $p = new Product();
        $p->setPublishedAt('2024-02-03', 'en');

        self::assertSame('2024-02-03', $p->getPublishedAt('Y-m-d', 'en', false));
        self::assertSame('2024-02-03', $p->getPublishedAt('Y-m-d', 'it'));
        self::assertNull($p->getPublishedAt('Y-m-d', 'it', false));
    }

    /**
     * @return void
     * @throws PropelException
     */
    public function testSetterLocale(): void
    {
        $p = new Product();
        $p->setTitle('delicious', 'en');
        $p->setTitle('bene', 'it');

        self::assertNull($p->getLocale());
        self::assertEquals('delicious', $p->getTitle('en-US'));
        self::assertEquals('bene', $p->getTitle('it-IT'));
        self::assertNull($p->getLocale());
    }

    /**
     * @return void
     * @throws PropelException
     */
    public function testLocaleTagChain(): void
    {
        PropelL10n::addDependency('it-IT', 'en');
        $p = new Product();
        $p->setTitle('bene', 'it');
        $p->setTitle('good', 'en');

        self::assertEquals('bene', $p->getTitle('it-IT'));
    }

    /**
     * @return void
     */
    public function testDate(): void
    {
        $p = new Product();
        $p->setPublishedAt('2024-02-03', 'de');

        self::assertSame('2024-02-03', $p->getPublishedAt('Y-m-d', 'de'));
    }

    /**
     * @return void
     */
    public function testLocaleAlias(): void
    {
        $p = new Product();
        $p->setLanguage('de');

        self::assertSame('de', $p->getLanguage());
        self::assertSame('de', $p->getLocale());

        $query = ProductQuery::create()->setLanguage('it');

        self::assertSame('it', $query->getLanguage());
    }

    /**
     * @return void
     */
    public function testRemoveTranslation(): void
    {
        $p = new Product();
        $p->setTitle('lecker', 'de');
        $p->save();
        $p->removeTranslation('de');

        self::assertNull($p->getTitle('de'));
    }

    /**
     * @return void
     */
    public function testRemoveTranslationCurrentOrGlobalLocale(): void
    {
        foreach ([null, 'de'] as $locale) {
            $p = new Product();
            $p->setTitle('delicious', 'en');
            $p->setTitle('lecker', 'de');
            $p->save();
            $id = $p->getPrimaryKey();
            ProductTableMap::clearInstancePool();
            $p = ProductQuery::create()->findPk($id);
            self::assertNotNull($p);
            if ($locale !== null) {
                $p->setLocale($locale);
            }

            $removedLocale = $locale ?? 'en';
            $translation = $p->getTranslation($removedLocale);
            $p->removeTranslation();

            self::assertSame($locale, $p->getLocale());
            self::assertNull(ProductI18nQuery::create()->findPk([$p->getPrimaryKey(), $removedLocale]));
            self::assertNotSame($translation, $p->getTranslation($removedLocale));
            self::assertNull($p->getTranslation($removedLocale)->getTitle());
            self::assertSame(
                ($removedLocale === 'en') ? 'lecker' : 'delicious',
                $p->getTranslation(($removedLocale === 'en') ? 'de' : 'en')->getTitle()
            );
        }
    }

    /**
     * @return void
     * @throws PropelException
     */
    protected function setUp(): void
    {
        ProductQuery::create()->deleteAll();
        ProductI18nQuery::create()->deleteAll();
    }
}
