import pytest
from ordain.model import *

@pytest.fixture
def basic_model():
    return {
        'User': Typedef(
            'User',
            StructType({
                'username': Typedef(
                    'username',
                    ScalarType.string,
                    TagRepository([

                    ]),
                    "The user's username"
                ),
                'password': Typedef(
                    'password',
                    ScalarType.string,
                    TagRepository([
                        Tag(None, 'only-if', 'python sql'),
                    ]),
                    "The user's (hashed) password"
                ),
                'created': Typedef(
                    'created',
                    ScalarType.datetime,
                    TagRepository([
                        Tag('js', 'ignore', True),
                    ]),
                    "The date and time the user signed up"
                ),
            }),
            TagRepository([
                Tag('py', 'impl', 'dataclass'),
                Tag('sql', 'name', 'users'),
            ]),
            "A basic user account"
        )
    }

@pytest.fixture
def inheritance_model():
    return {
        'Animal': Typedef(
            'Animal',
            StructType({
                'sex': Typedef(
                    'sex',
                    EnumType(ScalarType.string, ['male', 'female']),
                    TagRepository([
                        Tag('py', 'name', 'Sex')
                    ]),
                ),
                'current_activity': Typedef(
                    'current_activity',
                    EnumType(ScalarType.string, ['eating', 'sleeping', 'walking', 'pooping', 'procreating']),
                    TagRepository([
                        Tag('py', 'name', 'Activity')
                    ]),
                ),
            }),
            TagRepository([
                Tag('py', 'impl', 'dataclass')
            ])
        ),
        'Pet': Typedef(
            'Pet',
            StructType({
                'name': Typedef(
                    'name',
                    ScalarType.string,
                    TagRepository([

                    ])
                ),
            }),
            TagRepository([

            ]),
            parent='Animal'
        ),
        'Dog': Typedef(
            'Dog',
            StructType({
                'breed': Typedef(
                    'breed',
                    ScalarType.string,
                    TagRepository([

                    ])
                ),
            }),
            TagRepository([

            ]),
            parent='Pet'
        ),
    }