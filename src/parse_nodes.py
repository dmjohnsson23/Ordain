from dataclasses import dataclass, field
from typing import List, Any

@dataclass
class Node:
    _str: str = field(init=False, repr=False)
    _pos: int = field(init=False, repr=False)
    def _parsedata(self, s, pos):
        self._str = s
        self._pos = pos
        return self
    def __str__(self):
        return self.dump()

@dataclass
class DocBlock(Node):
    """
    Documentation block, used to describe a struct or field
    """
    contents: str
    def dump(self, indent_level=0):
        return self.contents

@dataclass
class Tag(Node):
    name: str
    args: str
    def dump(self, indent_level=0):
        return f"{self.name} {self.args.strip()}"

@dataclass
class PrimativeType(Node):
    name: str
    def dump(self, indent_level=0):
        return self.name

@dataclass
class Identifier(Node):
    name: str
    def dump(self, indent_level=0):
        name=self.name.replace('`', '\\`')
        return f"`{name}`"

@dataclass
class Literal(Node):
    value: Any
    def dump(self, indent_level=0):
        return repr(self.value) # TODO make better

@dataclass
class LiteralString(Literal):
    value: str|bytes
    @property
    def is_binary(self):
        return not isinstance(self.value, str)

@dataclass
class LiteralInt(Literal):
    value: int

@dataclass
class LiteralFloat(Literal):
    value: float

@dataclass
class ListType(Node):
    type: InlineType

@dataclass
class ArrayType(Node):
    count: int
    type: InlineType

@dataclass
class MappingType(Node):
    key_type: PrimativeType
    value_type: InlineType

@dataclass
class ReferenceType(Node):
    type: Identifier
    store: Identifier

@dataclass
class BackreferenceType(Node):
    type: Identifier
    store: Identifier
    via: List[Identifier] = None

@dataclass
class StructFieldDef(Node):
    name: Identifier
    type: Node
    tags: List[Tag] = None
    docblock: DocBlock = None
    def dump(self, indent_level=0):
        indents = '    '*indent_level
        return indents + f'\n{indents}'.join(filter(None, [self.docblock, *self.tags, f"{self.name}: {self.type}"]))

#TODO finish dump funcs
@dataclass
class StructDef(Node):
    fields: List[StructFieldDef]

@dataclass
class EnumFieldDef(Node):
    name: Identifier
    value: Literal = None

@dataclass
class EnumDef(Node):
    type: PrimativeType
    fields: List[StructFieldDef]

@dataclass
class InlineType(Node):
    type: PrimativeType|StructDef|EnumDef|Identifier
    nullalbe: bool = False

@dataclass
class TypeDef(Node):
    name: Identifier
    type: PrimativeType|StructDef|EnumDef|Identifier
    tags: List[Tag] = None
    docblock: DocBlock = None