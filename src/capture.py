#!/usr/bin/env python3

from datetime import datetime, timezone, timedelta
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from dotenv import load_dotenv
from zoneinfo import ZoneInfo

import time
import logging

import setting


class ForcastGrabberByImage:
    def __init__(self):
        self.scale_factor = 1 

        binary_location = "/usr/bin/google-chrome-stable"
        arguments = []
        # binary_location = param['binary_location']
        # arguments = param['binary_arguments']
        self.driver = self.prepare_instance(binary_location, arguments)


    def prepare_instance(self, binary_location, arguments):
        options = Options()
        options.binary_location = binary_location 
        options.add_argument("--hide-scrollbars")
        if arguments:
            for args in arguments:
                options.add_argument(args)
        driver = webdriver.Chrome(options=options)
        return driver


    def capture_webpage_screenshots(self, place_ids) -> dict:
        image_paths = []

        timestamp = datetime.now(tz=ZoneInfo('Asia/Tokyo')).isoformat()
        logging.warning("timestamp: " + timestamp)

        for i, place_id in enumerate(place_ids):
            save_destination = f"screenshot_{timestamp}.png" 
            response = self.capture_webpage_screenshot(place_id, save_destination)
            if response:
                image_paths.append(save_destination)
            else:
                # something wrong happen
                return None
        else:
            self.driver.quit()
        return timestamp, image_paths


    def capture_webpage_screenshot(self, place_id, save_destination) -> bool:
        url = self.extend_place_id(place_id)
        self.driver.get(url)
        scale_factor, window_size = self.scale_window(scale_factor=0) 

        self.driver.set_window_size(*window_size)
        logging.warning(f"scale_factor: {scale_factor} (window_size: {window_size})")

        logging.warning(f"place_id: {place_id}")
        logging.warning(f"driver.current_url: " + self.driver.current_url)
        # filename, filename_icon = paths[i]
        # image_paths[timestamp][place_id].extend([filename, filename_icon])
        # weather_icon.screenshot(filename_icon)
        self.execute_optional_script(scale_factor)
        response = self.driver.save_screenshot(save_destination)
        return response


    def scale_window(self, scale_factor=0):
        window_scales = [(1.0, 1100, 800), (1.5, 1650, 1200), (2.0, 2200, 1600)]
        # scale_factor = param["size"]
        factor, *window_size = window_scales[scale_factor]
        if (scale_factor in [0, 1, 2]):
            factor, *window_size = window_scales[self.scale_factor]
        return factor, window_size


    def extend_place_id(self, place_id):
        extended = "https://www.nhk.or.jp/kishou-saigai/city/weather/" + place_id
        return extended 


    def execute_optional_script(self, scale_factor):
        header         = self.driver.find_element(By.CSS_SELECTOR, ".nr-common-header-wrapper")
        the_menu       = self.driver.find_element(By.CSS_SELECTOR, ".theMenu")
        float_button   = self.driver.find_element(By.CSS_SELECTOR, ".the-float-button-data-map")
        weather_icon   = self.driver.find_element(By.CSS_SELECTOR, ".weatherLv3Forecast3Day_table_day1 img")
        banner_image   = self.driver.find_element(By.CSS_SELECTOR, ".balloon")
        title_subBlock = self.driver.find_element(By.CSS_SELECTOR, ".theWeatherLv3_title_subBlock")
        cookie_notice  = self.driver.find_element(By.CSS_SELECTOR, ".bottom_optout_announce")
        arguments = (header, the_menu, 
                     float_button, banner_image, 
                     title_subBlock, cookie_notice, 
                     str(scale_factor))

        javascript = """
        document.body.style.zoom = arguments[6];
        if (arguments[0]) { arguments[0].remove(); }
        if (arguments[1]) { arguments[1].remove(); }
        if (arguments[2]) { arguments[2].remove(); }
        if (arguments[3]) { arguments[3].remove(); }
        if (arguments[4]) { arguments[4].remove(); }
        if (arguments[5]) { arguments[5].remove(); }
        """ 
        response = self.driver.execute_script(javascript, *arguments)

        return response


def main():
    place_ids = (setting.place_id,)
    capture = ForcastGrabberByImage()
    capture.capture_webpage_screenshots(place_ids)

if __name__ == "__main__":
    main()

