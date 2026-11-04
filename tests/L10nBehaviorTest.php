<?php

namespace Gautrot\Propel\Behavior\L10n\Tests;

use Gautrot\Propel\Behavior\L10n\L10nBehavior;
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
