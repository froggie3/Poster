import pytest
from datetime import datetime
from run import *


@pytest.mark.freeze_time("2023-04-01T00:00:00+00:00")
def test_is_same_day():
    assert is_same_day("2023-04-01T12:00:00+00:00")
    assert is_same_day("2023-04-01T12:00:00+0:00")
    assert not is_same_day("2023-04-02T00:00:00+00:00")


def test_should_execute_operation():
    assert should_execute_operation({'done': False})
    assert not should_execute_operation({'done': True})

# def test_create_parmalink_and_filepath():
#     assert create_parmalink_and_filepath() == {}
