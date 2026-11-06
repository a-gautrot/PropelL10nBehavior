<?php

namespace Gautrot\Propel\Behavior\L10n;

use Override;
use InvalidArgumentException;
use LogicException;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Behavior\I18n\I18nBehavior;
use Propel\Generator\Builder\Om\AbstractOMBuilder;

/**
 * # L10nBehavior
 * @api
 */
class L10nBehavior extends I18nBehavior
{
    /**
     * Default parameters value
     *
     * @var array<string, mixed>
     */
    protected $parameters = [
        'i18n_table' => '%TABLE%_i18n',
        'i18n_phpname' => '%PHPNAME%I18n',
        'i18n_columns' => '',
        'i18n_pk_column' => null,
        'locale_column' => 'locale',
        'locale_length' => 5,
        'locale_alias' => '',
        'use_locale_chain' => 'false',
    ];

    /**
     * @var string|null
     */
    protected ?string $templateDirnameBackup = null;

    /**
     * @var L10nBehaviorObjectBuilderModifier|null
     */
    private ?L10nBehaviorObjectBuilderModifier $l10nObjectBuilderModifier = null;

    /**
     * @var L10nBehaviorQueryBuilderModifier|null
     */
    private ?L10nBehaviorQueryBuilderModifier $l10nQueryBuilderModifier = null;

    /**
     *
     */
    public function __construct()
    {
        $this->dirname = __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     * @return void
     */
    #[Override]
    public function modifyDatabase(): void
    {
    }

    /**
     * @return string
     */
    #[Override]
    public function getDefaultLocale(): string
    {
        return PropelL10n::getLocale();
    }

    /**
     * @return string
     */
    public function getLocaleAlias(): string
    {
        $alias = $this->getParameter('locale_alias');

        if (!is_string($alias)) {
            throw new InvalidArgumentException('The locale alias must be a string.');
        }

        return $alias;
    }

    /**
     * @return Column
     */
    #[Override]
    public function getLocaleColumn(): Column
    {
        if ($this->i18nTable === null) {
            throw new LogicException('The localization table has not been initialized.');
        }

        $column = parent::getLocaleColumn();

        if ($column === null) {
            throw new LogicException('The localization locale column has not been initialized.');
        }

        return $column;
    }

    /**
     * @return ForeignKey
     */
    #[Override]
    public function getI18nForeignKey(): ForeignKey
    {
        if ($this->i18nTable === null) {
            throw new LogicException('The localization table has not been initialized.');
        }

        $foreignKey = parent::getI18nForeignKey();

        if ($foreignKey === null) {
            throw new LogicException('The localization foreign key has not been initialized.');
        }

        return $foreignKey;
    }

    /**
     * @return string
     */
    public function getLocaleVariableName(): string
    {
        $alias = $this->getLocaleAlias();
        $localeVariableName = lcfirst($alias ?: $this->getLocaleColumn()->getPhpName());

        if (!preg_match('/\A[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*\z/', $localeVariableName)) {
            throw new InvalidArgumentException('The locale alias or column PHP name must be a valid PHP identifier.');
        }

        return $localeVariableName;
    }

    /**
     * @return L10nBehaviorObjectBuilderModifier
     */
    #[Override]
    public function getObjectBuilderModifier(): L10nBehaviorObjectBuilderModifier
    {
        $this->l10nObjectBuilderModifier ??= new L10nBehaviorObjectBuilderModifier($this);

        return $this->l10nObjectBuilderModifier;
    }

    /**
     * @return L10nBehaviorQueryBuilderModifier
     */
    #[Override]
    public function getQueryBuilderModifier(): L10nBehaviorQueryBuilderModifier
    {
        $this->l10nQueryBuilderModifier ??= new L10nBehaviorQueryBuilderModifier($this);

        return $this->l10nQueryBuilderModifier;
    }

    /**
     * @param AbstractOMBuilder $builder
     *
     * @return string
     */
    #[Override]
    public function staticAttributes(AbstractOMBuilder $builder): string
    {
        return $this->renderTemplate('staticAttributes');
    }

    /**
     * @return void
     */
    public function backupTemplatesDirname(): void
    {
        $this->templateDirnameBackup = $this->dirname;
        $this->dirname = __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     * @return void
     */
    public function restoreTemplatesDirname(): void
    {
        $this->dirname = (string)$this->templateDirnameBackup;
    }
}
