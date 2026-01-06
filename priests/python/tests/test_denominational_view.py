from ordain.model import *
from ordain.denominational_view import DenominationalTypedefView
from ordain.denominations import KnownDenomination

def test_get_name_without_tags(basic_model):
    view = DenominationalTypedefView.from_model('User', basic_model, KnownDenomination.PythonBase())
    assert view.name == 'User'

def test_get_name_from_tags(basic_model):
    view = DenominationalTypedefView.from_model('User', basic_model, KnownDenomination.SqlBase())
    assert view.name == 'users'

def test_get_impl(basic_model):
    view = DenominationalTypedefView.from_model('User', basic_model, KnownDenomination.PythonBase())
    assert view.impl is not None
    assert view.impl.cannon == 'py'
    assert view.impl.name == 'impl'
    assert view.impl.value == 'dataclass'

def test_skip_impl_for_other_denomination(basic_model):
    view = DenominationalTypedefView.from_model('User', basic_model, KnownDenomination.JsonBase())
    assert view.impl is None

def test_get_impl_inherited(inheritance_model):
    view = DenominationalTypedefView.from_model('Dog', inheritance_model, KnownDenomination.PythonBase())
    assert view.impl is not None
    assert view.impl.cannon == 'py'
    assert view.impl.name == 'impl'
    assert view.impl.value == 'dataclass'