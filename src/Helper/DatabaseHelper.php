<?php

namespace HeimrichHannot\EntityFilterBundle\Helper;

use Contao\Controller;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

class DatabaseHelper
{
    const OPERATOR_LIKE = 'like';
    const OPERATOR_UNLIKE = 'unlike';
    const OPERATOR_EQUAL = 'equal';
    const OPERATOR_UNEQUAL = 'unequal';
    const OPERATOR_LOWER = 'lower';
    const OPERATOR_LOWER_EQUAL = 'lowerequal';
    const OPERATOR_GREATER = 'greater';
    const OPERATOR_GREATER_EQUAL = 'greaterequal';
    const OPERATOR_IN = 'in';
    const OPERATOR_NOT_IN = 'notin';
    const OPERATOR_IS_NULL = 'isnull';
    const OPERATOR_IS_NOT_NULL = 'isnotnull';
    const OPERATOR_IS_EMPTY = 'isempty';
    const OPERATOR_IS_NOT_EMPTY = 'isnotempty';
    const OPERATOR_REGEXP = 'regexp';
    const OPERATOR_NOT_REGEXP = 'notregexp';

    public function __construct(
        private readonly InsertTagParser $insertTagParser,
    )
    {
    }

    /**
     * Computes a MySQL condition appropriate for the given operator.
     *
     * @param mixed $value
     * @param string $table
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

        if (isset($dca['sql']) && false !== stripos($dca['sql'], 'blob')) {
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
                            function ($val) {
                                return '"' . addslashes(trim($val)) . '"';
                            },
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
                            function ($val) {
                                return '"' . addslashes(trim($val)) . '"';
                            },
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
        switch ($verboseOperator) {
            case static::OPERATOR_LIKE:
                return 'LIKE';

            case static::OPERATOR_UNLIKE:
                return 'NOT LIKE';

            case static::OPERATOR_EQUAL:
                return '=';

            case static::OPERATOR_UNEQUAL:
                return '!=';

            case static::OPERATOR_LOWER:
                return '<';

            case static::OPERATOR_GREATER:
                return '>';

            case static::OPERATOR_LOWER_EQUAL:
                return '<=';

            case static::OPERATOR_GREATER_EQUAL:
                return '>=';

            case static::OPERATOR_IN:
                return 'IN';

            case static::OPERATOR_NOT_IN:
                return 'NOT IN';

            case static::OPERATOR_IS_NULL:
                return 'IS NULL';

            case static::OPERATOR_IS_NOT_NULL:
                return 'IS NOT NULL';

            case static::OPERATOR_IS_EMPTY:
                return '=""';

            case static::OPERATOR_IS_NOT_EMPTY:
                return '!=""';
        }

        return '';

    }

    public function composeWhereForQueryBuilder(QueryBuilder $queryBuilder, string $field, string $operator, array $dca = null, $value = null): string
    {
        $wildcard = ':'.str_replace('.', '_', $field);
        $wildcardParameterName = substr($wildcard, 1);
        $where = '';

        // remove dot for table prefixes
        if (false !== strpos($wildcard, '.')) {
            $wildcard = str_replace('.', '_', $wildcard);
        }

        switch ($operator) {
            case self::OPERATOR_LIKE:
                $where = $queryBuilder->expr()->like($field, $wildcard);
                $queryBuilder->setParameter($wildcardParameterName, '%'.$this->insertTagParser->replaceInline(\is_array($value) ? implode(' ', $value) : $value).'%');

                break;

            case self::OPERATOR_UNLIKE:
                $where = $queryBuilder->expr()->notLike($field, $wildcard);
                $queryBuilder->setParameter($wildcardParameterName, '%'.$this->insertTagParser->replaceInline(\is_array($value) ? implode(' ', $value) : $value).'%');

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
                $value = array_filter(!\is_array($value) ? explode(',', $value) : $value);

                // if empty add an unfulfillable condition in order to avoid an sql error
                if (empty($value)) {
                    $where = $queryBuilder->expr()->eq(1, 2);
                } else {
                    $where = $queryBuilder->expr()->in($field, $wildcard);
                    $preparedValue = array_map(
                        function ($val) {
                            return addslashes($this->insertTagParser->replaceInline(trim($val), false));
                        },
                        $value
                    );
                    $queryBuilder->setParameter($wildcardParameterName, $preparedValue, ArrayParameterType::STRING);
                }

                break;

            case self::OPERATOR_NOT_IN:
                $value = array_filter(!\is_array($value) ? explode(',', $value) : $value);

                // if empty add an unfulfillable condition in order to avoid an sql error
                if (empty($value)) {
                    $where = $queryBuilder->expr()->eq(1, 2);
                } else {
                    $where = $queryBuilder->expr()->notIn($field, $wildcard);
                    $preparedValue = array_map(
                        function ($val) {
                            return addslashes($this->insertTagParser->replaceInline(trim($val), false));
                        },
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
                $where = $field.(self::OPERATOR_NOT_REGEXP == $operator ? ' NOT REGEXP ' : ' REGEXP ').$wildcard;

                if (\is_array($dca) && isset($dca['eval']['multiple']) && $dca['eval']['multiple']) {
                    // match a serialized blob
                    if (\is_array($value)) {
                        // build a regexp alternative, e.g. (:"1";|:"2";)
                        $queryBuilder->setParameter(
                            $wildcardParameterName,
                            '('.implode(
                                '|',
                                array_map(
                                    function ($val) {
                                        return ':"'.$this->insertTagParser->replaceInline($val).'";';
                                    },
                                    $value
                                )
                            ).')'
                        );
                    } else {
                        $queryBuilder->setParameter($wildcardParameterName, ':"'.$this->insertTagParser->replaceInline($value).'";');
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