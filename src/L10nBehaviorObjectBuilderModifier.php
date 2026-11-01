<?php

namespace Gossi\Propel\Behavior\L10n;

use Propel\Generator\Behavior\I18n\I18nBehaviorObjectBuilderModifier;
use Propel\Generator\Builder\Om\ObjectBuilder;
use Propel\Generator\Model\Column;

/**
 * # L10nBehaviorObjectBuilderModifier
 */
class L10nBehaviorObjectBuilderModifier extends I18nBehaviorObjectBuilderModifier
{
    use RenderTrait;

    /**
     * @var L10nBehavior
     */
    protected $behavior;

    /**
     * @param ObjectBuilder $builder
     * @return string
     */
    public function objectAttributes(ObjectBuilder $builder): string
    {
        return $this->renderTemplate(
            'objectAttributes',
            [
            'objectClassName' => $builder->getClassNameFromBuilder(
                $builder->getNewStubObjectBuilder($this->behavior->getI18nTable())
            ),
            ]
        );
    }

    /**
     * @param ObjectBuilder $builder
     * @return string
     */
    public function objectClearReferences(ObjectBuilder $builder): string
    {
        return $this->renderTemplate('objectClearReferences');
    }

    /**
     * @param ObjectBuilder $builder
     * @return string
     */
    public function objectMethods(ObjectBuilder $builder): string
    {
        $builder->declareClass(PropelL10n::class);
        $this->builder = $builder;

        $script = $this->addSetLocale();
        $script .= $this->addGetLocale();

        $alias = $this->behavior->getParameter('locale_alias');
        if ($alias) {
            $script .= $this->addGetLocaleAlias($alias);
            $script .= $this->addSetLocaleAlias($alias);
        }

        $script .= $this->addResolveLocale();
        $script .= $this->addGetTranslation();
        $script .= $this->addRemoveTranslation();
        $script .= $this->addGetCurrentTranslation();

        foreach ($this->behavior->getI18nColumns() as $column) {
            $script .= $this->addTranslatedColumnGetter($column);
            $script .= $this->addTranslatedColumnSetter($column);
        }

        return $script;
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
     * @return string
     */
    protected function addGetTranslation(): string
    {
        $i18nTable = $this->behavior->getI18nTable();
        $fk = $this->behavior->getI18nForeignKey();

        return $this->renderTemplate(
            'objectGetTranslation',
            [
            'i18nTablePhpName' => $this->builder->getClassNameFromBuilder(
                $this->builder->getNewStubObjectBuilder($i18nTable)
            ),
            'i18nListVariable' => $this->builder->getRefFKCollVarName($fk),
            'localeColumnName' => $this->behavior->getLocaleColumn()->getPhpName(),
            'i18nQueryName' => $this->builder->getClassNameFromBuilder(
                $this->builder->getNewStubQueryBuilder($i18nTable)
            ),
            'i18nSetterMethod' => $this->builder->getRefFKPhpNameAffix($fk),
            ]
        );
    }

    /**
     * @return string
     */
    protected function addRemoveTranslation(): string
    {
        $i18nTable = $this->behavior->getI18nTable();
        $fk = $this->behavior->getI18nForeignKey();

        return $this->renderTemplate(
            'objectRemoveTranslation',
            [
            'objectClassName' => $this->builder->getClassNameFromBuilder($this->builder->getStubObjectBuilder()),
            'i18nQueryName' => $this->builder->getClassNameFromBuilder(
                $this->builder->getNewStubQueryBuilder($i18nTable)
            ),
            'i18nCollection' => $this->builder->getRefFKCollVarName($fk),
            'localeColumnName' => $this->behavior->getLocaleColumn()->getPhpName(),
            ]
        );
    }

    /**
     * @return string
     */
    protected function addGetCurrentTranslation(): string
    {
        return $this->renderTemplate(
            'objectGetCurrentTranslation',
            [
            'i18nTablePhpName' => $this->builder->getClassNameFromBuilder(
                $this->builder->getNewStubObjectBuilder($this->behavior->getI18nTable())
            ),
            'localeColumnName' => $this->behavior->getLocaleColumn()->getPhpName(),
            ]
        );
    }

    /**
     * @param Column $column
     * @return string
     */
    protected function addTranslatedColumnGetter(Column $column): string
    {
        $objectBuilder = $this->builder->getNewObjectBuilder($this->behavior->getI18nTable());
        $comment = '';
        $functionStatement = '';

        if ($this->isDateType($column->getType())) {
            $objectBuilder->addTemporalAccessorComment($comment, $column);
            $objectBuilder->addTemporalAccessorOpen($functionStatement, $column);
        } else {
            $objectBuilder->addDefaultAccessorComment($comment, $column);
            $objectBuilder->addDefaultAccessorOpen($functionStatement, $column);
        }

        $comment = preg_replace('/^\t/m', '', $comment);

        // Adds @throws tag to generated method
        $comment = str_replace('*/', "* @throws PropelException\n\t */", $comment);
        $functionStatement = preg_replace('/^\t/m', '', $functionStatement);
        preg_match_all('/\$[a-z]+/i', $functionStatement, $params);

        // Uses the locale's alias name for the var if it is different from "locale"
        $localeVariableName = $this->behavior->getLocaleVariableName();
        $localeParameter = "?string \$$localeVariableName = null";
        $comment = str_replace('*/', "* @param string|null \$$localeVariableName\n\t */", $comment);

        // Adds "$useLocaleChain" if we want to use a list of locales or just one
        $useLocaleChain = ($this->behavior->getParameter('use_locale_chain') === 'true') ? 'true' : 'false';
        $localeParameter .= ", bool \$useLocaleChain = $useLocaleChain";
        $comment = str_replace('*/', "* @param bool \$useLocaleChain Whether to use the locale chain\n\t */", $comment);

        // Adds the locale parameter to the getter parameters
        if (strpos($functionStatement, '()') !== false) {
            $functionStatement = str_replace('()', "($localeParameter)", $functionStatement);
        } else {
            $functionStatement = str_replace(')', ", $localeParameter)", $functionStatement);
        }

        return $this->renderTemplate(
            'objectTranslatedColumnGetter',
            [
            'comment' => $comment,
            'functionStatement' => $functionStatement,
            'columnPhpName' => $column->getPhpName(),
            'params' => implode(', ', $params[0]),
            'localeColumnName' => $this->behavior->getLocaleColumn()->getPhpName()
            ]
        );
    }

    /**
     * @param Column $column
     * @return string
     */
    protected function addTranslatedColumnSetter(Column $column): string
    {
        $objectBuilder = $this->builder->getNewObjectBuilder($this->behavior->getI18nTable());
        $comment = '';
        $functionStatement = '';

        if ($this->isDateType($column->getType())) {
            $objectBuilder->addTemporalMutatorComment($comment, $column);
        } else {
            $objectBuilder->addMutatorComment($comment, $column);
        }

        $objectBuilder->addMutatorOpenOpen($functionStatement, $column);
        $comment = preg_replace('/^\t/m', '', $comment);

        // Adds @throws tag to generated method
        $comment = str_replace('*/', "* @throws PropelException\n\t */", $comment);
        $functionStatement = preg_replace('/^\t/m', '', $functionStatement);
        preg_match_all('/\$[a-z]+/i', $functionStatement, $params);

        // Uses the locale's alias name for the var if it is different from "locale"
        $localeVariableName = $this->behavior->getLocaleVariableName();
        $localeParameter = "?string \$$localeVariableName = null";
        $comment = str_replace('*/', "* @param string|null \$$localeVariableName\n\t */", $comment);

        // Adds the locale parameter to the setter parameters
        $functionStatement = str_replace(')', ", $localeParameter)", $functionStatement);

        return $this->renderTemplate(
            'objectTranslatedColumnSetter',
            [
            'comment' => $comment,
            'functionStatement' => $functionStatement,
            'columnPhpName' => $column->getPhpName(),
            'params' => implode(', ', $params[0]),
            'localeColumnName' => $this->behavior->getLocaleColumn()->getPhpName()
            ]
        );
    }

    /**
     * @param string $script
     * @param ObjectBuilder $builder
     * @return void
     */
    public function objectFilter(string &$script, ObjectBuilder $builder): void
    {
        $i18nTable = $this->behavior->getI18nTable();
        $i18nTablePhpName = $builder->getNewStubObjectBuilder($i18nTable)->getUnprefixedClassName();
        $pattern = '/public function add' . preg_quote($i18nTablePhpName, '/') . '.*[\r\n]\s*\{/';
        // Adds this chunk of code in "add...I18n" to check if we're using the default or alias var name
        $addition = $this->renderTemplate(
            'objectAddTranslation',
            [
                'localeColumnName' => $this->behavior->getLocaleColumn()->getPhpName(),
            ]
        );
        $script = preg_replace_callback(
            $pattern,
            static fn(array $matches): string => $matches[0] . "\n" . $addition,
            $script
        );
    }
}
