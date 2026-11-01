<?php

namespace Gossi\Propel\Behavior\L10n;

use InvalidArgumentException;
use Propel\Generator\Behavior\I18n\I18nBehavior;
use Propel\Generator\Builder\Om\AbstractOMBuilder;

/**
 * # L10nBehavior
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
    protected $objectBuilderModifier;

    /**
     * @var L10nBehaviorQueryBuilderModifier|null
     */
    protected $queryBuilderModifier;

    public function __construct()
    {
        $this->dirname = __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     * @return void
     */
    public function modifyDatabase(): void
    {
    }

    /**
     * @return string
     */
    public function getDefaultLocale(): string
    {
        return PropelL10n::getLocale();
    }

    /**
     * @return string
     */
    public function getLocaleVariableName(): string
    {
        $alias = $this->getParameter('locale_alias');
        $localeVariableName = lcfirst($alias ?: $this->getLocaleColumn()->getPhpName());

        if (!preg_match('/\A[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*\z/', $localeVariableName)) {
            throw new InvalidArgumentException('The locale alias or column PHP name must be a valid PHP identifier.');
        }

        return $localeVariableName;
    }

    /**
     * @return L10nBehaviorObjectBuilderModifier|null
     */
    public function getObjectBuilderModifier(): ?L10nBehaviorObjectBuilderModifier
    {
        $this->objectBuilderModifier ??= new L10nBehaviorObjectBuilderModifier($this);

        return $this->objectBuilderModifier;
    }

    /**
     * @return L10nBehaviorQueryBuilderModifier|null
     */
    public function getQueryBuilderModifier(): ?L10nBehaviorQueryBuilderModifier
    {
        $this->queryBuilderModifier ??= new L10nBehaviorQueryBuilderModifier($this);

        return $this->queryBuilderModifier;
    }

    /**
     * @param AbstractOMBuilder $builder
     *
     * @return string
     */
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
