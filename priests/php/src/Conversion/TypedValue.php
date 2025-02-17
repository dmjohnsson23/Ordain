<?php

namespace DMJohnson\Ordain\Conversion;

use DMJohnson\Ordain\Exceptions\OrdainException;
use DMJohnson\Ordain\Model\Type;

/**
 * A typed value is used as an intermediate representation of a value during type conversion.
 */
abstract class TypedValue{
    public function __construct(public readonly Type $type, public readonly mixed $value){
        
    }
}