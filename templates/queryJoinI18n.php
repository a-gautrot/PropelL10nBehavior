<?php

echo <<<PHP
/**
 * Adds a JOIN clause to the query using the i18n relation
 *
 * @param string|null \$$localeVariableName Locale to use for the join condition, e.g. 'fr_FR'
 * @param string|null \$relationAlias optional alias for the relation
 * @param string|null \$joinType Accepted values are null, 'left join', 'right join', 'inner join'. Defaults to left join.
 *
 * @return $queryClass The current query, for fluid interface
 * @throws PropelException
 */
public function joinI18n(?string \$$localeVariableName = null, ?string \$relationAlias = null, ?string \$joinType = Criteria::LEFT_JOIN): $queryClass
{
    \$joinType = \\Gautrot\\Propel\\Behavior\\L10n\\QueryValidator::joinType(\$joinType);
    \\Gautrot\\Propel\\Behavior\\L10n\\QueryValidator::alias(\$relationAlias);

    if (\$$localeVariableName === null) {
        \$$localeVariableName = \$this->resolveLocale(\$$localeVariableName);
    }

    \$relationName = \$relationAlias ?: '$i18nRelationName';

    return \$this->join$i18nRelationName(\$relationAlias, \$joinType)
        ->addJoinCondition(\$relationName, \$relationName . '.$localeColumn = ?', \$$localeVariableName);
}


PHP;
