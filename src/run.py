from datetime import datetime, date
from selenium import webdriver
from selenium.webdriver.chrome.options import Options as ChromeOptions
from selenium.webdriver.common.by import By
import json
import mimetypes
import os
import requests
import time
from pprint import pprint


def capture_webpage_screenshots(param: dict) -> dict:
    """
    ウェブページのスクリーンショットを撮影し、画像を保存し、保存先のパスをログに残します。

    Args:
        param (dict): 操作のパラメータを含む辞書

    Returns:
        dict: 撮影日時と画像の保存先パスを記録した辞書。撮影が行われなかった場合は空の辞書を返します。
    """
    window_scales = [(1.0, 1100, 800), (1.5, 1650, 1200), (2.0, 2200, 1600)]

    if (param["size"] in [0, 1, 2]):
        s, w, h = window_scales[param["size"]]
    else:
        s, w, h = window_scales[0]

    options = ChromeOptions()
    options.binary_location = param['binary']['location']
    for args in param['binary']['arguments']:
        options.add_argument(args)

    driver = webdriver.Chrome(options=options)
    driver.set_window_size(w, h)

    place_ids = param["places"].keys()
    image_paths = {}
    date_iso = datetime.now().isoformat()
    image_paths[date_iso] = {}

    for i, place_id in enumerate(place_ids):
        driver.get(f"https://www.nhk.or.jp/kishou-saigai/city/weather/{place_id}/")

        print(f'{param["places"][place_id]}の気象情報を取得しています。\n' +
              f'Retrieving from {driver.current_url}')

        time.sleep(1)

        header = driver.find_element(By.CSS_SELECTOR, ".nr-common-header-wrapper")
        the_menu = driver.find_element(By.CSS_SELECTOR, ".theMenu")
        float_button = driver.find_element(By.CSS_SELECTOR, ".the-float-button-data-map")

        if i == 0:
            cookie_notice = driver.find_element(By.CSS_SELECTOR, "#notice_bottom_optout_announce_close")
            driver.execute_script(f"arguments[0].click();", cookie_notice)

        driver.execute_script(f"""document.body.style.zoom = '{s}';
                                  arguments[0].remove();
                                  arguments[1].remove();
                                  arguments[2].remove();
                                  """,
                                  header, the_menu, float_button)

        time.sleep(1)

        filename = next(generate_timestamped_path(param))
        image_paths[date_iso][place_id] = filename
        driver.save_screenshot(filename)

        time.sleep(1)

    driver.quit()

    return {
        "lastUpdate": date_iso,
        "imagePaths": image_paths
    }


def generate_timestamped_path(param: dict)->None:
    # ["/home/path/to/images/2023-05-27_08-50-38_12100001210400.png", ...]
    place_ids = param["places"].keys()

    for place_id in place_ids:
        yield str(os.path.join( param["dirs"]["imageContainer"],
                            f"{ datetime.now().strftime('%Y-%m-%d_%H-%M-%S') }_{ place_id }.png"))


def should_execute_operation(j: dict) -> bool:
    if not j['done']:
        return True

    # 実行される操作はないので False を返す
    return False


def is_same_day(last_update: str) -> bool:
    """
    最後の更新日と現在の日付が同じかどうかを判定します。

    Args:
        last_update (str): 最後の更新日時の文字列（例: "2023-05-27T13:50:40+09:00"）

    Returns:
        bool: 最後の更新日と現在の日付が同じ場合は True、異なる場合は False を返します。
    """
    compare_date = datetime.fromisoformat(last_update).date()
    today = datetime.today().date()

    return compare_date == today


def send_image_post_request(j: dict) -> str:
    """
    指定されたURLに対して画像ファイルをPOSTリクエストで送信し、レスポンスを返します。

    Args:
        j (dict): 最新の情報を含む辞書

    Returns:
        str: サーバーからのレスポンステキスト。レスポンスがない場合は空文字を返します。
    """
    url = j['settings']['upload']['entryPoint']

    image_paths = j['latest']['imagePaths']
    files = [list(x.values())[-1] for x in image_paths.values()]

    for file_path in files:
        mime_type, _ = mimetypes.guess_type(file_path)
        file = {'file': (file_path, open(file_path, 'rb'), mime_type)}
        response = requests.post(url, files=file)

        return response.text if response else ""


def main(j: dict) -> None:
    imagePaths = j['imagePaths']
    latest = j['latest']

    if not should_execute_operation(latest):
        print('Record already exists (' +
             f'Last updated: {latest["lastUpdate"]})'
        )
    else:
        # Keep responses
        response_capture = capture_webpage_screenshots(j['settings'])

        if not response_capture == {}:
            imagePaths.update(response_capture['imagePaths'])

            latest['done'] = True
            latest['lastUpdate'] = response_capture['lastUpdate']
            latest['imagePaths'] = response_capture['imagePaths']

            with open('log.json', 'w', encoding="utf-8") as fp:
                dictionary_written = {
                    "$README": j['$README'],
                    "settings": j['settings'],
                    "latest": latest,
                    "imagePaths": imagePaths
                }
                #print(json.dumps(dictionary_written, ensure_ascii=False, indent=2))
                json.dump(dictionary_written, fp, ensure_ascii=False, indent=2)

            if j['settings']['upload']['enabled']:
                response_upload = send_image_post_request(j)
                if response_upload:
                    print(f'Image uploaded to {json.loads(response_upload)["permalink"]}')
                else:
                    print(f'Upload failed')
        else:
            print('Error!')

    #print([settings, latest, imagePaths])
    pass


if __name__ == "__main__":
    # Open config file
    with open('log.json', 'r', encoding="utf-8") as fp:
        j = json.loads(fp.read())

    if j['settings']['force']:
        j['latest']['done'] = False

    # "done" は日付をまたいだ時に意味をなさないので必ずチェックにかける
    if j['latest']['done']:
        if not is_same_day(j['latest']['lastUpdate']):
            j['latest']['done'] = False
    main(j)