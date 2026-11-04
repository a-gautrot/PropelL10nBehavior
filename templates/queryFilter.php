<?php

echo <<<PHP
/**
 * Filters the query with the
 *
 * Example usage:
 * <code>
 * \$query->filterBy$columnPhpName('fooValue'); //
     WHERE $columnName = 'fooValue'
 * \$query->filterBy$columnPhpName('%fooValue%'); //
     WHERE $columnName LIKE '%fooValue%'
 * </code>
 *
 * @param string|string[]|null \$$columnName The value to use as filter.
 *          Accepts wildcards (* and % trigger a LIKE)
 * @param string|null \$comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
 * @param string|null \$$localeVariableName Overwrites the locale for this filter
 *
 * @return $queryClass The current query, for fluid interface
 * @throws PropelException
 */
public function filterBy$columnPhpName(\$$columnName, ?string \$comparison = null, ?string \$$localeVariableName = null): $queryClass
{
    \$comparison = \\Gautrot\\Propel\\Behavior\\L10n\\QueryValidator::comparison(\$comparison);

    if (\$$localeVariableName === null) {
        \$$localeVariableName = \$this->resolveLocale(\$$localeVariableName);
    }

    if (\$comparison === null) {
        if (is_array(\$$columnName)) {
            \$comparison = Criteria::IN;
        } elseif (preg_match('/[\\%\\*]/', \$$columnName)) {
            \$token = str_replace('*', '%', \$$columnName);
            \$comparison = Criteria::LIKE;
        }
    }

    return \$this->useI18nQuery(\$$localeVariableName)
        ->filterBy$columnPhpName(\$$columnName , \$comparison)
        ->endUse();
}


PHP;
