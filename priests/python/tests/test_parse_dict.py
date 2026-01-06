from ordain.model import *
from ordain.parse_dict import parse_typedefs


def test_simple_alias():
    model = parse_typedefs({'Age': {'type': 'int'}})
    assert isinstance(model, dict)
    assert len(model) == 1
    assert 'Age' in model
    assert isinstance(model['Age'], Typedef)
    assert model['Age'].name == 'Age'
    assert isinstance(model['Age'].type, ScalarType)
    assert model['Age'].type is ScalarType.int
    assert model['Age'].docs is None
    assert len(model['Age'].tags) == 0
    

def test_simple_alias_with_docs():
    model = parse_typedefs({'Age': {'type': 'int', 'docs': 'This is some documentation'}})
    assert isinstance(model, dict)
    assert len(model) == 1
    assert 'Age' in model
    assert isinstance(model['Age'], Typedef)
    assert model['Age'].name == 'Age'
    assert isinstance(model['Age'].type, ScalarType)
    assert model['Age'].type is ScalarType.int
    assert model['Age'].docs == 'This is some documentation'
    assert len(model['Age'].tags) == 0
    

def test_simple_alias_with_boolean_tags():
    model = parse_typedefs({'Age': {'type': 'int', 'tags': ['tag1', 'test.tag2']}})
    assert isinstance(model, dict)
    assert len(model) == 1
    assert 'Age' in model
    assert isinstance(model['Age'], Typedef)
    assert model['Age'].name == 'Age'
    assert isinstance(model['Age'].type, ScalarType)
    assert model['Age'].type is ScalarType.int
    assert model['Age'].docs is None
    assert len(model['Age'].tags) == 2
    assert isinstance(model['Age'].tags[0], Tag)
    assert isinstance(model['Age'].tags[1], Tag)
    assert model['Age'].tags[0].cannon is None
    assert model['Age'].tags[1].cannon == 'test'
    assert model['Age'].tags[0].name == 'tag1'
    assert model['Age'].tags[1].name == 'tag2'
    assert model['Age'].tags[0].value is True, 'Boolean tag has `true` value'
    assert model['Age'].tags[1].value is True, 'Boolean tag has `true` value'


def test_simple_alias_with_mapped_tags():
    model = parse_typedefs({'Age': {'type': 'int', 'tags': {'tag1': 'thing1', 'test.tag2': 'thing2'}}})
    assert isinstance(model, dict)
    assert len(model) == 1
    assert 'Age' in model
    assert isinstance(model['Age'], Typedef)
    assert model['Age'].name == 'Age'
    assert isinstance(model['Age'].type, ScalarType)
    assert model['Age'].type is ScalarType.int
    assert model['Age'].docs is None
    assert len(model['Age'].tags) == 2
    assert isinstance(model['Age'].tags[0], Tag)
    assert isinstance(model['Age'].tags[1], Tag)
    assert model['Age'].tags[0].cannon is None
    assert model['Age'].tags[1].cannon == 'test'
    assert model['Age'].tags[0].name == 'tag1'
    assert model['Age'].tags[1].name == 'tag2'
    assert model['Age'].tags[0].value == 'thing1'
    assert model['Age'].tags[1].value == 'thing2'


def test_simple_alias_with_repeat_tags():
    model = parse_typedefs({'Age': {'type': 'int', 'tags': [{'tag': 'thing1'}, {'tag': 'thing2'}]}})
    assert isinstance(model, dict)
    assert len(model) == 1
    assert 'Age' in model
    assert isinstance(model['Age'], Typedef)
    assert model['Age'].name == 'Age'
    assert isinstance(model['Age'].type, ScalarType)
    assert model['Age'].type is ScalarType.int
    assert model['Age'].docs is None
    assert len(model['Age'].tags) == 2
    assert isinstance(model['Age'].tags[0], Tag)
    assert isinstance(model['Age'].tags[1], Tag)
    assert model['Age'].tags[0].cannon is None
    assert model['Age'].tags[1].cannon is None
    assert model['Age'].tags[0].name == 'tag'
    assert model['Age'].tags[1].name == 'tag'
    assert model['Age'].tags[0].value == 'thing1'
    assert model['Age'].tags[1].value == 'thing2'


def test_struct():
    model = parse_typedefs({'User': {'type': 'struct', 'fields': {'name': {'type': 'string'},'password': {'type': 'string'}}}})
    assert isinstance(model, dict)
    assert len(model) == 1
    assert 'User' in model
    assert isinstance(model['User'], Typedef)
    assert model['User'].name == 'User'
    assert isinstance(model['User'].type, StructType)
    assert model['User'].docs is None
    assert len(model['User'].tags) == 0
    assert model['User'].struct_fields is not None, 'Struct must have fields'
    assert len(model['User'].struct_fields) == 2
    assert 'name' in model['User'].struct_fields
    assert 'password' in model['User'].struct_fields
    assert isinstance(model['User'].struct_fields['name'], Typedef)
    assert isinstance(model['User'].struct_fields['password'], Typedef)
    assert model['User'].struct_fields['name'].name == 'User.name'
    assert model['User'].struct_fields['password'].name == 'User.password'
    assert model['User'].struct_fields['name'].type is ScalarType.string
    assert model['User'].struct_fields['password'].type is ScalarType.string


def test_inheritance():
    model = parse_typedefs({
        'Parent': {'type': 'struct', 'fields': {'parent_field': {'type': 'string'}}},
        'Child': {'type': 'Parent', 'fields': {'child_field': {'type': 'string'}}},
    })
    assert isinstance(model, dict)
    assert len(model) == 2
    assert 'Parent' in model
    assert 'Child' in model
    assert isinstance(model['Parent'], Typedef)
    assert isinstance(model['Child'], Typedef)
    assert isinstance(model['Parent'].type, StructType)
    assert isinstance(model['Child'].type, StructType)
    assert model['Parent'].parent is None
    assert model['Child'].parent == 'Parent'


def test_multi_inheritance():
    model = parse_typedefs({
        'Grandparent': {'type': 'struct', 'fields': {'grandparent_field': {'type': 'string'}}},
        'Parent': {'type': 'Grandparent', 'fields': {'parent_field': {'type': 'string'}}},
        'Child': {'type': 'Parent', 'fields': {'child_field': {'type': 'string'}}},
    })
    assert isinstance(model, dict)
    assert len(model) == 3
    assert 'Grandparent' in model
    assert 'Parent' in model
    assert 'Child' in model
    assert isinstance(model['Grandparent'], Typedef)
    assert isinstance(model['Parent'], Typedef)
    assert isinstance(model['Child'], Typedef)
    assert isinstance(model['Grandparent'].type, StructType)
    assert isinstance(model['Parent'].type, StructType)
    assert isinstance(model['Child'].type, StructType)
    assert model['Grandparent'].parent is None
    assert model['Parent'].parent == 'Grandparent'
    assert model['Child'].parent == 'Parent'