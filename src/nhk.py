import json
import logging
import sys
import urllib.request
from pprint import pprint

import setting
from telop import telop as T

logging.basicConfig(level=logging.DEBUG)


class TenkiGetter:
    headers = {
        "Host": "www.nhk.or.jp",
        "Accept-Language": "ja,en-US;q=0.5",
        "Accept-Encoding": "gzip, deflate, br",
        "User-Agent": "Mozilla/5.0 (X11; Linux x86_64; rv:109.0) "
                      "Gecko/20100101 Firefox/118.0",
        "Referer": "https://www.nhk.or.jp/kishou-saigai/pref/weather/chiba/"
    }

    def __init__(self, uid: str):
        self.query = urllib.parse.urlencode({
            'uid': uid,
            'kind': "web",
            'akey': "18cce8ec1fb2982a4e11dd6b1b3efa36"  # MD5 checksum of "nhk"
        })
        self.weather_data = {}

    def get(self) -> bool:
        logging.debug(f"self.query={self.query}")
        url = f"https://www.nhk.or.jp/weather-data/v1/lv3/wx/?{self.query}"
        logging.debug(f"url={url}")
        with urllib.request.urlopen(url) as f:
            response = f.read().decode('utf-8')
            if response:
                self.weather_data = json.loads(response)
                return True
        return False


if __name__ == "__main__":
    uid = setting.place_id
    getter = TenkiGetter(uid)
    if not (res := getter.get()):
        sys.exit(1)
    name = getter.weather_data.get("name")
    forecast_three_days = getter.weather_data.get("trf").get("forecast")
    pprint(name)
    pprint(forecast_three_days)
    for v in forecast_three_days:
        idx = int(v.get("telop"))
        print(f"weather={T[idx]}")
