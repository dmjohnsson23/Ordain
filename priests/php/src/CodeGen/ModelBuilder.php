<?php
namespace DMJohnson\Ordain\CodeGen;

use DMJohnson\Ordain\Exceptions\OrdainException;
use DMJohnson\Ordain\Model\{NamedTypeReference, ScalarType, Tag,Typedef};
use DMJohnson\Ordain\Seeker;
use PhpParser\{BuilderHelpers, Parser, ParserFactory, PrettyPrinter};
use PhpParser\Builder\Declaration;
use PhpParser\Comment\Doc;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Stmt\Return_;
use ValueError;

/**
 * A code generation too to build a model class in PHP based on an ordination typedef
 */
class ModelBuilder{
    protected BuilderFactory $make;
    protected Parser $parser;
    protected string $propMode = 'magic';
    protected $propNameTransform = null;
    protected bool $convertSql = false;
    protected ?string $toSqlMethodName = null;
    protected ?string $fromSqlMethodName = null;
    protected ?string $sqlDialect = null;
    protected bool $convertJson = false;
    protected ?string $toJsonMethodName = null;
    protected ?string $fromJsonMethodName = null;
    protected bool $convertHtml = false;
    protected ?string $toHtmlMethodName = null;
    protected ?string $fromHtmlMethodName = null;
    protected bool $convertPdo = false;
    protected ?string $fetchMethodName = null;
    protected ?string $insertMethodName = null;
    protected ?string $updateMethodName = null;

    public function __construct(protected Seeker $find){
        $this->make = new BuilderFactory();
        $this->parser = (new ParserFactory)->createForHostVersion();
    }

    public function fieldsAsGetterSetter($transformNames=null){
        $this->propMode = 'methods';
        if ($transformNames === 'camelCase'){
            $this->propNameTransform = fn($name) => 
                \ucFirst(\preg_replace_callback(
                    '/(?<!^)_([a-z0-9])/',
                    fn($groups) => \ucfirst($groups[1]), // Emulated substitution pattern: \u$1
                    $name
                ));
        }
        elseif ($transformNames === 'snake_case'){
            $this->propNameTransform = fn($name) => 
                strtolower(preg_replace(
                    '/(?<!^)([A-Z][a-z]|(?<=[a-z])[^a-z_]|(?<=[A-Z])[0-9_])/',
                    '_$1', // Emulated substitution pattern: _\L$1
                    $name
                ));
        }
        elseif (is_callable($transformNames)){
            $this->propNameTransform = $transformNames;
        }
        elseif (!is_null($transformNames)){
            throw new ValueError('Invalid value for transformNames: '.var_export($transformNames, true));
        }
        return $this;
    }

    public function fieldsAsRealProperties(){
        // Real properties will not work for many configurations, so we'll have to be smart about when/how to allow it
        throw new OrdainException('Real properties are not yet implemented');
        $this->propMode = 'real';
        return $this;
    }

    public function fieldsAsMagicProperties(){
        $this->propMode = 'magic';
        return $this;
    }

    public function fieldsAsPropertyHooks(){
        // Requires PHP 8.4+
        throw new OrdainException('Property hooks are not yet implemented');
        $this->propMode = 'hooks';
        return $this;
    }

    /**
     * Add methods to convert an object to and from an SQL array.
     */
    public function withConvertSql(bool $convert=true, ?string $dialect=null, string $toMethodName='toSqlArray', string $fromMethodName='fromSqlArray'){
        $this->convertSql = $convert;
        $this->sqlDialect = $dialect;
        $this->toSqlMethodName = $toMethodName;
        $this->fromSqlMethodName = $fromMethodName;
        return $this;
    }

    /**
     * Add methods to convert an object to and from a JSON array.
     */
    public function withConvertJson(bool $convert=true, string $toMethodName='toJson', string $fromMethodName='fromJson'){
        $this->convertJson = $convert;
        $this->toJsonMethodName = $toMethodName;
        $this->fromJsonMethodName = $fromMethodName;
        return $this;
    }

    /**
     * Add methods to convert an object to and from an HTML array. (e.g. from POST data and to and array of input value strings)
     */
    public function withConvertHtml(bool $convert=true, string $toMethodName='toHtmlArray', string $fromMethodName='fromHtmlArray'){
        $this->convertJson = $convert;
        $this->toHtmlMethodName = $toMethodName;
        $this->fromHtmlMethodName = $fromMethodName;
        return $this;
    }

    /**
     * Add methods to save and load an object from the database using PDO. Implies `convertSql`.
     */
    public function withConvertPdo(bool $convert=true, string $fetchMethodName='fetch', string $insertMethodName='insert', string $updateMethodName='update'){
        $this->convertPdo = $convert;
        $this->fetchMethodName = $fetchMethodName;
        $this->insertMethodName = $insertMethodName;
        $this->updateMethodName = $updateMethodName;
        if (!$this->convertSql) $this->withConvertSql();
        return $this;
    }

    public function buildClassForStruct(Typedef $struct): string{
        if (!$struct->type instanceof ScalarType || $struct->type->type !== 'struct'){
            throw new OrdainException('buildClassForStruct can only generate model classes for structs');
        }
        // Create the class members
        [$namespace, $className] = $this->parseClassName($struct);
        $methods = [];
        $props = [];
        $attrs = [];
        $doc = $struct->docs ?? '';
        $this->makeProps($struct, $doc, $props, $methods);
        $this->makeStructAttrs($struct, $attrs);
        // Build the full source code
        $container = $namespace ? $this->make->namespace($namespace) : $this->make->block();
        $node = $container
            ->addStmt($this->make->comment(HEADER_COMMENT_TEXT))
            ->addStmt($this->make->use('Some\Other\Thingy'))
            ->addStmt($this->makeClass($className, $doc, $methods, $props, $attrs))
            ->getNode()
        ;
        $stmts = $namespace ? array($node) : $node->stmts;
        $prettyPrinter = new PrettyPrinter\Standard();
        $code = $prettyPrinter->prettyPrintFile($stmts);
        return $code;
    }

    protected function makeClass(string $className, string $doc, array $methods, array $props, array $attrs){
        $class = $this->make->class($className);
        $class->setDocComment(Utils::formatDocComment($doc));
        foreach ($props as $prop){
            $class->addStmt($prop);
        }
        foreach ($methods as $method){
            $class->addStmt($method);
        }
        foreach ($attrs as $attr){
            $class->addAttribute($attr);
        }
        return $class;
    }

    protected function parseClassName(Typedef $typedef){
        $name = $this->find->phpNameFor($typedef);
        $nameParts = \explode('\\', $name);
        $className = \array_pop($nameParts);
        $namespace = \implode('\\', $nameParts);
        return [$namespace, $className];
    }

    protected function parsePropName(Typedef $typedef){
        $tag = $typedef->tags->filter('name', ['php'])->getTop();
        if (is_null($tag)) return \array_pop(\explode('.', $typedef->name));
        else return $tag->value;
    }

    protected function parsePropReadonly(Typedef $typedef){
        $tag = $typedef->tags->filter('readonly', ['php'])->getTop();
        if (is_null($tag)) return false;
        else return $tag->value;
    }

    protected function parsePropType(Typedef $typedef){
        return 'mixed'; // TODO
    }

    protected function makeStructAttrs(Typedef $struct, array &$attrs){
        $tags = $struct->tags->filter('attr', ['php'], false)->sort();
        foreach ($tags as $tag){
            // TODO enable attr to have params
            $attrs[] = $this->make->attribute($tag->value);
        }
    }

    protected function makeProps(Typedef $struct, string &$doc, array &$props, array &$methods){
        foreach ($struct->struct_fields as $field){
            switch ($this->propMode){
                case 'magic':
                    $this->makePropMagic($field, $doc, $props, $methods, $getSwitch, $setSwitch);
                    break;
                case 'methods':
                    $this->makePropGetterSetter($field, $props, $methods);
                    break;
            }
        }
        // Add magic methods if needed
        if (isset($getSwitch)){
            $methods['__get'] = $this->make->method('__get')
                ->addParam($this->make->param('name'))
                ->addStmt($getSwitch)
            ;
        }
        if (isset($setSwitch)){
            $methods['__set'] = $this->make->method('__set')
                ->addParam($this->make->param('name'))
                ->addParam($this->make->param('value'))
                ->addStmt($setSwitch)
            ;
        }
    }

    protected function makePropGetterSetter(Typedef $prop, array &$props, array &$methods){
        $name = $this->parsePropName($prop);
        $phpType = $this->parsePropType($prop);
        // Backer property
        $backer = "_{$name}_backer";
        $props[$backer] = $this->make->property($backer)->makePrivate();
        // Transform name for getter/setter methods
        $paramName = $name;
        if (isset($this->propNameTransform)){
            $name = \call_user_func($this->propNameTransform, $name);
        }
        // Constructor param
        if (isset($methods['__construct'])){
            $method = $methods['__construct'];
        }
        else{
            $method = $methods['__construct'] = $this->make->method('__construct');
        }
        $param = $this->make->param($paramName);
        // TODO default value for param
        $method->addParam($param);
        $this->makePropSetterBody($prop, $method, $backer, $paramName);
        // getter
        $method = $this->make->method('get'.$name);
        $this->makePropGetterBody($prop, $method, $backer);
        $method->setDocComment(Utils::formatDocComment(($prop->docs ?? '')."\n\n @return $phpType"));
        $methods['get'.$name] = $method;
        // setter
        if (!$this->parsePropReadonly($prop)){
            $paramName = 'value';
            $method = $this->make->method('set'.$name);
            $method->addParam($this->make->param($paramName));
            $this->makePropSetterBody($prop, $method, $backer, $paramName);
            $method->setDocComment(Utils::formatDocComment(($prop->docs ?? '')."\n\n @param $phpType \$$paramName"));
            $methods['set'.$name] = $method;
        }
    }
    
    protected function makePropMagic(Typedef $prop, string &$doc, array &$props, array &$methods, ?Builder\Switch_ &$getSwitch, ?Builder\Switch_ &$setSwitch){
        $name = $this->parsePropName($prop);
        $phpType = $this->parsePropType($prop);
        // Backer property
        $backer = "_{$name}_backer";
        $props[$backer] = $this->make->property($backer)->makePrivate();
        // Constructor param
        if (isset($methods['__construct'])){
            $method = $methods['__construct'];
        }
        else{
            $method = $methods['__construct'] = $this->make->method('__construct');
        }
        $paramName = $name;
        $param = $this->make->param($paramName);
        // TODO default value for param
        $method->addParam($param);
        $this->makePropSetterBody($prop, $method, $backer, $paramName);
        // getter
        if (\is_null($getSwitch)){
            $getSwitch = $this->make->switch($this->make->var('name'));
        }
        $getSwitch->case($name);
        $this->makePropGetterBody($prop, $getSwitch, $backer);
        // setter
        if (!$this->parsePropReadonly($prop)){
            if (\is_null($setSwitch)){
                $setSwitch = $this->make->switch($this->make->var('name'));
            }
            $setSwitch->case($name);
            $paramName = 'value';
            $this->makePropSetterBody($prop, $setSwitch, $backer, $paramName);
        }
        // docstring
        $doc .= \PHP_EOL.\PHP_EOL."@property $phpType \$$name {$prop->docs}";
    }

    protected function makePropSetterBody(Typedef $prop, &$parentRef, $backerName, $paramName='value'){
        $codeTags = $this->find->findAndFilterTags($prop, 'set', ['php'], false);
        foreach ($codeTags as $tag){
            $this->applyCodeTag($tag, $parentRef, $paramName);
        }
        $parentRef->addStmt(new Assign($this->make->propertyFetch($this->make->var('this'), $backerName), $this->make->var($paramName)));
    }
    
    protected function makePropGetterBody(Typedef $prop, &$parentRef, $backerName){
        $parentRef->addStmt(new Assign($this->make->var('value'), $this->make->propertyFetch($this->make->var('this'), $backerName)));
        $codeTags = $this->find->findAndFilterTags($prop, 'get', ['php'], false);
        foreach ($codeTags as $tag){
            $this->applyCodeTag($tag, $parentRef, 'value');
        }
        $parentRef->addStmt(new Return_($this->make->var('value')));
    }

    protected function applyCodeTag(Tag $tag, &$parentRef, $varName){
        $code = $this->parser->parse('<?php '.$tag->value);
        // wrap the code in a closure
        // TODO inspect the code to see if it can be "unrolled" and embedded rather than using a closure
        $code = new Closure([
            'params'=>[$this->make->param('value')->getNode()],
            'stmts'=>$code,
        ]);
        // Call the closure
        $code = $this->make->funcCall('\call_user_func', [$code, $this->make->var($varName)]);
        // Assign the value back to the var
        $code = new Assign($this->make->var($varName), $code);
        // Add the code
        if ($parentRef instanceof Declaration){
            $parentRef->addStmt($code);
        }
        elseif (\is_array($parentRef)){
            $parentRef[] = BuilderHelpers::normalizeStmt($code);
        }
        else{
            throw new ValueError('Cannot add code to '.var_export($parentRef, true));
        }
    }

    protected function makeConvertSql(Typedef $struct, string &$doc, array &$props, array &$methods){

    }

    protected function makeConvertJson(Typedef $struct, string &$doc, array &$props, array &$methods){

    }

    protected function makeConvertHtml(Typedef $struct, string &$doc, array &$props, array &$methods){

    }

    protected function makeConvertPdo(Typedef $struct, string &$doc, array &$props, array &$methods){

    }

}

define('HEADER_COMMENT_TEXT', "
/*******************************************************************************
 *      This file contains generated code. Do not edit this code directly!     *
 ******************************************************************************/
");