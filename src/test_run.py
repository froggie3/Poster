import pytest
from datetime import datetime
from run import *


@pytest.mark.freeze_time("2023-04-01T00:00:00+09:00")
def test_calc_seconds_from_config():
    assert calc_seconds_from_config(1, 0) == 3600
    assert calc_seconds_from_config(0, 0) == 0
    assert calc_seconds_from_config(1, 0) == timedelta(seconds=3600).total_seconds()


@pytest.mark.freeze_time("2023-04-01T00:00:00+09:00")
def test_needs_update():
    intval = {"hours": 3, "minutes": 0}
    assert not needs_update("2023-04-01T02:59:59+09:00", intval)
    assert needs_update("2023-04-01T03:00:00+09:00", intval)
    assert needs_update("2023-04-01T03:00:01+09:00", intval)

    intval2 = {"hours": 0, "minutes": 30}
    assert not needs_update("2023-04-01T00:29:59+09:00", intval2)
    assert needs_update("2023-04-01T00:30:00+09:00", intval2)
    assert needs_update("2023-04-01T00:30:01+09:00", intval2)

def test_should_execute_operation():
    assert should_execute_operation(False)
    assert not should_execute_operation(True)


def test_extract_files_for_upload():
    image_paths = {
        "2023-06-02T03:00:04.364914": {
            "12345678901234": [
                "/path/to/images/2023-06-02_03-00-02_12345678901234.png",
                "/path/to/images/2023-06-02_03-00-02_12345678901234_icon.png"
            ]
        }
    }
    assert extract_files_for_upload(image_paths) == [
        "/path/to/images/2023-06-02_03-00-02_12345678901234.png",
        "/path/to/images/2023-06-02_03-00-02_12345678901234_icon.png"
    ]
    assert not extract_files_for_upload(image_paths) == [
        "/path/to/images/2023-06-02_03-00-02_12345678901234.png",
    ]


@pytest.mark.freeze_time("2023-04-01T00:00:00+09:00")
def test_create_parmalink_and_filepath():
    assert create_parmalink_and_filepath(place_ids=[12345678901234], dir_local='/path/to', dir_remote='https://example.com/image/to') == {
        'path': [
            ('/path/to/2023-04-01_00-00-00_12345678901234.png',
             '/path/to/2023-04-01_00-00-00_12345678901234_icon.png')
        ],
        'url': [
            ('https://example.com/image/to/2023-04-01_00-00-00_12345678901234.png',
             'https://example.com/image/to/2023-04-01_00-00-00_12345678901234_icon.png')
        ],
    }
