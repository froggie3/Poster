#!/usr/bin/env python3

from datetime import datetime, timezone, timedelta
from typing import Dict, List, Generator
import argparse
import json
import mimetypes
import os
import requests
import time
from zoneinfo import ZoneInfo
from pprint import pprint
import logging

from capture import * 
import setting

def create_parmalink_and_filepath(place_ids: list, dir_local: str, dir_remote: str) -> dict:
    """
    Create parmalink URLs for images to be uploaded and its local filepaths.

    Args:
        place_ids (list): A list containing location IDs each of which consists from 14 digit numbers
        dir_local (str): Local path for image to be contained: (Example: "/example/image/to")
        dir_remote (str): Remote path for image to be contained: (Example: "https://example.com/image/to/")

    Returns:
        A dictionary that contains both local and remote paths for images.
    """
    dictionary = {}
    dictionary['path'] = []
    dictionary['url'] = []

    for place_id in place_ids:
        unique_id = f"{ datetime.now(tz=ZoneInfo('Asia/Tokyo')).strftime('%Y-%m-%d_%H-%M-%S') }_{ place_id }"

        dictionary['url'].append(
            (
                f"{dir_remote}/{unique_id}.png",
                f"{dir_remote}/{unique_id}_icon.png"
            )
        )
        dictionary['path'].append(
            (
                f"{dir_local}/{unique_id}.png",
                f"{dir_local}/{unique_id}_icon.png",
            )
        )

    return dictionary


class ImageUploader:
    def __init__(self):
        pass


    def post(self, url, file_path):
        mime_type, _ = mimetypes.guess_type(file_path)
        data = open(file_path, 'rb')
        file = {
            'file': (file_path, data, mime_type)
        }
        response = requests.post(url, files=file)
        if response.text:
            logging.debug(f'Image uploaded to {response.json()["permalink"]}')
            return response.text
        return None 


    def send_image_post_request(self, url, image_paths: list) -> str:
        logging.debug(f"url={url}")
        files = self.extract_files_for_upload(image_paths)
        for file_path in files:
            logging.debug(f"filepath={filepath}")
            self.post(url, file_path) 


    def extract_files_for_upload(self, image_paths: dict) -> list:
        return [z
                for x in image_paths.values()
                for y in x.values()
                for z in y
                ]


def main(j: dict, args) -> None:
    imagePaths = j['imagePaths']
    latest = j['latest']

    parmalinks_filepaths = create_parmalink_and_filepath(
        place_ids=list(j["settings"]["places"].keys()),
        dir_local=j["settings"]["dirs"]["imageContainer"],
        dir_remote=j["settings"]["upload"]["imageContainer"],
    )

    # Keep responses
    response_capture = capture_webpage_screenshots(
        paths=parmalinks_filepaths['path'],
        **{
            "size": j['settings']['size'],
            "places": j["settings"]["places"],
            "binary_location": j["settings"]["binary"]["location"],
            "binary_arguments": j["settings"]["binary"]["arguments"],
        },
    )

    if not (response_capture['lastUpdate'] == "" or response_capture['imagePaths'] == {}):
        imagePaths.update(response_capture['imagePaths'])

        latest['done'] = True
        latest['lastUpdate'] = response_capture['lastUpdate']
        latest['imagePaths'] = response_capture['imagePaths']

        with open(os.environ.get('FORECAST_CONFIG'), 'w', encoding="utf-8") as fp:
            dictionary_written = {
                "settings": j['settings'],
                "latest": latest,
                    "imagePaths": imagePaths
                }
                # print(json.dumps(dictionary_written, ensure_ascii=False, indent=2))
                json.dump(dictionary_written, fp,
                          ensure_ascii=False, indent=2)

            if j['settings']['upload']['enabled']:
                send_image_post_request(
                    image_paths=latest['imagePaths'],
                    url=j['settings']['upload']['entryPoint']
                )
                pass

            if j['settings']['webhook']['enabled']:
                send_to_webhook(**{
                    "webhook_url": j['settings']['webhook']['url'],
                    "last_update": latest['lastUpdate'],
                    "place_ids": j['settings']['places'],
                }, urls=parmalinks_filepaths['url'])
                pass
    pass

class WebhookPosting:
    def __init__(self):
        self.headers = { "Content-Type": 'application/json' }
        self.webhook_url = "" 


    def post(self, url, payload, headers):
        response = requests.post(url, data=payload, headers=headers)
        return response


    def prepare_request(self, images) -> str:
        data = self.prepare_payload(*images)
        json_text = json.dumps({})
        if data:
            json_text = json.dumps(data) 
            return json_text 
        return json_text


class WebhookNHKNews(WebhookPosting):
    def __init__(self):
        pass

    def prepare_payload(self, filename, filename_icon) -> dict:
        logging.warning(f"filename={filename}, filename_icon={filename_icon}")
        payload = {
            "content": "",
            "username": "NHK NEWS WEB",
            "avatar_url": "https://pbs.twimg.com/profile_images" \
                          "/1232909058786484224/X8-z940J_400x400.png",
            "embeds": [{
                "title": f"{place_ids[place_id]} | 天気予報",
                "description": "3時間おきに天気予報をお伝えします",
                "url": "https://www.nhk.or.jp/kishou-saigai/city/weather/" \
                        place_id,
                "timestamp": self.last_timestamp,
                "color": 0x0076d1,
                "image": {
                    "url": filename,
                },
                "thumbnail": {
                    "url": filename_icon,
                },
                "footer": {
                    "text": "Deployed by Yokkin",
                    "icon_url": "https://yokkin.com/wp/wp-content/" \
                                "themes/Odamaki/files/img/website-logo.png"
                },
                "author": {
                    "name": "あなたの天気・防災"
                }
            }]
        }
        return payload 


    def send_to_webhook(self, urls: list, **kwargs) -> None:
        place_ids = kwargs['place_ids']
        last_timestamp = kwargs['last_update']

        for i, place_id in enumerate(place_ids.keys()):
            payload = self.prepare_request(urls[i])
            if payload:
                response = self.post(url, payload, self.headers)
                if response:
                    logging.warning(response.text)
                    continue
            break


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    args = parser.parse_args()

    if not os.path.exists(setting.config):
        logging.error(f'{setting.config} not found.')
        sys,exit(1)

    with open(setting.config, 'r', encoding="utf-8") as fp:
        j = json.loads(fp.read())

    main(args=args, j=j)

