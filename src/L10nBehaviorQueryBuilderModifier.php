<?php

namespace Gautrot\Propel\Behavior\L10n;

use LogicException;
use Override;
use Propel\Generator\Behavior\I18n\I18nBehaviorQueryBuilderModifier;
use Propel\Generator\Builder\Om\QueryBuilder;
use Propel\Generator\Model\Column;

/**
 * # L10nBehaviorQueryBuilderModifier
 * @api
 */
class L10nBehaviorQueryBuilderModifier extends I18nBehaviorQueryBuilderModifier
{
    use RenderTrait;

    /**
     * @return QueryBuilder
     */
    protected function getQueryBuilder(): QueryBuilder
    {
        $builder = $this->builder;
        if ($builder === null) {
            throw new LogicException('The localization QueryBuilder has not been initialized.');
        }

        return $builder;
    }



    /**
     * @param QueryBuilder $builder
     *
     * @return string
     */
    #[Override]
    public function queryMethods(QueryBuilder $builder): string
    {
        $builder->declareClass(PropelL10n::class);
        $script = parent::queryMethods($builder);
        $script .= $this->objectAttributes();
        $script .= $this->addSetLocale();
        $script .= $this->addGetLocale();

        if ($alias = $this->getL10nBehavior()->getLocaleAlias()) {
            $script .= $this->addGetLocaleAlias($alias);
            $script .= $this->addSetLocaleAlias($alias);
        }

        $script .= $this->addResolveLocale();

        foreach ($this->getL10nBehavior()->getI18nColumns() as $column) {
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
                'objectClassName' => $this->getQueryBuilder()->getClassNameFromBuilder(
                    $this->getQueryBuilder()->getStubObjectBuilder()
                ),
                'localeColumnName' => $this->getL10nBehavior()->getLocaleColumn()->getPhpName(),
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
                'localeColumnName' => $this->getL10nBehavior()->getLocaleColumn()->getPhpName(),
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
                'localeColumnName' => $this->getL10nBehavior()->getLocaleColumn()->getPhpName(),
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
                'localeColumnName' => $this->getL10nBehavior()->getLocaleColumn()->getPhpName(),
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
                'objectClassName' => $this->getQueryBuilder()->getClassNameFromBuilder(
                    $this->getQueryBuilder()->getStubObjectBuilder()
                ),
                'alias' => ucfirst($alias),
                'localeColumnName' => $this->getL10nBehavior()->getLocaleColumn()->getPhpName(),
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
                'queryClass' => $this->getQueryBuilder()->getQueryClassName(),
                'columnPhpName' => $column->getPhpName(),
                'columnName' => $column->getName(),
                'localeColumnName' => $this->getL10nBehavior()->getLocaleColumn()->getPhpName(),
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
                'objectClassName' => $this->getQueryBuilder()->getClassNameFromBuilder(
                    $this->getQueryBuilder()->getStubObjectBuilder()
                ),
                'columnPhpName' => $column->getPhpName(),
                'columnName' => $column->getName(),
                'localeColumnName' => $this->getL10nBehavior()->getLocaleColumn()->getPhpName(),
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
                'objectClassName' => $this->getQueryBuilder()->getClassNameFromBuilder(
                    $this->getQueryBuilder()->getStubObjectBuilder()
                ),
                'columnPhpName' => $column->getPhpName(),
                'columnName' => $column->getName(),
                'localeColumnName' => $this->getL10nBehavior()->getLocaleColumn()->getPhpName(),
            ]
        );
    }

    /**
     * @return string
     */
    #[Override]
    protected function addJoinI18n(): string
    {
        $fk = $this->getL10nBehavior()->getI18nForeignKey();

        return $this->renderTemplate(
            'queryJoinI18n',
            [
                'queryClass' => $this->getQueryBuilder()->getQueryClassName(),
                'i18nRelationName' => $this->getQueryBuilder()->getRefFKPhpNameAffix($fk),
                'localeColumn' => $this->getL10nBehavior()->getLocaleColumn()->getPhpName(),
            ]
        );
    }

    /**
     * @return string
     */
    #[Override]
    protected function addJoinWithI18n(): string
    {
        $fk = $this->getL10nBehavior()->getI18nForeignKey();

        return $this->renderTemplate(
            'queryJoinWithI18n',
            [
                'queryClass' => $this->getQueryBuilder()->getQueryClassName(),
                'i18nRelationName' => $this->getQueryBuilder()->getRefFKPhpNameAffix($fk),
            ]
        );
    }

    /**
     * @return string
     */
    #[Override]
    protected function addUseI18nQuery(): string
    {
        $i18nTable = $this->getL10nBehavior()->getI18nTable();
        $fk = $this->getL10nBehavior()->getI18nForeignKey();

        return $this->renderTemplate(
            'queryUseI18nQuery',
            [
                'queryClass' => $this->getQueryBuilder()->getClassNameFromBuilder(
                    $this->getQueryBuilder()->getNewStubQueryBuilder($i18nTable)
                ),
                'namespacedQueryClass' => $this->getQueryBuilder()->getNewStubQueryBuilder($i18nTable)
                    ->getFullyQualifiedClassName(),
                'i18nRelationName' => $this->getQueryBuilder()->getRefFKPhpNameAffix($fk),
                'localeColumn' => $this->getL10nBehavior()->getLocaleColumn()->getPhpName(),
            ]
        );
    }
}
