<?php
use \DMJohnson\Ordain\ArrayParser;
use DMJohnson\Ordain\Model\NamedTypeReference;
use DMJohnson\Ordain\Model\Tag;
use DMJohnson\Ordain\Model\Typedef;
use PHPUnit\Framework\TestCase;

class ArrayParserTest extends TestCase{
    function testSimpleAlias(){
        $model = ArrayParser::parse(['Age'=>['type'=>'int']]);
        $this->assertIsArray($model);
        $this->assertCount(1, $model);
        $this->assertArrayHasKey('Age', $model);
        $this->assertInstanceOf(Typedef::class, $model['Age']);
        $this->assertEquals('Age', $model['Age']->name);
        $this->assertInstanceOf(NamedTypeReference::class, $model['Age']->type);
        $this->assertEquals('int', $model['Age']->type->type);
        $this->assertNull($model['Age']->docs);
        $this->assertNull($model['Age']->tags);
        $this->assertNull($model['Age']->struct_fields);
    }
    function testSimpleAliasWithDocs(){
        $model = ArrayParser::parse(['Age'=>['type'=>'int', 'docs'=>'This is some documentation']]);
        $this->assertIsArray($model);
        $this->assertCount(1, $model);
        $this->assertArrayHasKey('Age', $model);
        $this->assertInstanceOf(Typedef::class, $model['Age']);
        $this->assertEquals('Age', $model['Age']->name);
        $this->assertInstanceOf(NamedTypeReference::class, $model['Age']->type);
        $this->assertEquals('int', $model['Age']->type->type);
        $this->assertEquals('This is some documentation', $model['Age']->docs);
        $this->assertNull($model['Age']->tags);
        $this->assertNull($model['Age']->struct_fields);
    }
    function testSimpleAliasWithBooleanTags(){
        $model = ArrayParser::parse(['Age'=>['type'=>'int', 'tags'=>['tag1', 'test.tag2']]]);
        $this->assertIsArray($model);
        $this->assertCount(1, $model);
        $this->assertArrayHasKey('Age', $model);
        $this->assertInstanceOf(Typedef::class, $model['Age']);
        $this->assertEquals('Age', $model['Age']->name);
        $this->assertInstanceOf(NamedTypeReference::class, $model['Age']->type);
        $this->assertEquals('int', $model['Age']->type->type);
        $this->assertNull($model['Age']->docs);
        $this->assertNull($model['Age']->struct_fields);
        $this->assertIsArray($model['Age']->tags, 'Tags must be present');
        $this->assertCount(2, $model['Age']->tags, 'Must have 2 tags');
        $this->assertInstanceOf(Tag::class, $model['Age']->tags[0]);
        $this->assertInstanceOf(Tag::class, $model['Age']->tags[1]);
        $this->assertNull($model['Age']->tags[0]->cannon);
        $this->assertEquals('test', $model['Age']->tags[1]->cannon);
        $this->assertEquals('tag1', $model['Age']->tags[0]->name);
        $this->assertEquals('tag2', $model['Age']->tags[1]->name);
        $this->assertTrue($model['Age']->tags[0]->value, 'Boolean tag has `true` value');
        $this->assertTrue($model['Age']->tags[1]->value, 'Boolean tag has `true` value');
    }
    function testSimpleAliasWithMappedTags(){
        $model = ArrayParser::parse(['Age'=>['type'=>'int', 'tags'=>['tag1'=>'thing1', 'test.tag2'=>'thing2']]]);
        $this->assertIsArray($model);
        $this->assertCount(1, $model);
        $this->assertArrayHasKey('Age', $model);
        $this->assertInstanceOf(Typedef::class, $model['Age']);
        $this->assertEquals('Age', $model['Age']->name);
        $this->assertInstanceOf(NamedTypeReference::class, $model['Age']->type);
        $this->assertEquals('int', $model['Age']->type->type);
        $this->assertNull($model['Age']->docs);
        $this->assertNull($model['Age']->struct_fields);
        $this->assertIsArray($model['Age']->tags, 'Tags must be present');
        $this->assertCount(2, $model['Age']->tags, 'Must have 2 tags');
        $this->assertInstanceOf(Tag::class, $model['Age']->tags[0]);
        $this->assertInstanceOf(Tag::class, $model['Age']->tags[1]);
        $this->assertNull($model['Age']->tags[0]->cannon);
        $this->assertEquals('test', $model['Age']->tags[1]->cannon);
        $this->assertEquals('tag1', $model['Age']->tags[0]->name);
        $this->assertEquals('tag2', $model['Age']->tags[1]->name);
        $this->assertEquals('thing1', $model['Age']->tags[0]->value);
        $this->assertEquals('thing2', $model['Age']->tags[1]->value);
    }
    function testSimpleAliasWithRepeatTags(){
        $model = ArrayParser::parse(['Age'=>['type'=>'int', 'tags'=>[['tag'=>'thing1'], ['tag'=>'thing2']]]]);
        $this->assertIsArray($model);
        $this->assertCount(1, $model);
        $this->assertArrayHasKey('Age', $model);
        $this->assertInstanceOf(Typedef::class, $model['Age']);
        $this->assertEquals('Age', $model['Age']->name);
        $this->assertInstanceOf(NamedTypeReference::class, $model['Age']->type);
        $this->assertEquals('int', $model['Age']->type->type);
        $this->assertNull($model['Age']->docs);
        $this->assertNull($model['Age']->struct_fields);
        $this->assertIsArray($model['Age']->tags, 'Tags must be present');
        $this->assertCount(2, $model['Age']->tags, 'Must have 2 tags');
        $this->assertInstanceOf(Tag::class, $model['Age']->tags[0]);
        $this->assertInstanceOf(Tag::class, $model['Age']->tags[1]);
        $this->assertNull($model['Age']->tags[0]->cannon);
        $this->assertNull($model['Age']->tags[1]->cannon);
        $this->assertEquals('tag', $model['Age']->tags[0]->name);
        $this->assertEquals('tag', $model['Age']->tags[1]->name);
        $this->assertEquals('thing1', $model['Age']->tags[0]->value);
        $this->assertEquals('thing2', $model['Age']->tags[1]->value);
    }
    function testStruct(){
        $model = ArrayParser::parse(['User'=>['type'=>'struct', 'fields'=>['name'=>['type'=>'string'],'password'=>['type'=>'string']]]]);
        $this->assertIsArray($model);
        $this->assertCount(1, $model);
        $this->assertArrayHasKey('User', $model);
        $this->assertInstanceOf(Typedef::class, $model['User']);
        $this->assertEquals('User', $model['User']->name);
        $this->assertInstanceOf(NamedTypeReference::class, $model['User']->type);
        $this->assertEquals('struct', $model['User']->type->type);
        $this->assertNull($model['User']->docs);
        $this->assertNull($model['User']->tags);
        $this->assertIsArray($model['User']->struct_fields, 'Struct must have fields');
        $this->assertCount(2, $model['User']->struct_fields);
        $this->assertArrayHasKey('name', $model['User']->struct_fields);
        $this->assertArrayHasKey('password', $model['User']->struct_fields);
        $this->assertInstanceOf(Typedef::class, $model['User']->struct_fields['name']);
        $this->assertInstanceOf(Typedef::class, $model['User']->struct_fields['password']);
        $this->assertEquals('User.name', $model['User']->struct_fields['name']->name);
        $this->assertEquals('User.password', $model['User']->struct_fields['password']->name);
        $this->assertEquals('string', $model['User']->struct_fields['name']->type->type);
        $this->assertEquals('string', $model['User']->struct_fields['password']->type->type);
    }
}