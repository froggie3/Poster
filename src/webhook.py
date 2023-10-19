#!/usr/bin/env python3

import os
import json
import mimetypes
import time
import urllib.request
import datetime
# import requests
import logging

import setting
import json
import logging
import sys
import urllib.request
from pprint import pprint

logging.basicConfig(level=logging.DEBUG)


class Telop:
    """https://www.nhk.or.jp/kishou-saigai/about/"""
    TELOP = ["" for idx in range(1 << 9)]
    TELOP[100] = "晴れ", ":sunny:"
    TELOP[101] = "晴れ時々くもり", ":partly_sunny:"
    TELOP[102] = "晴れ一時雨", ":white_sun_rain_cloud:" 
    TELOP[103] = "晴れ時々雨", ":white_sun_rain_cloud:" 
    TELOP[111] = "晴れのちくもり", ":white_sun_cloud:"
    TELOP[114] = "晴れのち雨", ":white_sun_rain_cloud:" 
    TELOP[200] = "くもり", ":cloud:" 
    TELOP[201] = "くもり時々晴れ", ":partly_sunny:" 
    TELOP[202] = "くもり一時雨", ":cloud_rain:" 
    TELOP[203] = "くもり時々雨", ":cloud_rain:" 
    TELOP[211] = "くもりのち晴れ", ":white_sun_cloud:" 
    TELOP[214] = "くもりのち雨", ":cloud_rain:" 
    TELOP[300] = "雨", ":cloud_rain:" 
    TELOP[301] = "雨時々晴れ", ":white_sun_rain_cloud:" 
    TELOP[302] = "雨一時くもり", ":white_sun_small_cloud:" 
    TELOP[303] = "雨時々雪", ":cloud_snow:" 
    TELOP[311] = "雨のち晴れ", ":white_sun_rain_cloud:" 
    TELOP[313] = "雨のちくもり", ":cloud_rain:" 
    TELOP[315] = "雨のち雪", ":cloud_snow:" 
    TELOP[400] = "雪", ":snowflake:" 
    TELOP[401] = "雪時々晴れ", ":cloud_snow:"
    TELOP[402] = "雪時々やむ", ":cloud_snow:"
    TELOP[403] = "雪時々雨", ":cloud_snow:"
    TELOP[411] = "雪のち晴れ", ":white_sun_rain_cloud:"
    TELOP[413] = "雪のちくもり", ":cloud_snow:"
    TELOP[414] = "雪のち雨", ":cloud_snow:"


class TenkiFetch:
    """Retrieve the weather forecast from NHK NEWS WEB API"""

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

    def fetch(self) -> dict:
        logging.debug(f"self.query={self.query}")
        url = f"https://www.nhk.or.jp/weather-data/v1/lv3/wx/?{self.query}"
        logging.debug(f"url={url}")
        with urllib.request.urlopen(url) as f:
            response = f.read().decode('utf-8')
            if response:
                self.weather_data = json.loads(response)
                return self.weather_data 
        return {} 


class WebhookPosting:
    headers = {
        "Content-Type": 'application/json', 
        "User-Agent": "Mozilla/5.0"
    }
    webhook_url = ""

    def __init__(self):
        pass

    def post(self, url, payload, headers):
        data = payload.encode("utf-8")
        req = urllib.request.Request(url, data=data, headers=headers)
        with urllib.request.urlopen(req) as f:
            response = f.read().decode('utf-8')
            logging.warning(response)
            return response
        return None


    def prepare_payload(self, image):
        return {}


class WebhookNHKNews(WebhookPosting):
    AUTHOR_URL = "https://yokkin.com/d/forecast_resource/author.jpg"
    AVATAR_URL = "https://yokkin.com/d/forecast_resource/avatar.png"

    def __init__(self, url, weather):
        self.webhook_url = url
        self.content = "天気でーす"
        self.weather = weather

    def prepare_payload(self, weather) -> dict:
        logging.debug(f"namespace={weather.forecast_date}")
        logging.debug(f"namespace={dir(weather)}")
        self.location_url = f"https://www.nhk.or.jp/kishou-saigai/city/weather/{weather.location_uid}"
        self.thumbnail_url = f"https://yokkin.com/d/forecast_resource/tlp{weather.telop}.png"
        self.timestamp = datetime.datetime.fromisoformat(weather.forecast_date).isoformat()
        # self.timestamp = datetime.now(tz=timezone(timedelta(hours=9))).isoformat()
        payload = {
            "content": self.content, 
            "username": "NHK NEWS WEB",
            "avatar_url": self.AVATAR_URL, 
            "embeds": [{
                "title": f"きょうの天気予報",
                "description": f"{weather.location_name}の天気予報です",
                "url": self.location_url,
                "timestamp": self.timestamp,
                "color": 0x0076d1,
                "image": {"url": "https://www3.nhk.or.jp/weather/tenki/tenki_01.jpg"},
                "thumbnail": { "url": self.thumbnail_url, },
                "footer": {
                    "text": "Deployed by Yokkin",
                    "icon_url": self.AUTHOR_URL,
                },
                "author": {
                    "name": "NHK NEWS WEB",
                    "url": "https://www3.nhk.or.jp/news/",
                    "icon_url": self.AVATAR_URL,
                },
                "fields": [
                    {
                        "name": "天気",
                        "value": f"{weather.weather_emoji} {weather.weather}",
                        "inline": False 
                    },
                    {
                        "name": "最高気温",
                        "value": f":chart_with_upwards_trend: {weather.max_temp} ℃ "
                        f"({weather.max_temp_diff} ℃)",
                        "inline": True
                    },
                    {
                        "name": "最低気温",
                        "value": f":chart_with_downwards_trend: {weather.max_temp} ℃ "
                        f"({weather.min_temp_diff} ℃)",
                        "inline": True
                    },
                    {
                        "name": "降水確率",
                        "value": f":umbrella: {weather.rainy_day} %",
                        "inline": True 
                    },
                ],
            }],
        }
        return payload 


    def send(self) -> bool:
        data = self.prepare_payload(self.weather)
        payload = json.dumps(data)
        logging.warning(payload)
        response = self.post(self.webhook_url, payload, self.headers)
        if response:
            logging.warning(response)
            return True 
        return False


class Weather:
    """Extract weather information just needed"""
    def __init__(self, weather_data: dict):
        pref = weather_data.get("lv2_info").get("name")
        district, self.location_uid = (weather_data.get(k) for k in ("name", "uid"))
        self.location_name = pref + district

        forecast_three_days = weather_data.get("trf").get("forecast")
        forecast_today = forecast_three_days[0]

        search_keys = ("forecast_date", "max_temp", "max_temp_diff", "min_temp", 
                    "min_temp_diff", "rainy_day")

        self.forecast_date, self.max_temp, self.max_temp_diff, self.min_temp, \
            self.min_temp_diff, self.rainy_day = (
                forecast_today.get(k) for k in search_keys
            )

        self.telop = forecast_today.get("telop")  # three digit number represents weather
        self.weather, self.weather_emoji = Telop.TELOP[int(self.telop)]



def main():
    url = "https://discord.com/api/webhooks/1112251236092215346/" \
           "VPtVFJ-WwGdFTGdvFfTj_3OC4E1TmI9KUGOUthC11JFB1UsrYN_VnSLLey4Nf3qXpfL9"

    # these are executed only when imported 
    fetch = TenkiFetch(uid=setting.place_id)
    if not (res := fetch.fetch()):
        sys.exit(1)
    weather = Weather(weather_data=res)
    webhook = WebhookNHKNews(url, weather=weather)
    webhook.send()


if __name__ == "__main__":
    main() 

