<?php

use DMJohnson\Ordain\Model\{NamedTypeReference, ScalarType, Typedef,TagRepository,Tag};
use \DMJohnson\Ordain\Seeker;
use PHPUnit\Framework\TestCase;

class SeekerTest extends TestCase{
    function testResolveTypedefOnce(){
        $model = [
            'Thing'=>new Typedef(
                'Thing',
                new ScalarType('int'),
                null,
                new TagRepository([]),
                null
            )
        ];
        $seeker = new Seeker($model);
        $result = $seeker->resolveTypedef('Thing');
        $this->assertSame($model['Thing'], $result);
    }

    function testResolveTypedefTwice(){
        $model = [
            'Parent'=>new Typedef(
                'Parent',
                new ScalarType('int'),
                null,
                new TagRepository([]),
                null
            ),
            'Child'=>new Typedef(
                'Child',
                new NamedTypeReference('Parent'),
                null,
                new TagRepository([]),
                null
            )
        ];
        $seeker = new Seeker($model);
        $result = $seeker->resolveTypedef('Child');
        $this->assertNotNull($result);
        $this->assertEquals('Child', $result->name);
        $this->assertInstanceOf(ScalarType::class, $result->type);
        $this->assertEquals('int', $result->type->type);
    }

    function testResolveTypedefInheritedDocs(){
        $model = [
            'Parent'=>new Typedef(
                'Parent',
                new ScalarType('int'),
                'These are the parent docs',
                new TagRepository([]),
                null
            ),
            'Child'=>new Typedef(
                'Child',
                new NamedTypeReference('Parent'),
                null,
                new TagRepository([]),
                null
            )
        ];
        $seeker = new Seeker($model);
        $result = $seeker->resolveTypedef('Child');
        $this->assertNotNull($result);
        $this->assertEquals('These are the parent docs', $result->docs);
    }

    function testResolveTypedefOverwriteDocs(){
        $model = [
            'Parent'=>new Typedef(
                'Parent',
                new ScalarType('int'),
                'These are the parent docs',
                new TagRepository([]),
                null
            ),
            'Child'=>new Typedef(
                'Child',
                new NamedTypeReference('Parent'),
                'These are the child docs',
                new TagRepository([]),
                null
            )
        ];
        $seeker = new Seeker($model);
        $result = $seeker->resolveTypedef('Child');
        $this->assertNotNull($result);
        $this->assertEquals('These are the child docs', $result->docs);
    }

    function testResolveTypedefInheritTags(){
        $t1 = new Tag(null, 'parent', true);
        $t2 = new Tag(null, 'child', true);
        $model = [
            'Parent'=>new Typedef(
                'Parent',
                new ScalarType('int'),
                null,
                new TagRepository([$t1]),
                null
            ),
            'Child'=>new Typedef(
                'Child',
                new NamedTypeReference('Parent'),
                null,
                new TagRepository([$t2]),
                null
            )
        ];
        $seeker = new Seeker($model);
        $result = $seeker->resolveTypedef('Child');
        $this->assertNotNull($result);
        $this->assertSame($t1, $result->tags[0], 'Parent tags are considered as defined first');
        $this->assertSame($t2, $result->tags[1], 'Child tags are considered as defined last');
    }
}