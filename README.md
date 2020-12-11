# Rise of Kingdoms CLI Tools

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

- [RoK Monster](#rok-monster)
- [How it works](#how-it-works)
- [Install](#install)
  - [Ubuntu](#ubuntu)
    - [Software](#software)
    - [Tessdata](#tessdata)
    - [rok-monster-cli](#rok-monster-cli)
- [Jobs](#jobs)
  - [Governor More Info Kills](#governor-more-info-kills)
    - [Input](#input)
    - [Data](#data)
    - [Output](#output)
- [Usage](#usage)
  - [Arguments](#arguments)
  - [Setup](#setup)
  - [Start a job via CLI](#start-a-job-via-cli)
  - [Start a job via php](#start-a-job-via-php)
- [Config](#config)
  - [Profile](#profile)

---

## [RoK Monster](https://rokmonster.com)

Command line tools to help automate collection of player statistics from [Rise of Kingdoms](https://rok.lilithgames.com/en). By analyzing recorded game play we can extract various data points such as governor power, deaths, kills and more. This can help with various kingdom statistics or fairly distributing [KvK](https://rok.guide/the-lost-kingdom-kvk/) rewards.

![Sample](https://carmelosantana.com/wp-content/uploads/sites/8/2020/11/rok-monster-cli-v0.2.0.png)

*Results may vary.*

## How it works

Here's a quick overview of what happens during application execution for job `governor_more_info_kills`.

1. Screenshots are captured from any source video representing the most "interesting" frames.
2. We iterate through each frame and perform the following actions:
   1. Screenshots are compared to sample images.
   2. The image is cropped per instructions declared in the profile. Each segment represents a single data point we're trying to collect. This is an output confirming a match was found and we're trying to capture data. A callback function can be provided to further process this data point.

    ```
    268: 2020-10-08 12-37-37.mkv-98.png
    Distortion: 0.115005
    OCR: e59720dac6af183f86e73dd957881990-name.png
    OCR: e59720dac6af183f86e73dd957881990-power.png
    OCR: e59720dac6af183f86e73dd957881990-kills.png
    OCR: e59720dac6af183f86e73dd957881990-t1.png
    OCR: e59720dac6af183f86e73dd957881990-t2.png
    OCR: e59720dac6af183f86e73dd957881990-t3.png
    OCR: e59720dac6af183f86e73dd957881990-t4.png
    OCR: e59720dac6af183f86e73dd957881990-t5.png
    ```
    
3. Finally a table prints when running via CLI. CSV output can be enabled with `--output_csv`.

## Install

Requirements:

- php 7.4 *(tested)*
- [Composer](https://getcomposer.org/)
- [ImageMagick](https://imagemagick.org/)
- [Tesseract](https://github.com/tesseract-ocr/tesseract)
- [FFmpeg](https://ffmpeg.org/)

### Ubuntu

#### Software

This assumes you have PHP 7.4 installed and running with access to [Composer](https://getcomposer.org/).

```bash
sudo apt install imagemagick ffmpeg tesseract-ocr
```

#### Tessdata

Using [tessdata](https://github.com/tesseract-ocr/tessdata) or [tessdata_best](https://github.com/tesseract-ocr/tessdata_best) models from the [Tesseract](https://github.com/tesseract-ocr) repositories have produced better results. You can download select languages or clone the repository and set this path with  `--tessdata`.

Alternatively you can try to install via apt-get. Some issues may include:

- Permissions
- Models don't work with legacy engine
- Less accurate

```bash
sudo apt install tesseract-ocr-all
```

#### rok-monster-cli

```bash
git clone https://github.com/carmelosantana/rok-monster-cli
cd rok-monster-cli
composer install
```

## Jobs

Default jobs are defined in `config.php` while user defined jobs can be added to `config.local.php`. A job contains all necessary instructions to prepare an image or video for OCR.

**Available jobs:**

- [Governor More Info Kills](#governor-more-info-kills)

### Governor More Info Kills

`governor_more_info_kills`

![Governor Info](https://carmelosantana.com/wp-content/uploads/sites/8/2020/11/governor_more_info_kills.png)

#### Input

Recording of the governor(s) **More Info** screen located in their profile. Kills per troop type can also be captured by pressing **(?)** by total kills.

#### Data

![Data capture](https://carmelosantana.com/wp-content/uploads/sites/8/2020/11/771ff7c3be3fdcfe06c6500f22b60edf-preview.png)

- Name
- Power
- Total kills
- Kills (per troop type)
- Dead

#### Output

- Table via CLI
- CSV

## Usage

### Arguments

| Argument | Value | Default |Description |
| --- | --- | --- | --- |
| debug | `bool` | `1` | Prints raw OCR reading per image. Uses local `--tmp_path` and preserves cropped images.  |
| job | `string` | *Required* | ID of job defined in `config.php` or `config.local.php` |
| input_path | `string` | *Required* | Media source path or file  |
| output_path | `string` | *Optional* | Defaults to `--input_path` |
| tmp_path | `string` | `sys_get_temp_dir()`| Temp directory for images manipulated during processing  |
| oem | `int` | `0` | OCR Engine Mode |
| psm | `int` | `0` | Page Segmentation Method |
| tessdata | `string` | `null` | User defined location for tessdata. Defaults to system installation path.  |
| compare_to_sample | `bool` | `1` | Enable compare to sample  |
| distortion | `float` | `0` | Distortion metric measured by Imagick compare  |
| output_csv | `bool` | `0` | Save a .csv file on job completion |
| output_user_words | `bool` | `0` | Save a list words found during the job |
| video | `bool` | `1` | Enable video processing |

- *bool as `0\1` or `true\false`*

### Setup

- Game resolution and capture of at least 1920x1080
- Current job templates are designed for English and 16/9 resolution

### Start a job via CLI

1. Record the necessary screens specified per the given job. In this example we need the **Governor More Info** profile screen.
2. Run job:

    ```bash
    php rok.php --job=governor_more_info_kills --input_path=YOUR_MEDIA_SOURCE(S)
    ```

3. Check `--output_path` for files containing Governor statistics.

### Start a job via php

1. Include `rok.php` in your project.
2. Invoke `RoK\OCR\ocr()`. This will return an `array` of the data captured.
   - **Required:** First argument, an `array` with at least `job` and `input_path`
   - **Optional:** Second argument, an `array` containing a [job profile](#job-definition).

## Config

Default jobs and basic settings are defined in `config.php`. This file should **not** be modified as it may change with development.

Changes can be made in a new file with the name of `config.local.php`. Existing jobs can be changed, new jobs can be added, and media paths can be defined in `config.local.php`. *Technically* it will load anything but for now we'll be using it for custom jobs 😅.

### Profile

Here we define parameters for each job. These parameters are user customizable and provide exact crop points for segmenting text within a screenshot and defining how Tesseract should interact with this cropped image. Each newly cropped image segment represents a single data point we want to capture.

```php
'governor_more_info_kills' => [
    'oem' => 0,
    'psm' => 7,
    'ocr_schema' => [
        'name' => [
            'crop' => [472, 147, 407, 91],
        ],
        'power' => [
            'allowlist' => range(0, 9),
            'crop' => [980, 168, 216, 35],
            'callback' => 'text_remove_non_numeric',
        ],
    ],
];
```

This sample config would output the following data.

| name | power |
| --- | --- |
| Barbara Walters | 25,351,714 |
| Zal zal | 28,512,574 |

Now we breakdown the config and explain each part.

| Key | Explanation |
| --- | --- |
| `governor_more_info_kills` | Array key is job title |
| `oem` | Specify the OCR Engine Mode [Tesseract](https://github.com/tesseract-ocr/tessdoc/blob/master/Command-Line-Usage.md) |
| `psm` | Specify the Page Segmentation Method [Tesseract](https://github.com/tesseract-ocr/tessdoc/blob/master/Command-Line-Usage.md) |
| `ocr_schema` | Start defining each data point |
| `name` | Data point with ID of `name` |
| `crop` | Crop points to segment `name` from the image. [x, y, image-crop-x, image-crop-y] |
| `power` | Data point with ID of `power` |
| `allowlist` | Character whitelist [tesseract-ocr-for-php](https://github.com/thiagoalessio/tesseract-ocr-for-php#allowlist) |
| `crop` | Crop points to segment `power` from the image. [x, y, image-crop-x, image-crop-y] |
| `callback` | The callback function receives raw OCR data as it's only argument to further processing. This could be for any cleanup or additional data manipulation before next image is processed. Provide namespace if applicable. |