<?php 
namespace DMJohnson\Ordain\Conversion\TypedValue;

use DMJohnson\Ordain\Conversion\TypedValue;
use DMJohnson\Ordain\Model\ScalarType;

class FloatValue extends TypedValue{
    public readonly float $value;

    public function __construct(float $value){
        parent::__construct(new ScalarType('float'), $value);
    }
}