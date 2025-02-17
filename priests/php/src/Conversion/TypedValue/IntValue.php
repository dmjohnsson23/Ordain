<?php 
namespace DMJohnson\Ordain\Conversion\TypedValue;

use DMJohnson\Ordain\Conversion\TypedValue;
use DMJohnson\Ordain\Model\ScalarType;

class IntValue extends TypedValue{
    public readonly int $value;

    public function __construct(int $value){
        parent::__construct(new ScalarType('int'), $value);
    }
}