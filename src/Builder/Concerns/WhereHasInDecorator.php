<?php

namespace Luminee\Eloquent\Builder\Concerns;

use Closure;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\Relation;

trait WhereHasInDecorator
{
    /**
     * Is use `exists` (default)
     *
     * @var bool
     */
    protected $useExists = false;

    /**
     * Is use `in` (Mutually exclusive with `exists`)
     *
     * @var bool
     */
    protected $useIn = false;

    /**
     * Add a relationship count / exists condition to the query with where clauses.
     *
     * @param string $relation
     * @param Closure|null $callback
     * @param string $operator
     * @param int $count
     * @return Builder|static
     */
    public function whereHasIn(
        string $relation,
        Closure $callback = null,
        string $operator = '>=',
        int $count = 1
    ) {
        $this->useIn = true;

        return $this->has($relation, $operator, $count, 'and', $callback);
    }

    public function whereHasNotIn(string $relation, Closure $callback = null)
    {
        return $this->whereHasIn($relation, $callback, '<');
    }

    /**
     * Add nested relationship count / exists conditions to the query.
     *
     * Sets up recursive call to whereHas until we finish the nested relation.
     *
     * @param string $relations
     * @param string $operator
     * @param int $count
     * @param string $boolean
     * @param Closure|null $callback
     * @return Builder|static
     */
    protected function hasNested($relations, $operator = '>=', $count = 1, $boolean = 'and', $callback = null)
    {
        $relations = explode('.', $relations);

        $closure = function ($q) use (&$closure, &$relations, $operator, $count, $callback) {
            // In order to nest "has", we need to add count relation constraints on the
            // callback Closure. We'll do this by simply passing the Closure its own
            // reference to itself so it calls itself recursively on each segment.
            $q->useIn = $this->useIn;

            count($relations) > 1
                ? $q->whereHas(array_shift($relations), $closure)
                : $q->has(array_shift($relations), $operator, $count, 'and', $callback);
        };

        return $this->has(array_shift($relations), '>=', 1, $boolean, $closure);
    }

    /**
     * @param Builder $hasQuery
     * @param Relation $relation
     * @param $operator
     * @param $count
     * @param $boolean
     * @return Builder
     * @throws Exception
     */
    protected function addHasWhere(Builder $hasQuery, Relation $relation, $operator, $count, $boolean): Builder
    {
        $hasQuery->mergeConstraintsFrom($relation->getQuery());

        if (!$this->canUseExistsForExistenceCheck($operator, $count)) {
            return $this->addWhereCountQuery($hasQuery->toBase(), $operator, $count, $boolean);
        }

        if (!$this->useExists && ($this->useIn ||
            $relation->getParent()->isUseIn() ?? false)) {
            unset($hasQuery->getQuery()->wheres[0]);
            return $this->whereIn($this->getWhereInKey($relation),
                $hasQuery->select($this->getWhereInSubKey($relation))->toBase(),
                $boolean, $operator === '<' && $count === 1);
        }

        return $this->addWhereExistsQuery($hasQuery->toBase(), $boolean, $operator === '<' && $count === 1);
    }

    /**
     * @param Relation $relation
     * @return string
     * @throws Exception
     */
    protected function getWhereInKey(Relation $relation): string
    {
        if ($relation instanceof BelongsTo) {
            return $relation->getForeignKey();
        }
        if ($relation instanceof HasOneOrMany) {
            return $relation->getQualifiedParentKeyName();
        }

        throw new Exception('Unknown relation type');
    }

    /**
     * @param Relation $relation
     * @return string
     * @throws Exception
     */
    protected function getWhereInSubKey(Relation $relation): string
    {
        if ($relation instanceof BelongsTo) {
            return $relation->getOwnerKey();
        }
        if ($relation instanceof HasOneOrMany) {
            return $relation->getQualifiedForeignKeyName();
        }

        throw new Exception('Unknown relation type');
    }

}