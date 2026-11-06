<?php

namespace Gautrot\Propel\Behavior\L10n;

use LogicException;
use Override;
use Propel\Generator\Behavior\I18n\I18nBehaviorObjectBuilderModifier;
use Propel\Generator\Builder\Om\ObjectBuilder;
use Propel\Generator\Model\Column;

/**
 * # L10nBehaviorObjectBuilderModifier
 * @api
 */
class L10nBehaviorObjectBuilderModifier extends I18nBehaviorObjectBuilderModifier
{
    use RenderTrait;

    private function addNullableReturnType(string $comment): string
    {
        if (preg_match('/@return ([^\\s]+)/', $comment, $matches) === 1) {
            $type = $matches[1];
            if ($type !== 'mixed' && !str_contains($type, 'null')) {
                return str_replace('@return ' . $type, '@return ' . $type . '|null', $comment);
            }
        }

        return $comment;
    }

    private function stripGeneratedIndent(string $code): string
    {
        $result = preg_replace('/^\\t/m', '', $code);
        if ($result === null) {
            throw new LogicException('Unable to normalize generated localization code.');
        }

        return $result;
    }

    protected function getObjectBuilder(): ObjectBuilder
    {
        $builder = $this->builder;
        if ($builder === null) {
            throw new LogicException('The localization ObjectBuilder has not been initialized.');
        }

        return $builder;
    }



    /**
     * @param ObjectBuilder $builder
     * @return string
     */
    #[Override]
    public function objectAttributes(ObjectBuilder $builder): string
    {
        return $this->renderTemplate(
            'objectAttributes',
            [
            'objectClassName' => $builder->getClassNameFromBuilder(
                $builder->getNewStubObjectBuilder($this->getL10nBehavior()->getI18nTable())
            ),
            ]
        );
    }

    /**
     * @param ObjectBuilder $builder
     * @return string
     */
    #[Override]
    public function objectClearReferences(ObjectBuilder $builder): string
    {
        return $this->renderTemplate('objectClearReferences');
    }

    /**
     * @param ObjectBuilder $builder
     * @return string
     */
    #[Override]
    public function objectMethods(ObjectBuilder $builder): string
    {
        $builder->declareClass(PropelL10n::class);
        $this->builder = $builder;

        $script = $this->addSetLocale();
        $script .= $this->addGetLocale();

        $alias = $this->getL10nBehavior()->getLocaleAlias();
        if ($alias) {
            $script .= $this->addGetLocaleAlias($alias);
            $script .= $this->addSetLocaleAlias($alias);
        }

        $script .= $this->addResolveLocale();
        $script .= $this->addGetTranslation();
        $script .= $this->addRemoveTranslation();
        $script .= $this->addGetCurrentTranslation();

        foreach ($this->getL10nBehavior()->getI18nColumns() as $column) {
            $script .= $this->addTranslatedColumnGetter($column);
            $script .= $this->addTranslatedColumnSetter($column);
        }

        return $script;
    }

    /**
     * @return string
     */
    #[Override]
    protected function addSetLocale(): string
    {
        return $this->renderTemplate(
            'objectSetLocale',
            [
            'objectClassName' => $this->getObjectBuilder()->getClassNameFromBuilder(
                $this->getObjectBuilder()->getStubObjectBuilder()
            ),
            'localeColumnName' => $this->getL10nBehavior()->getLocaleColumn()->getPhpName(),
            ]
        );
    }

    /**
     * @return string
     */
    #[Override]
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
    #[Override]
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
    #[Override]
    protected function addSetLocaleAlias(string $alias): string
    {
        return $this->renderTemplate(
            'objectSetLocaleAlias',
            [
            'objectClassName' => $this->getObjectBuilder()->getClassNameFromBuilder(
                $this->getObjectBuilder()->getStubObjectBuilder()
            ),
            'alias' => ucfirst($alias),
            'localeColumnName' => $this->getL10nBehavior()->getLocaleColumn()->getPhpName(),
            ]
        );
    }

    /**
     * @return string
     */
    #[Override]
    protected function addGetTranslation(): string
    {
        $i18nTable = $this->getL10nBehavior()->getI18nTable();
        $fk = $this->getL10nBehavior()->getI18nForeignKey();

        return $this->renderTemplate(
            'objectGetTranslation',
            [
            'i18nTablePhpName' => $this->getObjectBuilder()->getClassNameFromBuilder(
                $this->getObjectBuilder()->getNewStubObjectBuilder($i18nTable)
            ),
            'i18nListVariable' => $this->getObjectBuilder()->getRefFKCollVarName($fk),
            'localeColumnName' => $this->getL10nBehavior()->getLocaleColumn()->getPhpName(),
            'i18nQueryName' => $this->getObjectBuilder()->getClassNameFromBuilder(
                $this->getObjectBuilder()->getNewStubQueryBuilder($i18nTable)
            ),
            'i18nSetterMethod' => $this->getObjectBuilder()->getRefFKPhpNameAffix($fk),
            ]
        );
    }

    /**
     * @return string
     */
    #[Override]
    protected function addRemoveTranslation(): string
    {
        $i18nTable = $this->getL10nBehavior()->getI18nTable();
        $fk = $this->getL10nBehavior()->getI18nForeignKey();

        return $this->renderTemplate(
            'objectRemoveTranslation',
            [
            'objectClassName' => $this->getObjectBuilder()->getClassNameFromBuilder(
                $this->getObjectBuilder()->getStubObjectBuilder()
            ),
            'i18nQueryName' => $this->getObjectBuilder()->getClassNameFromBuilder(
                $this->getObjectBuilder()->getNewStubQueryBuilder($i18nTable)
            ),
            'i18nCollection' => $this->getObjectBuilder()->getRefFKCollVarName($fk),
            'localeColumnName' => $this->getL10nBehavior()->getLocaleColumn()->getPhpName(),
            ]
        );
    }

    /**
     * @return string
     */
    #[Override]
    protected function addGetCurrentTranslation(): string
    {
        return $this->renderTemplate(
            'objectGetCurrentTranslation',
            [
            'i18nTablePhpName' => $this->getObjectBuilder()->getClassNameFromBuilder(
                $this->getObjectBuilder()->getNewStubObjectBuilder($this->getL10nBehavior()->getI18nTable())
            ),
            'localeColumnName' => $this->getL10nBehavior()->getLocaleColumn()->getPhpName(),
            ]
        );
    }

    /**
     * @param Column $column
     * @return string
     */
    #[Override]
    protected function addTranslatedColumnGetter(Column $column): string
    {
        $objectBuilder = $this->getObjectBuilder()->getNewObjectBuilder($this->getL10nBehavior()->getI18nTable());
        $comment = '';
        $functionStatement = '';

        if ($this->isDateType($column->getType())) {
            $objectBuilder->addTemporalAccessorComment($comment, $column);
            $objectBuilder->addTemporalAccessorOpen($functionStatement, $column);
        } else {
            $objectBuilder->addDefaultAccessorComment($comment, $column);
            $objectBuilder->addDefaultAccessorOpen($functionStatement, $column);
        }

        $comment = $this->addNullableReturnType($this->stripGeneratedIndent($comment));

        // Adds @throws tag to generated method
        $comment = str_replace('*/', "* @throws PropelException\n\t */", $comment);
        $functionStatement = $this->stripGeneratedIndent($functionStatement);
        preg_match_all('/\$[a-z]+/i', $functionStatement, $params);

        // Uses the locale's alias name for the var if it is different from "locale"
        $localeVariableName = $this->getL10nBehavior()->getLocaleVariableName();
        $localeParameter = "?string \$$localeVariableName = null";
        $comment = str_replace('*/', "* @param string|null \$$localeVariableName\n\t */", $comment);

        // Adds "$useLocaleChain" if we want to use a list of locales or just one
        $useLocaleChain = ($this->getL10nBehavior()->getParameter('use_locale_chain') === 'true') ? 'true' : 'false';
        $localeParameter .= ", bool \$useLocaleChain = $useLocaleChain";
        $comment = str_replace('*/', "* @param bool \$useLocaleChain Whether to use the locale chain\n\t */", $comment);

        // Adds the locale parameter to the getter parameters
        if (str_contains($functionStatement, '()')) {
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
            'localeColumnName' => $this->getL10nBehavior()->getLocaleColumn()->getPhpName()
            ]
        );
    }

    /**
     * @param Column $column
     * @return string
     */
    #[Override]
    protected function addTranslatedColumnSetter(Column $column): string
    {
        $objectBuilder = $this->getObjectBuilder()->getNewObjectBuilder($this->getL10nBehavior()->getI18nTable());
        $comment = '';
        $functionStatement = '';

        if ($this->isDateType($column->getType())) {
            $objectBuilder->addTemporalMutatorComment($comment, $column);
        } else {
            $objectBuilder->addMutatorComment($comment, $column);
        }

        $objectBuilder->addMutatorOpenOpen($functionStatement, $column);
        $comment = $this->stripGeneratedIndent($comment);

        // Adds @throws tag to generated method
        $comment = str_replace('*/', "* @throws PropelException\n\t */", $comment);
        $functionStatement = $this->stripGeneratedIndent($functionStatement);
        preg_match_all('/\$[a-z]+/i', $functionStatement, $params);

        // Uses the locale's alias name for the var if it is different from "locale"
        $localeVariableName = $this->getL10nBehavior()->getLocaleVariableName();
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
            'localeColumnName' => $this->getL10nBehavior()->getLocaleColumn()->getPhpName()
            ]
        );
    }

    /**
     * @param string $script
     * @param ObjectBuilder $builder
     * @return void
     */
    #[Override]
    public function objectFilter(string &$script, ObjectBuilder $builder): void
    {
        $i18nTable = $this->getL10nBehavior()->getI18nTable();
        $i18nTablePhpName = $builder->getNewStubObjectBuilder($i18nTable)->getUnprefixedClassName();
        $pattern = '/public function add' . preg_quote($i18nTablePhpName, '/') . '.*[\r\n]\s*\{/';
        // Adds this chunk of code in "add...I18n" to check if we're using the default or alias var name
        $addition = $this->renderTemplate(
            'objectAddTranslation',
            [
                'localeColumnName' => $this->getL10nBehavior()->getLocaleColumn()->getPhpName(),
            ]
        );
        $filteredScript = preg_replace_callback(
            $pattern,
            static fn(array $matches): string => $matches[0] . "\n" . $addition,
            $script
        );
        if ($filteredScript === null) {
            throw new LogicException('Unable to update generated localization code.');
        }
        $script = $filteredScript;
    }
}
