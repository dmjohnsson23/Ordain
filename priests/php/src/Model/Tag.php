<?php
namespace DMJohnson\Ordain\Model;

class Tag{
    function __construct(
        public readonly ?string $cannon,
        public readonly string $name,
        public readonly mixed $value,
    ){}

    /** 
     * Alternate constructor 
     * 
     * @return static
     */
    static function fromKeyValue(string $key, $value){
        $dot = \strrpos($key, '.');
        if ($dot === false) return new static(null, $key, $value);
        return new static(
            \substr($key, 0, $dot),
            \substr($key, $dot+1),
            $value,
        );
    }
}