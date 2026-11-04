<?php

use Gautrot\Propel\Behavior\L10n\PropelL10n;
use PHPUnit\Framework\TestCase;

/**
 * # PropelL10nTest
 */
class PropelL10nTest extends TestCase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        PropelL10n::setLocale('en');
        PropelL10n::setFallback('en');
        PropelL10n::setDependencies([]);
    }

    /**
     * @return void
     */
    public function testAddDependency(): void
    {
        PropelL10n::addDependency('de-DE', 'en-US');

        self::assertTrue(PropelL10n::hasDependency('de-DE'));
        self::assertSame('en-US', PropelL10n::getDependency('de_DE'));
        self::assertNull(PropelL10n::getDependency('it-IT'));
        self::assertEquals(['de-DE' => 'en-US'], PropelL10n::getDependencies());
    }

    /**
     * @return void
     */
    public function testRemoveDependency(): void
    {
        PropelL10n::addDependency('de-DE', 'en-US');
        PropelL10n::removeDependency('de-DE');

        self::assertCount(0, PropelL10n::getDependencies());
    }

    /**
     * @return void
     */
    public function testSetDependencies(): void
    {
        $deps = [
            'de-DE' => 'en-US',
            'de-CH' => 'de-DE',
            'ja-JP' => 'en-US'
        ];

        PropelL10n::setDependencies($deps);

        self::assertEquals(2, PropelL10n::countDependencies('de-CH'));
        self::assertEquals(0, PropelL10n::countDependencies('it-IT'));
        self::assertEquals($deps, PropelL10n::getDependencies());
    }

    /**
     * @return void
     */
    public function testCurrentLocale(): void
    {
        self::assertEquals('en', PropelL10n::getLocale());

        PropelL10n::setLocale('de-DE');
        self::assertEquals('de-DE', PropelL10n::getLocale());
    }

    /**
     * @return void
     */
    public function testNormalize(): void
    {
        self::assertSame('de-DE', PropelL10n::normalize('de_DE'));
    }

    /**
     * @return void
     */
    public function testFallback(): void
    {
        self::assertSame('en', PropelL10n::getFallback());

        PropelL10n::setFallback('fr_FR');

        self::assertSame('fr-FR', PropelL10n::getFallback());
    }

    /**
     * @return void
     */
    public function testLocaleChain(): void
    {
        PropelL10n::setFallback('fr_FR');
        PropelL10n::setDependencies(
            [
                'de-CH' => 'de-DE',
                'de-DE' => 'en-US',
            ]
        );

        self::assertSame('en-US', PropelL10n::getDependency('de-DE'));
        self::assertSame(
            ['de-CH', 'de-DE', 'de', 'en-US', 'en', 'fr-FR'],
            PropelL10n::getLocaleChain('de-CH')
        );
    }

    /**
     * @return void
     */
    public function testLocaleChainCycle(): void
    {
        PropelL10n::setDependencies(
            [
                'de-CH' => 'de-DE',
                'de-DE' => 'de-CH',
            ]
        );

        self::assertSame(
            ['de-CH', 'de-DE', 'de', 'en'],
            PropelL10n::getLocaleChain('de-CH')
        );
        self::assertSame(2, PropelL10n::countDependencies('de-CH'));
    }
}
