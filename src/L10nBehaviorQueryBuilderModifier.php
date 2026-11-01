<?php

namespace Gossi\Propel\Behavior\L10n;

use Propel\Generator\Behavior\I18n\I18nBehaviorQueryBuilderModifier;
use Propel\Generator\Builder\Om\QueryBuilder;
use Propel\Generator\Model\Column;

/**
 * # L10nBehaviorQueryBuilderModifier
 */
class L10nBehaviorQueryBuilderModifier extends I18nBehaviorQueryBuilderModifier
{
    use RenderTrait;

    /**
     * @var L10nBehavior
     */
    protected $behavior;

    /**
     * @param QueryBuilder $builder
     *
     * @return string
     */
    public function queryMethods(QueryBuilder $builder): string
    {
        $builder->declareClass(PropelL10n::class);
        $script = parent::queryMethods($builder);
        $script .= $this->objectAttributes();
        $script .= $this->addSetLocale();
        $script .= $this->addGetLocale();

        if ($alias = $this->behavior->getParameter('locale_alias')) {
            $script .= $this->addGetLocaleAlias((string)$alias);
            $script .= $this->addSetLocaleAlias((string)$alias);
        }

        $script .= $this->addResolveLocale();

        foreach ($this->behavior->getI18nColumns() as $column) {
            $script .= $this->addFilter($column);
            $script .= $this->addFind($column);
            $script .= $this->addFindOne($column);
        }

        return $script;
    }

    /**
     * @return string
     */
    protected function objectAttributes(): string
    {
        return $this->renderTemplate('queryAttributes');
    }

    /**
     * @return string
     */
    protected function addSetLocale(): string
    {
        return $this->renderTemplate(
            'objectSetLocale',
            [
                'objectClassName' => $this->builder->getClassNameFromBuilder($this->builder->getStubObjectBuilder()),
                'localeColumnName' => $this->behavior->getLocaleColumn()->getPhpName(),
            ]
        );
    }

    /**
     * @return string
     */
    protected function addGetLocale(): string
    {
        return $this->renderTemplate(
            'objectGetLocale',
            [
                'localeColumnName' => $this->behavior->getLocaleColumn()->getPhpName(),
            ]
        );
    }

    /**
     * @return string
     */
    protected function addResolveLocale(): string
    {
        return $this->renderTemplate(
            'objectResolveLocale',
            [
                'localeColumnName' => $this->behavior->getLocaleColumn()->getPhpName(),
            ]
        );
    }

    /**
     * @param string $alias
     * @return string
     */
    protected function addGetLocaleAlias(string $alias): string
    {
        return $this->renderTemplate(
            'objectGetLocaleAlias',
            [
                'alias' => ucfirst($alias),
                'localeColumnName' => $this->behavior->getLocaleColumn()->getPhpName(),
            ]
        );
    }

    /**
     * @param string $alias
     * @return string
     */
    protected function addSetLocaleAlias(string $alias): string
    {
        return $this->renderTemplate(
            'objectSetLocaleAlias',
            [
                'objectClassName' => $this->builder->getClassNameFromBuilder($this->builder->getStubObjectBuilder()),
                'alias' => ucfirst($alias),
                'localeColumnName' => $this->behavior->getLocaleColumn()->getPhpName(),
            ]
        );
    }

    /**
     * @param Column $column
     * @return string
     */
    protected function addFilter(Column $column): string
    {
        return $this->renderTemplate(
            'queryFilter',
            [
                'queryClass' => $this->builder->getQueryClassName(),
                'columnPhpName' => $column->getPhpName(),
                'columnName' => $column->getName(),
                'localeColumnName' => $this->behavior->getLocaleColumn()->getPhpName(),
            ]
        );
    }

    /**
     * @param Column $column
     * @return string
     */
    protected function addFind(Column $column): string
    {
        return $this->renderTemplate(
            'queryFind',
            [
                'objectClassName' => $this->builder->getClassNameFromBuilder($this->builder->getStubObjectBuilder()),
                'columnPhpName' => $column->getPhpName(),
                'columnName' => $column->getName(),
                'localeColumnName' => $this->behavior->getLocaleColumn()->getPhpName(),
            ]
        );
    }

    /**
     * @param Column $column
     * @return string
     */
    protected function addFindOne(Column $column): string
    {
        return $this->renderTemplate(
            'queryFindOne',
            [
                'objectClassName' => $this->builder->getClassNameFromBuilder($this->builder->getStubObjectBuilder()),
                'columnPhpName' => $column->getPhpName(),
                'columnName' => $column->getName(),
                'localeColumnName' => $this->behavior->getLocaleColumn()->getPhpName(),
            ]
        );
    }

    /**
     * @return string
     */
    protected function addJoinI18n(): string
    {
        $fk = $this->behavior->getI18nForeignKey();

        return $this->renderTemplate(
            'queryJoinI18n',
            [
                'queryClass' => $this->builder->getQueryClassName(),
                'i18nRelationName' => $this->builder->getRefFKPhpNameAffix($fk),
                'localeColumn' => $this->behavior->getLocaleColumn()->getPhpName(),
            ]
        );
    }

    /**
     * @return string
     */
    protected function addJoinWithI18n(): string
    {
        $fk = $this->behavior->getI18nForeignKey();

        return $this->renderTemplate(
            'queryJoinWithI18n',
            [
                'queryClass' => $this->builder->getQueryClassName(),
                'i18nRelationName' => $this->builder->getRefFKPhpNameAffix($fk),
            ]
        );
    }

    /**
     * @return string
     */
    protected function addUseI18nQuery(): string
    {
        $i18nTable = $this->behavior->getI18nTable();
        $fk = $this->behavior->getI18nForeignKey();

        return $this->renderTemplate(
            'queryUseI18nQuery',
            [
                'queryClass' => $this->builder->getClassNameFromBuilder($this->builder->getNewStubQueryBuilder($i18nTable)),
                'namespacedQueryClass' => $this->builder->getNewStubQueryBuilder($i18nTable)->getFullyQualifiedClassName(),
                'i18nRelationName' => $this->builder->getRefFKPhpNameAffix($fk),
                'localeColumn' => $this->behavior->getLocaleColumn()->getPhpName(),
            ]
        );
    }
}
