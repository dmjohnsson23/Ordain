<?php

namespace DMJohnson\Ordain\Conversion;

use DMJohnson\Ordain\Exceptions\OrdainException;

class Repr{
    /**
     * @var array<string,Repr> A registry of all repr types existing
     */
    private static array $registry;
    /**
     * @var array<string,callable(mixed):mixed> $dumpers A mapping of type strings to their conversion functions
     */
    private array $dumpers;
    /**
     * @var array<string,callable(mixed):mixed> $dumpers A mapping of type strings to their conversion functions
     */
    private array $loaders;

    /**
     * @internal
     *
     * While this constructor is public to enable anonymous subclasses, it is recommended to use 
     * the static `of` method whenever possible.
     */
    public function __construct(public readonly string $name){
        static::$registry[$name] = $this;
        $this->dumpers = [];
        $this->loaders = [];
    }

    public static function of(string $name): Repr{
        $repr = static::$registry[$name];
        if (!isset($repr)){
            $repr = static::$registry[$name] = new Repr($name);
        }
        return $repr;
    }

    /**
     * @param mixed $value The value to dump
     * @param string $toType The target type to convert into
     */
    public function dump($value, $toType){
        $func = $this->dumpers[$toType]; // TODO normalize equivilent types, handle nullable type, fallback to mixed
        if (!isset($func)){
            throw new OrdainException("Cannot dump {var_export($value)} to $toType; no dumper registered");
        }
        try{
            return $func($value);
        }
        catch (\Exception $e){
            throw new OrdainException("Cannot dump {var_export($value)} to $toType; ".$e->getMessage(), previous:$e);
        }
    }

    /**
     * @param mixed $value The value to load
     * @param string $toType The target type to convert into
     */
    public function load($value, $toType){
        $func = $this->loaders[$toType]; // TODO normalize equivilent types, handle nullable type, fallback to mixed
        if (!isset($func)){
            throw new OrdainException("Cannot load {var_export($value)} to $toType; no loader registered");
        }
        try{
            return $func($value);
        }
        catch (\Exception $e){
            throw new OrdainException("Cannot load {var_export($value)} to $toType; ".$e->getMessage(), previous:$e);
        }
    }

    /**
     * @param string $toType The target type to convert into
     * @param callable(mixed):mixed $dumper The function to execute to perform the conversion.
     */
    public function registerDumper($toType, $dumper){
        $this->dumpers[$toType] = $dumper;
        return $this;
    }

    /**
     * @param string $toType The target type to convert into
     * @param callable(mixed):mixed $loader The function to execute to perform the conversion.
     */
    public function registerLoader($toType, $loader){
        $this->loaders[$toType] = $loader;
        return $this;
    }

}


(new class ('html.sanitized') extends Repr{
    /** @var \Symfony\Component\HtmlSanitizer\HtmlSanitizer $sanitizer */
    public $sanitizer;
})
    ->registerDumper('string', function($value){
        if (!isset($sanitizer)){
            $sanitizer = new \Symfony\Component\HtmlSanitizer\HtmlSanitizer(
                (new \Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig())
                ->allowSafeElements()
                ->allowLinkSchemes(['http', 'https', 'mailto'])
                ->allowMediaSchemes(['http', 'https', 'data'])
                ->forceAttribute('a', 'rel', 'noopener noreferrer')
                ->dropAttribute('ping', '*')
                ->blockElement('form')
                ->dropElement('input')
                ->dropElement('button')
                ->dropElement('textarea')
                ->dropElement('select')
            );
        }
    })
    ->registerLoader('string', function($value){
        return $value;
    })
;

Repr::of('html.escaped') 
    ->registerDumper('string', function($value){
        return \htmlspecialchars($value);
    })
    ->registerLoader('string', function($value){
        return \htmlspecialchars_decode($value);
    })
;

Repr::of('php.serialized') 
    ->registerDumper('string', function($value){
        return \serialize($value);
    })
    ->registerLoader('mixed', function($value){
        return \unserialize($value);
    })
;

Repr::of('json') 
    // TODO prior to dumping, and after loading, additional transformation may be needed. I think 
    // this can be accomplished by stacking the `json` repr on top of a `json-compatible` repr
    ->registerDumper('string', function($value){
        $result = \json_encode($value);
        if ($result === false){
            throw new \RuntimeException(\json_last_error_msg(), \json_last_error());
        }
        return $result;
    })
    ->registerLoader('array', function($value){
        $result = \json_decode($value, true);
        if (\is_null($result) && \strtolower(\trim($value)) !== 'null'){
            throw new \RuntimeException(\json_last_error_msg(), \json_last_error());
        }
    })
    ->registerLoader('object', function($value){
        $result = \json_decode($value);
        if (\is_null($result) && \strtolower(\trim($value)) !== 'null'){
            throw new \RuntimeException(\json_last_error_msg(), \json_last_error());
        }
    })
;

