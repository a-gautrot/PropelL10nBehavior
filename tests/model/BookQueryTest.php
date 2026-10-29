<?php

namespace model;

use Book;
use BookI18nQuery;
use BookQuery;
use Gossi\Propel\Behavior\L10n\PropelL10n;
use Map\BookTableMap;
use PHPUnit\Framework\TestCase;
use Gossi\Propel\Behavior\L10n\Tests\SchemaBuilder;
use Propel\Runtime\Exception\PropelException;

/**
 * # BookQueryTest
 */
class BookQueryTest extends TestCase
{
    /**
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        if (!class_exists(Book::class)) {
            $schema = <<<XML
<database name="l10n_behavior" defaultIdMethod="native">
	<table name="book">
		<column name="id" required="true" primaryKey="true" autoIncrement="true" type="integer" />
		<column name="title" type="varchar" required="true" />
		<column name="author" type="varchar" />
	
		<behavior name="l10n">
            <parameter name="locale_length" value="35" />
			<parameter name="i18n_columns" value="title" />
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
    public function testFilter(): void
    {
        $query = BookQuery::create();
        $query->filterByTitle('Lord of the Rings');
        $book = $query->findOne();

        self::assertNotNull($book);
        self::assertEquals('Herr der Ringe', $book->getTitle('de'));
    }

    /**
     * @return void
     * @throws PropelException
     */
    public function testFind(): void
    {
        $query = BookQuery::create();
        $books = $query->findByTitle('The Hunger Games%');

        self::assertCount(2, $books);
    }

    /**
     * @return void
     * @throws PropelException
     */
    public function testFindOne(): void
    {
        $query = BookQuery::create();
        $book = $query->findOneByTitle('The Hunger Games%');

        self::assertNotNull($book);
        self::assertEquals('Die Tribute von Panem: Tödliche Spiele', $book->getTitle('de'));
    }

    /**
     * @return void
     */
    public function testFilterList(): void
    {
        $books = BookQuery::create()
            ->filterByTitle(['Lord of the Rings', 'The Hunger Games'])
            ->find();

        self::assertCount(2, $books);
    }

    /**
     * @return void
     * @throws PropelException
     */
    public function testLocales(): void
    {
        $query = BookQuery::create();
        $query->setLocale('de');
        $query->filterByTitle('Herr der Ringe');
        $book = $query->findOne();

        self::assertNotNull($book);

        $query = BookQuery::create();
        $query->setLocale('de');
        $query->filterByTitle('Yubiwa Monogatari', null, 'ja-latn-JP');
        $book = $query->findOne();

        self::assertNotNull($book);
    }

    /**
     * @return void
     */
    public function testFilterLocaleResolution(): void
    {
        $scenarios = [
            'global locale' => [null, null, 'en', 'Lord of the Rings'],
            'query locale' => ['de', null, 'de', 'Herr der Ringe'],
            'explicit locale' => ['de', 'en', 'en', 'Lord of the Rings'],
        ];

        foreach ($scenarios as [$currentLocale, $explicitLocale, $expectedLocale, $expectedTitle]) {
            $query = BookQuery::create();

            if ($currentLocale !== null) {
                $query->setLocale($currentLocale);
            }

            $book = $query
                ->filterByTitle($expectedTitle, null, $explicitLocale)
                ->findOne();

            self::assertNotNull($book);
            self::assertSame($expectedTitle, $book->getTitle($expectedLocale));
            self::assertSame($currentLocale, $query->getLocale());
        }
    }

    /**
     * @return void
     */
    public function testJoinI18nLocaleResolution(): void
    {
        $scenarios = [
            'global locale' => [null, null, 'en', 'Lord of the Rings'],
            'query locale' => ['de', null, 'de', 'Herr der Ringe'],
            'explicit locale' => ['de', 'en', 'en', 'Lord of the Rings'],
        ];

        foreach ($scenarios as [$currentLocale, $explicitLocale, $expectedLocale, $expectedTitle]) {
            $query = BookQuery::create();

            if ($currentLocale !== null) {
                $query->setLocale($currentLocale);
            }

            $book = $query
                ->joinI18n($explicitLocale)
                ->where('BookI18n.Title = ?', $expectedTitle)
                ->findOne();

            self::assertNotNull($book);
            self::assertSame($expectedTitle, $book->getTitle($expectedLocale));
            self::assertSame($currentLocale, $query->getLocale());
        }
    }

    /**
     * @return void
     */
    public function testJoinWithI18nLocaleResolution(): void
    {
        $scenarios = [
            'global locale' => [null, null, 'en'],
            'query locale' => ['de', null, 'de'],
            'explicit locale' => ['de', 'en', 'en'],
        ];

        foreach ($scenarios as [$currentLocale, $explicitLocale, $expectedLocale]) {
            BookTableMap::clearInstancePool();
            $query = BookQuery::create();

            if ($currentLocale !== null) {
                $query->setLocale($currentLocale);
            }

            $book = $query
                ->joinWithI18n($explicitLocale)
                ->orderById()
                ->findOne();

            self::assertNotNull($book);
            self::assertCount(1, $book->getBookI18ns());
            self::assertSame($expectedLocale, $book->getBookI18ns()->getFirst()->getLocale());
            self::assertSame($currentLocale, $query->getLocale());
        }
    }

    /**
     * @return void
     */
    public function testUseI18nQueryLocaleResolution(): void
    {
        $scenarios = [
            'global locale' => [null, null, 'en', 'Lord of the Rings'],
            'query locale' => ['de', null, 'de', 'Herr der Ringe'],
            'explicit locale' => ['de', 'en', 'en', 'Lord of the Rings'],
        ];

        foreach ($scenarios as [$currentLocale, $explicitLocale, $expectedLocale, $expectedTitle]) {
            $query = BookQuery::create();

            if ($currentLocale !== null) {
                $query->setLocale($currentLocale);
            }

            $book = $query
                ->useI18nQuery($explicitLocale)
                ->filterByTitle($expectedTitle)
                ->endUse()
                ->findOne();

            self::assertNotNull($book);
            self::assertSame($expectedTitle, $book->getTitle($expectedLocale));
            self::assertSame($currentLocale, $query->getLocale());
        }
    }

    /**
     * @return void
     * @throws PropelException
     */
    protected function setUp(): void
    {
        // reset db contents
        BookQuery::create()->deleteAll();
        BookI18nQuery::create()->deleteAll();

        // fill in some dummy data

        // lord of the rings
        $book = new Book();
        $book->setTitle('Lord of the Rings');
        $book->setTitle('Herr der Ringe', 'de');
        $book->setTitle('Yubiwa Monogatari', 'ja-latn-JP');
        $book->save();

        // hunger games
        $book = new Book();
        $book->setTitle('The Hunger Games');
        $book->setTitle('Die Tribute von Panem: Tödliche Spiele', 'de');
        $book->setTitle('Hangā Gēmu', 'ja-latn-JP');
        $book->save();

        $book = new Book();
        $book->setTitle('The Hunger Games: Catching Fire');
        $book->setTitle('Die Tribute von Panem: Gefährliche Liebe', 'de');
        $book->setTitle('Hangā Gēmu 2', 'ja-latn-JP');
        $book->save();
    }
}
