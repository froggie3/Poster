#!/usr/bin/env python3

import os
import json
import mimetypes
import urllib.request
# import requests
import logging

import setting
from capture import * 


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
    def __init__(self, url):
        self.webhook_url = url
        pass


    def prepare_payload(self, filename="", filename_icon="") -> dict:
        logging.warning(f"filename={filename}, filename_icon={filename_icon}")
        payload = {
            "content": "Hello world!",
            "username": "NHK NEWS WEB",
            "avatar_url": "https://pbs.twimg.com/profile_images" \
                          "/1232909058786484224/X8-z940J_400x400.png"
            "attachments": [{
                "id": 0,
                "description": "Image of a cute little cat",
                "filename": "myfilename.png"
            }]
        }
        # embeds = {
        #     "embeds": [
        #         {
        #             "title": f"{place_ids[place_id]} | 天気予報",
        #             "description": "3時間おきに天気予報をお伝えします",
        #             "url": "https://www.nhk.or.jp/kishou-saigai/city/weather/" \
        #                     place_id,
        #             "timestamp": self.last_timestamp,
        #             "color": 0x0076d1,
        #             "image": {
        #                 "url": filename,
        #             },
        #             "thumbnail": {
        #                 "url": filename_icon,
        #             },
        #             "footer": {
        #                 "text": "Deployed by Yokkin",
        #                 "icon_url": "https://yokkin.com/wp/wp-content/" \
        #                             "themes/Odamaki/files/img/website-logo.png"
        #             },
        #             "author": {
        #                 "name": "あなたの天気・防災"
        #             }
        #         }
        #     ]
        # }
        # payload.update(embeds)
        return payload 


    def send(self) -> bool:
        # place_ids = kwargs['place_ids']
        # last_timestamp = kwargs['last_update']
        data = self.prepare_payload()
        payload = json.dumps(data)
        logging.warning(payload)
        response = self.post(self.webhook_url, payload, self.headers)
        if response:
            logging.warning(response)
            return True 
        return False
        


def main():
    webhook = WebhookNHKNews(
        "https://discord.com/api/webhooks/1112251236092215346/" \
        "VPtVFJ-WwGdFTGdvFfTj_3OC4E1TmI9KUGOUthC11JFB1UsrYN_VnSLLey4Nf3qXpfL9" 
    )
    webhook.send()
    pass


if __name__ == "__main__":
    main() 

