import pyparsing as p
import parse_nodes as n
from ast import literal_eval

COMMENT = p.Suppress((p.Literal('//') - p.rest_of_line) | p.Literal('/*') + p.NotAny('*') - p.SkipTo(p.Literal('*/')) - p.Literal('*/'))
DOCBLOCK = p.Literal('/**') - p.SkipTo(p.Literal('*/')) - p.Literal('*/')
@DOCBLOCK.set_parse_action
def parse_docblock(s, pos, tokens):
    return n.DocBlock(''.join(tokens))._parsedata(s, pos)
IDENTIFIER = p.common.identifier | p.QuotedString('`', esc_char='\\')
@IDENTIFIER.set_parse_action
def parse_identifier(s, pos, tokens):
    return n.Identifier(tokens[0])._parsedata(s, pos)
PRIMITIVE_TYPE = p.Keyword('int') | p.Keyword('float') | p.Keyword('byte') | p.Keyword('string') | p.Keyword('binary') | p.Keyword('bool') | p.Keyword('date') | p.Keyword('datetime')
@PRIMITIVE_TYPE.set_parse_action
def parse_primative_type(s, pos, tokens):
    return n.PrimativeType(tokens[0])._parsedata(s, pos)
LITERAL_STRING = p.python_quoted_string
@LITERAL_STRING.set_parse_action
def parse_literal_string(s, pos, tokens):
    # Hijack python's string parsing
    # Single-quotes are bytes, double-quotes are str
    if tokens[0][0] == "'":
        return n.LiteralString(literal_eval(f"b{tokens[0]}"))._parsedata(s, pos)
    else:
        return n.LiteralString(literal_eval(tokens[0]))._parsedata(s, pos)
LITERAL_INT = (p.Suppress('0x') + p.common.hex_integer) | (p.Suppress('0b') + p.Word('10').set_parse_action(lambda bits: int(bites, base=2))) | p.common.signed_integer
@LITERAL_INT.set_parse_action
def parse_literal_int(s, pos, tokens):
    return n.LiteralInt(tokens[0])._parsedata(s, pos)
LITERAL_FLOAT = p.common.fnumber
@LITERAL_FLOAT.set_parse_action
def parse_literal_float(s, pos, tokens):
    return n.LiteralFloat(tokens[0])._parsedata(s, pos)
LITERAL_VALUE = LITERAL_INT | LITERAL_FLOAT | LITERAL_STRING


TAG = p.Combine(p.Literal('#') - p.Word(p.identbodychars + '-.')) - (p.original_text_for(p.nested_expr()) | p.rest_of_line)
@TAG.set_parse_action
def parse_tag(s, pos, tokens):
    return n.Tag(*tokens)._parsedata(s, pos)


INLINE_TYPE = p.Forward()
REFERENCE_TYPE = p.Suppress('&') - IDENTIFIER - p.Keyword('from').suppress() - IDENTIFIER
@REFERENCE_TYPE.set_parse_action
def parse_reference_type(s, pos, tokens):
    return n.ReferenceType(*tokens)._parsedata(s, pos)
BACKREFERENCE_TARGET = IDENTIFIER + p.Opt(p.Keyword('via').suppress() - p.Group(IDENTIFIER - p.ZeroOrMore(p.Suppress('.')-IDENTIFIER)))
BACKREFERENCE_TYPE = p.Suppress('*') - IDENTIFIER - p.Keyword('from').suppress - BACKREFERENCE_TARGET
@BACKREFERENCE_TYPE.set_parse_action
def parse_backreference_type(s, pos, tokens):
    return n.ReferenceType(*tokens)._parsedata(s, pos)
LIST_TYPE = p.Suppress(p.Keyword('list') - p.Keyword('of')) - INLINE_TYPE
@LIST_TYPE.set_parse_action
def parse_list_type(s, pos, tokens):
    return n.ListType(tokens[0])._parsedata(s, pos)
ARRAY_TYPE = p.Suppress(p.Keyword('array') - p.Keyword('of')) - p.common.integer - INLINE_TYPE
@ARRAY_TYPE.set_parse_action
def parse_array_type(s, pos, tokens):
    return n.ListType(*tokens)._parsedata(s, pos)
MAPPING_TYPE = p.Suppress(p.Keyword('mapping') - p.Keyword('of')) - PRIMITIVE_TYPE - p.Keyword('to').suppress() - INLINE_TYPE
@MAPPING_TYPE.set_parse_action
def parse_mapping_type(s, pos, tokens):
    return n.MappingType(*tokens)._parsedata(s, pos)
STRUCT_FIELD_DEF = p.Opt(DOCBLOCK) + p.Group(p.ZeroOrMore(TAG)) + IDENTIFIER - p.Suppress(p.Literal(':')) - INLINE_TYPE
@STRUCT_FIELD_DEF.set_parse_action
def parse_struct_field_def(s, pos, tokens):
    if isinstance(tokens[0], n.DocBlock):
        docblock, tags, name, type = tokens
    else:
        docblock = None
        tags, name, type = tokens
    return n.StructFieldDef(name, type, tags, docblock)._parsedata(s, pos)
STRUCT_DEF = p.Keyword('struct') - p.Suppress('{') - p.Group(p.ZeroOrMore(STRUCT_FIELD_DEF)) - p.Suppress('}')
@STRUCT_DEF.set_parse_action
def parse_struct_def(s, pos, tokens):
    return n.StructDef(tokens[1])._parsedata(s, pos)
ENUM_FIELD_DEF = IDENTIFIER + p.Opt(p.Suppress('=') + LITERAL_VALUE)
@ENUM_FIELD_DEF.set_parse_action
def parse_enum_field_def(s, pos, tokens):
    return n.EnumFieldDef(*tokens)._parsedata(s, pos)
ENUM_DEF = p.Keyword('enum') - p.Opt(p.Suppress(p.Keyword('of')) - PRIMITIVE_TYPE, n.PrimativeType('int')) - p.Suppress('{') - p.Group(p.ZeroOrMore(ENUM_FIELD_DEF)) - p.Suppress('}')
@ENUM_DEF.set_parse_action
def parse_enum_def(s, pos, tokens):
    return n.EnumDef(tokens[1], tokens[2])._parsedata(s, pos)

INLINE_TYPE <<= p.Opt('?') + (PRIMITIVE_TYPE | STRUCT_DEF | ENUM_DEF | LIST_TYPE | ARRAY_TYPE | MAPPING_TYPE | IDENTIFIER)
@INLINE_TYPE.set_parse_action
def parse_inline_type(s, pos, tokens):
    return n.InlineType(tokens[-1], tokens[0]=='?')._parsedata(s, pos)

TYPE_DEF = p.Opt(DOCBLOCK) + p.Group(p.ZeroOrMore(TAG)) + p.Keyword('type').suppress() - IDENTIFIER - p.Suppress(':') - (PRIMITIVE_TYPE | STRUCT_DEF | ENUM_DEF | LIST_TYPE | ARRAY_TYPE | MAPPING_TYPE | IDENTIFIER)
@TYPE_DEF.set_parse_action
def parse_type_def(s, pos, tokens):
    if isinstance(tokens[0], n.DocBlock):
        docblock, tags, name, type = tokens
    else:
        docblock = None
        tags, name, type = tokens
    return n.TypeDef(name, type, tags, docblock)._parsedata(s, pos)


DOCUMENT = p.ZeroOrMore(TYPE_DEF).ignore(COMMENT)

if __name__ == '__main__':
    from pprint import pprint
    data = '''
// New types are delcared with the `type` keyword.
type SomeDataType: struct{
    // Types of values appear after a colon
    val1: int
    /** docblock */
    #sql-type VARCHAR(32)
    #label Value 2
    val2: string
}
// Some types take parameters via keywords (`to`, `of`)
type ComplexProperties: struct{
    #sql-ignore
   a_list: list of string
   #php-validate(
    foreach ($value as $k=>$v){
        if ($v == '()') return false;
    }
    return true;
   )
   a_mapping: mapping of string to int
}
'''
    pprint(DOCUMENT.parse_string(data, parse_all=True))