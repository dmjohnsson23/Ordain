from .exceptions import ParseException
from .model import *
from typing import Mapping, Tuple, Union, Optional

def parse_typedefs(typedef_dict: dict)->Mapping[str,Typedef]:
    """
    Build a model from an array, such as would be obtained by parsing a JSON or YAML definition.
    
    :raises ParseException: If the input is not well-formed
    """
    model = {}
    for key, value in typedef_dict.items():
        model[key] = parse_typedef(key, value, model, typedef_dict)
    return model


def parse_typedef(key, value, model, typedef_dict)->Typedef:
    """
    Build a typedef from an array
    
    :raises ParseException: If the input is not well-formed
    """
    if 'type' not in value:
        raise ParseException(f"Typedef {key} has no type")
    type_type, resolved_type_str, extends = preparse_type_string(value['type'], typedef_dict)
    type = parse_type(key, value, type_type, resolved_type_str, model, typedef_dict)
    if 'docs' in value:
        if not isinstance(value['docs'], str):
            raise ParseException("Typedef {key} has a non-string docs")
        docs = value['docs']
    else:
        docs = None
    if 'tags' in value:
        tags = parse_tags(key, value['tags'])
    else:
        tags = TagRepository([])
    return Typedef(key, type, tags, docs, extends)


def preparse_type_string(type, typedef_dict)->Tuple[type, str, Optional[str]]:
    if type in ScalarType:
        return ScalarType, type, None
    elif type == "struct":
        return StructType, type, None
    elif isinstance(type, str) and type in typedef_dict:
        parent_type = typedef_dict[type]['type']
        parent_preparse = preparse_type_string(parent_type, typedef_dict)
        return parent_preparse[0], parent_preparse[1], type
    # TODO parse inline type
    else:
        raise ParseException(f"Undefined type: {type}")
    

def parse_type(key, value, type_type, type_str, model, typedef_dict)->Type:
    """
    Parse a typedef string into an actual type. 
    
    Also triggers any special sub-parsing needed for that type.
    """
    if type_type is ScalarType:
        return ScalarType(type_str)
    elif type_type is StructType:
        fields = {}
        for field_key, field_value in value.get('fields', {}).items():
            fields[field_key] = parse_typedef(f"{key}.{field_key}", field_value, model, typedef_dict)
        return StructType(fields)
    else:
        raise ValueError(f'Unsupported type_type: {type_type}')


def parse_tags(typedef_key, tags:Union[list,dict])->TagRepository:
    """
    Build a list of tags from an array
    
    :raises ParseException: If the input is not well-formed
    """
    parsed = []
    if isinstance(tags, dict):
        for key, value in tags.items():
            parsed.append(Tag.from_key_value(key, value))
    else:
        for value in tags:
            if isinstance(value, str):
                # A plain string alone is a boolean flag
                parsed.append(Tag.from_key_value(value, True))
            elif isinstance(value, dict) and len(value) == 1:
                # A k-v pair as a dict
                parsed.append(Tag.from_key_value(*list(value.items())[0]))
            else:
                raise ParseException(f"Tag {repr(value)} in typedef {typedef_key} could not be parsed")
    return TagRepository(parsed)