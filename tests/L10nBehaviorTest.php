<?php

namespace Gautrot\Propel\Behavior\L10n\Tests;

use Gautrot\Propel\Behavior\L10n\L10nBehavior;
use Gautrot\Propel\Behavior\L10n\PropelL10n;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * # L10nBehaviorTest
 */
class L10nBehaviorTest extends TestCase
{
    /**
     * @return void
     */
    public function testDefaultLocale(): void
    {
        $locale = PropelL10n::getLocale();
        try {
            PropelL10n::setLocale('fr');
            $behavior = new L10nBehavior();
            $behavior->modifyDatabase();
            self::assertSame('fr', $behavior->getDefaultLocale());
            PropelL10n::setLocale('de');
            self::assertSame('de', $behavior->getDefaultLocale());
        } finally {
            PropelL10n::setLocale($locale);
        }
    }

    /**
     * @return void
     */
    public function testLocaleVariableName(): void
    {
        $behavior = new L10nBehavior();
        $aliases = [
            'Culture' => 'culture',
            'language' => 'language',
            'UserCulture' => 'userCulture'
        ];

        foreach ($aliases as $alias => $name) {
            $parameters = $behavior->getParameters();
            $parameters['locale_alias'] = $alias;
            $behavior->setParameters($parameters);

            self::assertSame($name, $behavior->getLocaleVariableName());
        }
    }

    /**
     * @return void
     */
    public function testInvalidAlias(): void
    {
        $behavior = new L10nBehavior();
        $parameters = $behavior->getParameters();
        $parameters['locale_alias'] = 'culture); /*';
        $behavior->setParameters($parameters);

        $this->expectException(InvalidArgumentException::class);

        $behavior->getLocaleVariableName();
    }
}
