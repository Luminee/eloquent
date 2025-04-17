<?php

namespace Luminee\Eloquent\Model\Concerns;

trait UseInDecorator
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
     * Use `in` and disable `exists`
     *
     * @return void
     */
    public function useIn(): void
    {
        $this->useExists = false;

        $this->useIn = true;
    }

    public function isUseIn(): bool
    {
        return $this->useIn;
    }
}