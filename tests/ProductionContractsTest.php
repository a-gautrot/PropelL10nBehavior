<?php

use Gautrot\Propel\Behavior\L10n\L10nBehavior;
use Gautrot\Propel\Behavior\L10n\L10nBehaviorObjectBuilderModifier;
use Gautrot\Propel\Behavior\L10n\L10nBehaviorQueryBuilderModifier;
use Gautrot\Propel\Behavior\L10n\PropelL10n;
use PHPUnit\Framework\TestCase;
use Propel\Generator\Behavior\I18n\I18nBehavior;
use Propel\Generator\Model\Table;

/**
 * # ProductionContractsTest
 */
final class ProductionContractsTest extends TestCase
{
    /**
     * @return void
     */
    public function testAbsentLocaleColumn(): void
    {
        $behavior = new class extends L10nBehavior {
            public function __construct()
            {
                parent::__construct();
                $this->table = new Table('incomplete');
                $this->table->setPhpName('Incomplete');
                $this->i18nTable = new Table('incomplete_translation');
            }
        };

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/The localization locale column has not been initialized\./');
        $behavior->getLocaleColumn();
    }

    /**
     * @return void
     */
    public function testAbsentForeignKey(): void
    {
        $behavior = new class extends L10nBehavior {
            public function __construct()
            {
                parent::__construct();
                $this->i18nTable = new Table('incomplete_translation');
            }
        };

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/The localization foreign key has not been initialized\./');
        $behavior->getI18nForeignKey();
    }

    /**
     * @return void
     */
    public function testMissingObjectBuilder(): void
    {
        $modifier = new class(new L10nBehavior()) extends L10nBehaviorObjectBuilderModifier {
            public function renderSetter(): string
            {
                return $this->addSetLocale();
            }
        };

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/The localization ObjectBuilder has not been initialized\./');
        $modifier->renderSetter();
    }

    /**
     * @return void
     */
    public function testMissingQueryBuilder(): void
    {
        $modifier = new class(new L10nBehavior()) extends L10nBehaviorQueryBuilderModifier {
            public function renderSetter(): string
            {
                return $this->addSetLocale();
            }
        };

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/The localization QueryBuilder has not been initialized\./');
        $modifier->renderSetter();
    }

    /**
     * @return void
     */
    public function testWrongBehavior(): void
    {
        $modifier = new class(new I18nBehavior()) extends L10nBehaviorQueryBuilderModifier {
            public function renderAttributes(): string
            {
                return $this->objectAttributes();
            }
        };

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/Localization generation requires an L10n behavior\./');
        $modifier->renderAttributes();
    }

    /**
     * @return void
     */
    public function testTemplateDirectoryRestored(): void
    {
        $behavior = new class extends L10nBehavior {
            public function __construct()
            {
                parent::__construct();
                $this->dirname = 'custom-templates/';
            }

            public function templateDirectory(): string
            {
                return $this->getDirname();
            }
        };
        $parameters = $behavior->getParameters();
        $parameters['locale_alias'] = 'Culture';
        $behavior->setParameters($parameters);
        $modifier = new class($behavior) extends L10nBehaviorQueryBuilderModifier {
            public function renderMissingTemplate(): string
            {
                return $this->renderTemplate('missing-template');
            }
        };

        try {
            $modifier->renderMissingTemplate();
            self::fail('A missing template must be rejected.');
        } catch (InvalidArgumentException $exception) {
            self::assertStringContainsString('missing-template', $exception->getMessage());
            self::assertSame('custom-templates/', $behavior->templateDirectory());
        }
    }

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
