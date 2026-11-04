<?php

use Gautrot\Propel\Behavior\L10n\L10nBehavior;
use Gautrot\Propel\Behavior\L10n\PropelL10n;
use PHPUnit\Framework\TestCase;

/**
 * # ProductionContractsTest
 */
final class ProductionContractsTest extends TestCase
{
    /**
     * @return void
     */
    public function testAliasType(): void
    {
        $behavior = new L10nBehavior();
        $parameters = $behavior->getParameters();
        $parameters['locale_alias'] = ['invalid'];
        $behavior->setParameters($parameters);

        $this->expectException(InvalidArgumentException::class);
        $behavior->getLocaleAlias();
    }

    /**
     * @return void
     */
    public function testMissingLocaleColumn(): void
    {
        $this->expectException(LogicException::class);
        (new L10nBehavior())->getLocaleColumn();
    }

    /**
     * @return void
     */
    public function testMissingForeignKey(): void
    {
        $this->expectException(LogicException::class);
        (new L10nBehavior())->getI18nForeignKey();
    }

    /**
     * @return void
     */
    public function testInvalidLocale(): void
    {
        $before = PropelL10n::getLocale();
        try {
            PropelL10n::setLocale(str_repeat('a', INTL_MAX_LOCALE_LEN + 1));
            self::fail('An oversized locale must be rejected.');
        } catch (InvalidArgumentException $exception) {
            self::assertSame($before, PropelL10n::getLocale());
        }
    }
}
