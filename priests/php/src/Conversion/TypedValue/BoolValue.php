<?php 
namespace DMJohnson\Ordain\Conversion\TypedValue;

use DMJohnson\Ordain\Conversion\TypedValue;
use DMJohnson\Ordain\Model\ScalarType;

class BoolValue extends TypedValue{
    public readonly bool $value;

    public function __construct(bool $value){
        parent::__construct(new ScalarType('bool'), $value);
    }
}