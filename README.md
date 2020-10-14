# Rise of Kingdoms CLI Tools

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

- [How it works](#how-it-works)
- [Setup](#setup)
  - [Install](#install)
  - [Config](#config)
- [Usage](#usage)
  - [Arguments](#arguments)
- [Jobs](#jobs)
  - [Governor More Info](#governor-more-info)
    - [Input](#input)
    - [Data](#data)
    - [Output](#output)
- [Job definition](#job-definition)

Command line tools to help construct player statistics from [Rise of Kingdoms](https://rok.lilithgames.com/en). By analyzing recorded game play we can extract various data points such as governor power, deaths, kills and more. This can help with various kingdom statistics or fairly distributing [KvK](https://rok.guide/the-lost-kingdom-kvk/) rewards.

![Sample](https://cdn.wp.farm/rok.monster/github/1638-sample.png)

## How it works

Here's a quick break down of what happens during application execution for job `governor-more-info`.

1. Screenshots are created from the input video. These screenshots are roughly 45 frames apart and represent the most "interesting" frame of the batch. This will hopefully capture a clear image of the screen that contains the data we're trying to collect.
2. We iterate through each frame and perform the following actions:
   1. These screenshots are compared to sample images. When a match is found a profile is loaded that contains instructions for further processing.
   2. The image is cropped per each segment declared in the profile. Each segment represents a single data point we're trying to collect. This is what it looks like after a match is found and data points are read.

    ```bash
    [2020-10-08 12-37-37.mkv-329.png] #256
    Distortion: 0.0311905
    Match: governor_more_info_kills
    OCR: name 6d2cafb1c404b08e338113283ea6caad.png
    OCR: power 8a648e370f0443741fd901bfa5eac07f.png
    OCR: kills 51ea300ae469bc8ab409b39fdcafcc42.png
    OCR: deaths 372e50c3b1d58d71975d0caa93683119.png
    OCR: t1 1c23a20b131247c6c7dc67a5f1c6d6d6.png
    OCR: t2 5e456bf5f30abc09cb061288b6f0dda7.png
    OCR: t3 5de0b3e8c32f20864a41a82cb4d79004.png
    OCR: t4 5194d9efb63fa0d0b977566024314b96.png
    OCR: t5 094345220a180a383086e358f006af72.png
    ```

    The hashed image is the cropped image segment. These files are considered temporary and are purged on each new run.
    3. If provided a callback function will further process this single data point.
3. Data is structured per the profile and prepared for output, in this case a CSV.
4. A table prints with the data formatted per the previously loaded profile.

## Setup

Requirements:

- php 7.4 *(tested)*
- [Composer](https://getcomposer.org/)
- [Tesseract](https://github.com/tesseract-ocr/tesseract)
- [FFmpeg](https://ffmpeg.org/)

Recommended:

- Game resolution and capture of at least 1920x1080

### Install

```bash
git clone https://github.com/carmelosantana/rok-monster-cli
cd rok-monster-cli
composer install
```

### Config

Default jobs and basic settings are defined in `config.php`. This file should **not** be modified as it may change with development.

Changes can be made in a new file with the name of `my-config.php`. Existing jobs can be changed, new jobs can be added, and media paths can be defined in `my-config.php`. *Technically* it will load anything but for now we'll be using it for custom jobs 😅.

## Usage

1. Record the necessary screens specified per the given job. In this example we need the **Governor More Info** profile screen.
2. Copy video to `ROK_PATH_INPUT`.
3. Run job:

    ```bash
    php rok.php --job=governor_more_info
    ```

4. Check `ROK_PATH_OUTPUT` for files containing Governor statistics.

### Arguments

| Argument | Value | Default |Description |
| --- | --- | --- | --- |
| echo | `boolean` | *0* | Prints raw OCR reading per image |
| purge | `boolean` | *1* | Delete tmp working DIR at job start |
| video | `boolean` | *1* | Process video - create screenshots etc |
| distortion | `float` | *0.037* | Distortion metric measured by Imagick compare  |
| frames | `int` | *45* | Frames between each screenshot  |
| oem | `int` | *0* | Tesseract setting |
| psm | `int` | *7* | Tesseract setting |

- *boolean 0/1*

**Example:** Reprocess images without creating new screenshots and change distortion.

```bash
php rok.php --job=governor_more_info --video=0 --purge=0 --distoration=0.05
```

## Jobs

Jobs are predefined actions that can perform numerous actions.

**Available jobs:**

- [Governor More Info](#governor-more-info)

### Governor More Info

`governor_more_info`

![Governor Info](images/sample/governor_more_info_kills.png)

#### Input

Recording of the governor(s) **More Info** screen located in their profile. Kills per troop type can also be captured by pressing **(?)** by total kills.

#### Data

- Name
- Power
- Total kills
- Deaths
- Kills (per troop type)

#### Output

- CSV
- Table via CLI

## Job definition

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
            'whitelist' => range(0, 9),
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
| `whitelist` | Character whitelist [tesseract-ocr-for-php](https://github.com/thiagoalessio/tesseract-ocr-for-php#whitelist) |
| `crop` | Crop points to segment `power` from the image. [x, y, image-crop-x, image-crop-y] |
| `callback` | The callback function receives raw OCR data as it's only argument to further processing. This could be for any cleanup or additional data manipulation before next image is processed.  |
