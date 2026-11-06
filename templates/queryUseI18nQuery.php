<?php

echo <<<PHP
/**
 * Use the I18n relation query object
 *
 * @see useQuery()
 *
 * @param string|null \$$localeVariableName Locale to use for the join condition, e.g. 'fr_FR'
 * @param string|null \$relationAlias optional alias for the relation
 * @param string|null \$joinType Accepted values are null, 'left join', 'right join', 'inner join'. Defaults to left join.
 *
 * @return $namespacedQueryClass A secondary query class using the current class as primary query
 * @throws PropelException
 */
public function useI18nQuery(?string \$$localeVariableName = null, ?string \$relationAlias = null, ?string \$joinType = Criteria::LEFT_JOIN): ModelCriteria
{
    if (\$$localeVariableName === null) {
        \$$localeVariableName = \$this->resolveLocale(\$$localeVariableName);
    }

    return \$this->joinI18n(\$$localeVariableName, \$relationAlias, \$joinType)
        ->useQuery(\$relationAlias ?: '$i18nRelationName', '$namespacedQueryClass');
}


PHP;
