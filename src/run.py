#!/usr/bin/env python3

from datetime import datetime, timezone, timedelta
from selenium import webdriver
from selenium.webdriver.chrome.options import Options as ChromeOptions
from selenium.webdriver.common.by import By
from typing import Dict, List, Generator
import argparse
import json
import mimetypes
import os
import requests
import time
from zoneinfo import ZoneInfo
from pprint import pprint


def capture_webpage_screenshots(paths: list, **param: dict) -> dict:
    """
    ウェブページのスクリーンショットを撮影し、画像を保存し、保存先のパスをログに残します。

    Args:
        paths (list): Local paths for images
        param (dict): 操作のパラメータを含む辞書

    Returns:
        dict: 撮影日時と画像の保存先パスを記録した辞書。撮影が行われなかった場合は空の辞書を返します。
    """
    window_scales = [
        (1.0, 1100, 800),
        (1.5, 1650, 1200),
        (2.0, 2200, 1600)
    ]

    if (param["size"] in [0, 1, 2]):
        s, w, h = window_scales[param["size"]]
    else:
        s, w, h = window_scales[0]

    options = ChromeOptions()
    options.binary_location = param['binary_location']
    for args in param['binary_arguments']:
        options.add_argument(args)

    driver = webdriver.Chrome(options=options)
    driver.set_window_size(w, h)

    place_ids = param["places"].keys()
    image_paths = {}
    date_iso = datetime.now(tz=ZoneInfo('Asia/Tokyo')).isoformat()

    # 前もってプロパティを作っておく
    image_paths[date_iso] = {}
    for place_id in place_ids:
        image_paths[date_iso][place_id] = []

    for i, place_id in enumerate(place_ids):
        driver.get(
            f"https://www.nhk.or.jp/kishou-saigai/city/weather/{place_id}/")

        print(f'{param["places"][place_id]}の気象情報を取得しています。\n' +
              f'Retrieving from {driver.current_url}')

        time.sleep(1)

        header = driver.find_element(
            By.CSS_SELECTOR, ".nr-common-header-wrapper")

        the_menu = driver.find_element(
            By.CSS_SELECTOR, ".theMenu")

        float_button = driver.find_element(
            By.CSS_SELECTOR, ".the-float-button-data-map")

        weather_icon = driver.find_element(
            By.CSS_SELECTOR, ".weatherLv3Forecast3Day_table_day1 img")

        if i == 0:
            cookie_notice = driver.find_element(
                By.CSS_SELECTOR, "#notice_bottom_optout_announce_close")

            driver.execute_script(f"arguments[0].click();", cookie_notice)

        driver.execute_script(f"""document.body.style.zoom = '{s}';
                                  arguments[0].remove();
                                  arguments[1].remove();
                                  arguments[2].remove();
                                  """,
                              header, the_menu, float_button)

        time.sleep(1)

        filename, filename_icon = paths[i]

        image_paths[date_iso][place_id].extend([filename, filename_icon])

        weather_icon.screenshot(filename_icon)

        time.sleep(1)

        driver.save_screenshot(filename)

        time.sleep(1)

    driver.quit()

    return {
        "lastUpdate": date_iso,
        "imagePaths": image_paths
    }


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


def should_execute_operation(done: bool) -> bool:
    if not done:
        return True

    # 実行される操作はないので False を返す
    return False


def needs_update(last_update: str, interval: dict[str, dict[str, int]]) -> bool:
    last = datetime.fromisoformat(last_update)
    today = datetime.now(tz=ZoneInfo('Asia/Tokyo'))

    # print([
    #        calc_seconds_from_config(**interval),
    #        (last - today).total_seconds()])

    return calc_seconds_from_config(**interval) <= (last - today).total_seconds()


def calc_seconds_from_config(hours: int, minutes: int) -> int:
    return 3600 * hours + 60 * minutes


def send_image_post_request(url: str, image_paths: list) -> str:
    """
    指定されたURLに対して画像ファイルをPOSTリクエストで送信し、レスポンスを返します。

    Args:
        url: POST先のURL
        image_paths: 送信する画像のリスト

    Returns:
        bool: サーバーからのレスポンステキストがすべて正常な時はTrue。
        レスポンスがない場合はFalseを返します。
    """

    files = extract_files_for_upload(image_paths)

    for file_path in files:
        mime_type, _ = mimetypes.guess_type(file_path)
        file = {
            'file': (file_path, open(file_path, 'rb'),
                     mime_type)}

        # リクエストを送信
        response = requests.post(url, files=file)
        if response.text:
            print(f'Image uploaded to {response.json()["permalink"]}')
            # print(f'Image uploaded to {json.loads(response.text)["permalink"]}')
        else:
            return False

    return True


def extract_files_for_upload(image_paths: dict) -> list:
    return [z
            for x in image_paths.values()
            for y in x.values()
            for z in y
            ]


def main(j: dict, args) -> None:
    imagePaths = j['imagePaths']
    latest = j['latest']

    if not should_execute_operation(j['latest']['done']):
        print(
            'Record already exists (' +
            f'Last updated: {latest["lastUpdate"]})'
        )
    else:
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

            with open(f'{args.profile}.json', 'w', encoding="utf-8") as fp:
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


def send_to_webhook(urls: list, **kwargs) -> None:

    place_ids = kwargs['place_ids']
    last_timestamp = kwargs['last_update']

    for i, place_id in enumerate(place_ids.keys()):

        url = kwargs['webhook_url']

        filename, filename_icon = urls[i]

        # pprint([filename, filename_icon])

        data = {
            "content": "",
            "username": "NHK NEWS WEB",
            "avatar_url": "https://pbs.twimg.com/profile_images/1232909058786484224/X8-z940J_400x400.png",
            # "allowed_mentions": True,
            "embeds": [{
                "title": f"{place_ids[place_id]} | 天気予報",
                "description": f"全国 > 千葉県 > {place_ids[place_id]}の天気",
                "url": f"https://www.nhk.or.jp/kishou-saigai/city/weather/{place_id}/",
                "timestamp": f"{last_timestamp}",
                "color": 0x0076d1,
                "image": {
                    "url": f"{filename}"},
                "thumbnail": {
                    "url": f"{filename_icon}",
                    # "height": 16,
                    # #"width": 16
                },
                "footer": {
                    "text": "Deployed by Yokkin",
                    "icon_url": "https://yokkin.com/wp/wp-content/themes/Odamaki/files/img/website-logo.png"
                },
                "author": {
                    "name": "あなたの天気・防災"
                }
            }]
        }

        headers = {
            "Content-Type": 'application/json'
        }
        response = requests.post(url, data=json.dumps(data), headers=headers)

        if response:
            # print(r.text)
            pass


if __name__ == "__main__":
    parser = argparse.ArgumentParser()

    parser.add_argument(
        '--profile',
        dest='profile',
        default='default',
        type=str,
        help='specify a file for configuration excluding an extention (default: "%(default)s")'
    )

    args = parser.parse_args()

    if (os.path.exists(f'{args.profile}.json')):
        # Open config file
        with open(f'{args.profile}.json', 'r', encoding="utf-8") as fp:
            j = json.loads(fp.read())

        if j['settings']['force']:
            j['latest']['done'] = False

        # "done" は日付をまたいだ時に意味をなさないので必ずチェックにかける
        if j['latest']['done']:
            if not needs_update(
                    j['latest']['lastUpdate'],
                    {
                        'hours':   j['settings']['interval']['hours'],
                        'minutes': j['settings']['interval']['minutes']
                    }):
                j['latest']['done'] = False

        main(args=args, j=j)

    else:
        msg = f'Error: {args.profile}.json not found.'
        print(msg)
