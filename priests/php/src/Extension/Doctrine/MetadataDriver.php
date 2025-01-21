<?php

namespace DMJohnson\Ordain\Extension\Doctrine;

use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use DMJohnson\Ordain\Seeker;
use DMJohnson\Ordain\Model\{Typedef,ScalarType};

class MetadataDriver implements MappingDriver{
    private array $classMap;
    function __construct(private Seeker $seeker){
        $this->classMap = [];
        foreach ($this->seeker->getAllNamedTypedefs() as $typedef){
            $this->classMap[$this->seeker->phpNameFor($typedef)] = $typedef;
        }
        return $this->classMap;
    }

    public function getAllClassNames(): array{
        return \array_keys($this->classMap);
    }

    public function loadMetadataForClass(string $className, ClassMetadata $metadata): void{
        $typedef = $this->seeker->resolveTypedef($this->classMap[$className]);
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable($this->seeker->sqlNameFor($typedef));
        foreach ($typedef->struct_fields as $field){
            $resolvedField = $this->seeker->resolveTypedef($field);
            $fieldBuilder = $builder->createField(
                $this->seeker->phpNameFor($resolvedField),
                $this->resolveDoctrineType($resolvedField),
                )
                ->columnName($this->seeker->sqlNameFor($resolvedField))
            ;
            $idTag = $resolvedField->tags->filter('id', ['sql'])->getTop();
            if (isset($idTag) && $idTag->value){
                $fieldBuilder->makePrimaryKey();
            }
            $requiredTag = $resolvedField->tags->filter('required', ['sql'])->getTop();
            if (isset($requiredTag)){
                $fieldBuilder->nullable(!$requiredTag->value);
            }
            else{
                $fieldBuilder->nullable();
            }
            $sqlTypeTag = $resolvedField->tags->filter('type', ['sql'], false)->getTop();
            if (isset($sqlTypeTag)){
                // TODO include other parts of the definition, not just the type
                $fieldBuilder->columnDefinition($sqlTypeTag->value);
            }
            $fieldBuilder->build();
        }
    }

    public function isTransient(string $className): bool{
        // All types in Ordain are currently concrete; abstract types don't exist yet
        return false;
    }

    protected function resolveDoctrineType(Typedef $field): string{
        // Get the Doctrine type, native PHP type, or Ordain type (preferred in that order)
        // Get the SQL type and repr, if applicable
        // If there is an SQL type or repr, we must alway use a hook type
        // If we already have a Doctrine type, use that
        // If we have a PHP or Ordain type, check if it can be converted to a Doctrine type
        // If all else fails, use a hook type




        $phpTypeTag = $field->tags->filter('type', ['php.doctrine', 'php'])->getTop(['php.doctrine'], ['php']);
        $sqlTypeTag = $field->tags->filter('type', ['sql'], false)->getTop();
        $sqlReprTag = $field->tags->filter('repr', ['sql'])->getTop();
        $ordainType = $field->type;
        // Do we have a doctrine type already specified?
        if ($phpTypeTag->cannon === 'php.doctrine'){
            $doctrineType = $phpTypeTag->value;
        }
        // Is there a PHP type? Map it to a doctrine type
        elseif (isset($phpTypeTag)){
            // Look for a mapped type
            if (\array_key_exists($phpTypeTag->value, $this->classMap)){
                $doctrineType = $phpTypeTag->value;
            }
            // Map PHP's built-in types to doctrine types
            else switch($phpTypeTag->value){
                case 'integer':
                    $doctrineType = 'integer';
                    break;
                case 'float':
                    $doctrineType = 'float';
                    break;
                case 'string':
                    $doctrineType = 'string';
                    break;
                case 'boolean':
                    $doctrineType = 'boolean';
                    break;
                case 'DateTime':
                case '\DateTime':
                    $doctrineType = 'datetimez';
                    break;
                case 'DateTimeImmutable':
                case '\DateTimeImmutable':
                    $doctrineType = 'datetimez_immutable';
                    break;
            }
        }
        // Is the Ordain type is a primitive (scalar) type?
        elseif ($ordainType instanceof ScalarType){
            // Map Ordain's scalar types to doctrine types
            switch ($ordainType->type){
                case 'binary':
                    $doctrineType = 'binary';
                    break;
                case 'bool':
                    $doctrineType = 'boolean';
                    break;
                case 'date':
                    $doctrineType = 'date_immutable';
                    break;
                case 'datetime':
                    $doctrineType = 'datetimez_immutable';
                    break;
                case 'decimal':
                    $doctrineType = 'decimal';
                    break;
                case 'float':
                    $doctrineType = 'float';
                    break;
                case 'int':
                    $doctrineType = 'integer';
                    break;
                case 'string':
                    $doctrineType = 'string';
                    break;
                case 'time':
                    $doctrineType = 'time_immutable';
                    break;
            }
        }
    }
}