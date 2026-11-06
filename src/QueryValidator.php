<?php

namespace Gautrot\Propel\Behavior\L10n;

use InvalidArgumentException;
use Propel\Runtime\ActiveQuery\Criteria;

/**
 * # QueryValidator
 * Validates SQL structure supplied to generated localization queries
 * @api
 */
final class QueryValidator
{
    /**
     * @param string|null $comparison
     * @return string|null
     */
    public static function comparison(?string $comparison): ?string
    {
        if ($comparison === null) {
            return null;
        }
        $allowed = [
            Criteria::EQUAL, Criteria::NOT_EQUAL, Criteria::ALT_NOT_EQUAL,
            Criteria::LESS_THAN, Criteria::LESS_EQUAL, Criteria::GREATER_THAN, Criteria::GREATER_EQUAL,
            Criteria::LIKE, Criteria::NOT_LIKE, Criteria::ILIKE, Criteria::NOT_ILIKE,
            Criteria::IN, Criteria::NOT_IN, Criteria::ISNULL, Criteria::ISNOTNULL,
        ];
        foreach ($allowed as $operator) {
            if (strtoupper(trim($comparison)) === trim($operator)) {
                return $operator;
            }
        }
        throw new InvalidArgumentException('Unsupported localized column comparison operator.');
    }

    /**
     * @param string|null $joinType
     * @return string|null
     */
    public static function joinType(?string $joinType): ?string
    {
        if ($joinType === null) {
            return null;
        }
        $joinType = strtoupper(trim($joinType));
        if (!in_array($joinType, [Criteria::LEFT_JOIN, Criteria::RIGHT_JOIN, Criteria::INNER_JOIN], true)) {
            throw new InvalidArgumentException('Unsupported localization join type.');
        }

        return $joinType;
    }

    /**
     * @param string|null $alias
     * @return void
     */
    public static function alias(?string $alias): void
    {
        if ($alias !== null && !preg_match('/\A[A-Za-z_]\w*\z/', $alias)) {
            throw new InvalidArgumentException('A localization join alias must be a SQL identifier.');
        }
    }
}
