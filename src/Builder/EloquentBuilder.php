<?php

namespace Luminee\Eloquent\Builder;

use Illuminate\Database\Eloquent\Builder;
use Luminee\Eloquent\Builder\Concerns\WhereHasInDecorator;

class EloquentBuilder extends Builder
{
    use WhereHasInDecorator;
}