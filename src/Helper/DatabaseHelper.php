<?php

namespace HeimrichHannot\EntityFilterBundle\Helper;

use Contao\Controller;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Query\QueryBuilder;

class DatabaseHelper
{
    public const SQL_CONDITION_OR = 'OR';
    public const SQL_CONDITION_AND = 'AND';

    public const OPERATOR_LIKE = 'like';
    public const OPERATOR_UNLIKE = 'unlike';
    public const OPERATOR_EQUAL = 'equal';
    public const OPERATOR_UNEQUAL = 'unequal';
    public const OPERATOR_LOWER = 'lower';
    public const OPERATOR_LOWER_EQUAL = 'lowerequal';
    public const OPERATOR_GREATER = 'greater';
    public const OPERATOR_GREATER_EQUAL = 'greaterequal';
    public const OPERATOR_IN = 'in';
    public const OPERATOR_NOT_IN = 'notin';
    public const OPERATOR_IS_NULL = 'isnull';
    public const OPERATOR_IS_NOT_NULL = 'isnotnull';
    public const OPERATOR_IS_EMPTY = 'isempty';
    public const OPERATOR_IS_NOT_EMPTY = 'isnotempty';
    public const OPERATOR_REGEXP = 'regexp';
    public const OPERATOR_NOT_REGEXP = 'notregexp';

    public const OPERATORS = [
        self::OPERATOR_LIKE,
        self::OPERATOR_UNLIKE,
        self::OPERATOR_EQUAL,
        self::OPERATOR_UNEQUAL,
        self::OPERATOR_LOWER,
        self::OPERATOR_LOWER_EQUAL,
        self::OPERATOR_GREATER,
        self::OPERATOR_GREATER_EQUAL,
        self::OPERATOR_IN,
        self::OPERATOR_NOT_IN,
        self::OPERATOR_IS_NULL,
        self::OPERATOR_IS_NOT_NULL,
        self::OPERATOR_IS_EMPTY,
        self::OPERATOR_IS_NOT_EMPTY,
        self::OPERATOR_REGEXP,
        self::OPERATOR_NOT_REGEXP,
    ];

    public function __construct(
        private readonly InsertTagParser $insertTagParser,
    ) {
    }

    /**
     * Computes a MySQL condition appropriate for the given operator.
     *
     * @return array Returns array($strQuery, $arrValues)
     */
    public function computeCondition(string $field, string $operator, mixed $value, string $table): array
    {
        $operator = trim(strtolower($operator));
        $values = [];
        $pattern = '?';
        $addQuotes = false;

        $explodedField = explode('.', $field);

        // remove table if already added to field name
        if (\count($explodedField) > 1) {
            $field = end($explodedField);
        }

        Controller::loadDataContainer($table);

        $dca = &$GLOBALS['TL_DCA'][$table]['fields'][$field];

        if (isset($dca['sql']) && false !== stripos((string) $dca['sql'], 'blob')) {
            $addQuotes = true;
        }

        switch ($operator) {
            case '<>':
            case static::OPERATOR_UNEQUAL:
            case static::OPERATOR_EQUAL:
                $values[] = $this->insertTagParser->replaceInline(\is_array($value) ? implode(' ', $value) : $value);

                break;

            case static::OPERATOR_GREATER:
            case static::OPERATOR_LOWER_EQUAL:
            case static::OPERATOR_GREATER_EQUAL:
            case static::OPERATOR_LOWER:
                $pattern = 'CAST(? AS DECIMAL)';
                $values[] = $this->insertTagParser->replaceInline(\is_array($value) ? implode(' ', $value) : $value);

                break;

            case static::OPERATOR_IN:
                $value = array_filter(explode(',', $this->insertTagParser->replaceInline($value)));

                // skip if empty to avoid sql error
                if (empty($value)) {
                    break;
                }

                $pattern = '(' . implode(
                    ',',
                    array_map(
                        fn ($val) => '"' . addslashes(trim($val)) . '"',
                        $value
                    )
                ) . ')';

                break;

            case static::OPERATOR_NOT_IN:
                $value = array_filter(explode(',', $this->insertTagParser->replaceInline($value)));

                // skip if empty to avoid sql error
                if (empty($value)) {
                    break;
                }

                $pattern = '(' . implode(
                    ',',
                    array_map(
                        fn ($val) => '"' . addslashes(trim($val)) . '"',
                        $value
                    )
                ) . ')';

                break;

            case static::OPERATOR_IS_NULL:
            case static::OPERATOR_IS_NOT_NULL:
            case static::OPERATOR_IS_EMPTY:
            case static::OPERATOR_IS_NOT_EMPTY:
                $pattern = '';

                break;
            case static::OPERATOR_UNLIKE:
            default:
                if (\is_array($value)) {
                    foreach ($value as $val) {
                        $values[] = $this->insertTagParser->replaceInline('%' . ($addQuotes ? '"' . $val . '"' : $val) . '%');
                    }

                    break;
                }
                $values[] = $this->insertTagParser->replaceInline('%' . ($addQuotes ? '"' . $value . '"' : $value) . '%');

                break;
        }

        $operator = $this->transformVerboseOperator($operator);

        return [$table . '.' . "$field $operator $pattern", $values];
    }

    /**
     * Transforms verbose operators to valid MySQL operators (aka junctors).
     * Supports: like, unlike, equal, unequal, lower, greater, lowerequal, greaterequal, in, notin.
     *
     * @return string The transformed operator or false if not supported
     */
    private function transformVerboseOperator(string $verboseOperator): string
    {
        return match ($verboseOperator) {
            static::OPERATOR_LIKE => 'LIKE',
            static::OPERATOR_UNLIKE => 'NOT LIKE',
            static::OPERATOR_EQUAL => '=',
            static::OPERATOR_UNEQUAL => '!=',
            static::OPERATOR_LOWER => '<',
            static::OPERATOR_GREATER => '>',
            static::OPERATOR_LOWER_EQUAL => '<=',
            static::OPERATOR_GREATER_EQUAL => '>=',
            static::OPERATOR_IN => 'IN',
            static::OPERATOR_NOT_IN => 'NOT IN',
            static::OPERATOR_IS_NULL => 'IS NULL',
            static::OPERATOR_IS_NOT_NULL => 'IS NOT NULL',
            static::OPERATOR_IS_EMPTY => '=""',
            static::OPERATOR_IS_NOT_EMPTY => '!=""',
            default => '',
        };
    }

    public function composeWhereForQueryBuilder(QueryBuilder $queryBuilder, string $field, string $operator, ?array $dca = null, $value = null): string
    {
        $wildcard = ':' . str_replace('.', '_', $field);
        $wildcardParameterName = substr($wildcard, 1);
        $where = '';

        // remove dot for table prefixes
        if (str_contains($wildcard, '.')) {
            $wildcard = str_replace('.', '_', $wildcard);
        }

        switch ($operator) {
            case self::OPERATOR_LIKE:
                $where = $queryBuilder->expr()->like($field, $wildcard);
                $queryBuilder->setParameter($wildcardParameterName, '%' . $this->insertTagParser->replaceInline(\is_array($value) ? implode(' ', $value) : $value) . '%');

                break;

            case self::OPERATOR_UNLIKE:
                $where = $queryBuilder->expr()->notLike($field, $wildcard);
                $queryBuilder->setParameter($wildcardParameterName, '%' . $this->insertTagParser->replaceInline(\is_array($value) ? implode(' ', $value) : $value) . '%');

                break;

            case self::OPERATOR_EQUAL:
                $where = $queryBuilder->expr()->eq($field, $wildcard);
                $queryBuilder->setParameter($wildcardParameterName, $this->insertTagParser->replaceInline(\is_array($value) ? implode(' ', $value) : $value));

                break;

            case self::OPERATOR_UNEQUAL:
                $where = $queryBuilder->expr()->neq($field, $wildcard);
                $queryBuilder->setParameter($wildcardParameterName, $this->insertTagParser->replaceInline(\is_array($value) ? implode(' ', $value) : $value));

                break;

            case self::OPERATOR_LOWER:
                $where = $queryBuilder->expr()->lt($field, $wildcard);
                $queryBuilder->setParameter($wildcardParameterName, $this->insertTagParser->replaceInline(\is_array($value) ? implode(' ', $value) : $value));

                break;

            case self::OPERATOR_LOWER_EQUAL:
                $where = $queryBuilder->expr()->lte($field, $wildcard);
                $queryBuilder->setParameter($wildcardParameterName, $this->insertTagParser->replaceInline(\is_array($value) ? implode(' ', $value) : $value));

                break;

            case self::OPERATOR_GREATER:
                $where = $queryBuilder->expr()->gt($field, $wildcard);
                $queryBuilder->setParameter($wildcardParameterName, $this->insertTagParser->replaceInline(\is_array($value) ? implode(' ', $value) : $value));

                break;

            case self::OPERATOR_GREATER_EQUAL:
                $where = $queryBuilder->expr()->gte($field, $wildcard);
                $queryBuilder->setParameter($wildcardParameterName, $this->insertTagParser->replaceInline(\is_array($value) ? implode(' ', $value) : $value));

                break;

            case self::OPERATOR_IN:
                $value = array_filter(!\is_array($value) ? explode(',', (string) $value) : $value);

                // if empty add an unfulfillable condition in order to avoid an sql error
                if (empty($value)) {
                    $where = $queryBuilder->expr()->eq(1, 2);
                } else {
                    $where = $queryBuilder->expr()->in($field, $wildcard);
                    $preparedValue = array_map(
                        fn ($val) => addslashes($this->insertTagParser->replaceInline(trim((string) $val))),
                        $value
                    );
                    $queryBuilder->setParameter($wildcardParameterName, $preparedValue, ArrayParameterType::STRING);
                }

                break;

            case self::OPERATOR_NOT_IN:
                $value = array_filter(!\is_array($value) ? explode(',', (string) $value) : $value);

                // if empty add an unfulfillable condition in order to avoid an sql error
                if (empty($value)) {
                    $where = $queryBuilder->expr()->eq(1, 2);
                } else {
                    $where = $queryBuilder->expr()->notIn($field, $wildcard);
                    $preparedValue = array_map(
                        fn ($val) => addslashes($this->insertTagParser->replaceInline(trim((string) $val))),
                        $value
                    );
                    $queryBuilder->setParameter($wildcardParameterName, $preparedValue, ArrayParameterType::STRING);
                }

                break;

            case self::OPERATOR_IS_NULL:
                $where = $queryBuilder->expr()->isNull($field);

                break;

            case self::OPERATOR_IS_NOT_NULL:
                $where = $queryBuilder->expr()->isNotNull($field);

                break;

            case self::OPERATOR_IS_EMPTY:
                $where = $queryBuilder->expr()->eq($field, '\'\'');

                break;

            case self::OPERATOR_IS_NOT_EMPTY:
                $where = $queryBuilder->expr()->neq($field, '\'\'');

                break;

            case self::OPERATOR_REGEXP:
            case self::OPERATOR_NOT_REGEXP:
                $where = $field . (self::OPERATOR_NOT_REGEXP == $operator ? ' NOT REGEXP ' : ' REGEXP ') . $wildcard;

                if (\is_array($dca) && isset($dca['eval']['multiple']) && $dca['eval']['multiple']) {
                    // match a serialized blob
                    if (\is_array($value)) {
                        // build a regexp alternative, e.g. (:"1";|:"2";)
                        $queryBuilder->setParameter(
                            $wildcardParameterName,
                            '(' . implode(
                                '|',
                                array_map(
                                    fn ($val) => ':"' . $this->insertTagParser->replaceInline($val) . '";',
                                    $value
                                )
                            ) . ')'
                        );
                    } else {
                        $queryBuilder->setParameter($wildcardParameterName, ':"' . $this->insertTagParser->replaceInline($value) . '";');
                    }
                } else {
                    // TODO: this makes no sense, yet
                    $queryBuilder->setParameter($wildcardParameterName, $this->insertTagParser->replaceInline(\is_array($value) ? implode(' ', $value) : $value));
                }

                break;
        }

        return $where;
    }
}
