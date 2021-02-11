# Rise of Kingdoms CLI Tools

[![Discord](https://img.shields.io/discord/768180228710465598)](https://discord.gg/drhxwVQ) [![License: MIT](https://img.shields.io/github/license/carmelosantana/rok-monster-cli)](https://opensource.org/licenses/MIT)

- [RoK Monster](#rok-monster)
- [How it works](#how-it-works)
- [Install](#install)
  - [Automated](#automated)
  - [Manual](#manual)
    - [Software](#software)
    - [Tessdata](#tessdata)
    - [rok-monster-cli](#rok-monster-cli)
- [Jobs](#jobs)
  - [Governor More Info Kills](#governor-more-info-kills)
- [Usage](#usage)
  - [Setup](#setup)
  - [Arguments](#arguments)
  - [Start a job via CLI](#start-a-job-via-cli)
  - [Start a job via php](#start-a-job-via-php)
- [Config](#config)
  - [Profile](#profile)
- [Community](#community)
- [Funding](#funding)
- [License](#license)

---

## [RoK Monster](https://rokmonster.com)

Command line tools to help automate collection of player statistics from [Rise of Kingdoms](https://rok.lilithgames.com/en). By analyzing recorded game play we can extract various data points such as governor power, deaths, kills and more. This can help with various kingdom statistics or fairly distributing [KvK](https://rok.guide/the-lost-kingdom-kvk/) rewards.

![Sample](https://carmelosantana.com/wp-content/uploads/sites/8/2020/11/rok-monster-cli-v0.2.0.png)

*Results may vary.*

## How it works

Here's a quick overview of what happens during application execution for job `governor_more_info_kills`.

1. Screenshots are captured from any source video representing the most "interesting" frames.
2. We iterate through each frame and perform the following actions:
   1. Screenshots are compared to a sample image.
   2. If we have a match we crop the image per instructions declared in the profile. *Each segment represents a single data point we're trying to collect.*
3. On completion a table prints when running via CLI.

## Install

### Automated

The simplest way to get up and running is with the following command:

```bash
curl -sSL https://raw.githubusercontent.com/carmelosantana/rok-monster-cli/master/install.sh | bash -s y
```

This will install all necessary dependencies and start a small demo project.

---

Alternatively you can clone the repository and review the install script before executing.

```bash
git clone https://github.com/carmelosantana/rok-monster-cli/
cd rok-monster-cli
sudo bash install.sh
```

### Manual

Requirements:

- [PHP](https://www.php.net/manual/en/install.php) 7.4
  - GD extension
- [Composer](https://getcomposer.org/)
- [ImageMagick](https://imagemagick.org/)
- [Tesseract](https://github.com/tesseract-ocr/tesseract)
- [FFmpeg](https://ffmpeg.org/)

#### Software

This assumes you have [PHP](https://www.php.net/manual/en/install.php) 7.4 installed and running with access to [Composer](https://getcomposer.org/).

```bash
sudo apt install imagemagick ffmpeg tesseract-ocr
```

*This does not represent the complete install instructions for all dependencies. Please review the [install script](https://github.com/carmelosantana/rok-monster-cli/blob/master/install.sh) for detailed installation instructions.*

#### Tessdata

Using [tessdata](https://github.com/tesseract-ocr/tessdata) or [tessdata_best](https://github.com/tesseract-ocr/tessdata_best) models from the [Tesseract](https://github.com/tesseract-ocr) repositories have produced better results than the models provided by apt-get. You can download select languages or clone the repository and set this path with  `--tessdata`.

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

Capture video or screenshots of the governor(s) **More Info** screen located in their profile. Kills per troop type can also be captured by pressing **(?)** by total kills.

| Data | OCR Areas |
| --- | --- |
| Name, Power, Total kills, Kills *(by troop type)*, Dead| ![Data capture](https://carmelosantana.com/wp-content/uploads/sites/8/2020/11/771ff7c3be3fdcfe06c6500f22b60edf-preview-e1612324629618.png) |

## Usage

### Setup

- Game resolution and capture of at least 1920x1080
- Current job templates are designed for English and 16/9 resolution

### Arguments

| Argument | Value | Default | Description |
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
| video | `bool` | `1` | Enable video processing |

- *bool as `0\1` or `true\false`*

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
| `callback` | The callback function receives raw OCR data as it's only argument. This could be for used for additional cleanup or data manipulation before the next image is processed. Provide namespace if applicable. |

## Community

Have a question, an idea, or need help geting started? Checkout our [Discord](https://discord.gg/drhxwVQ)!

Honorable mentions for [community](https://discord.gg/drhxwVQ) members who've donated time and resources to **rok-monster-cli** or [rokmonster.com](https://rokmonster.com).

- BouchB
- j7johnny

## Funding

If you find **rok-monster-cli** useful you can help fund future development by making a contribution to one of our funding sources below.

- [PayPal](https://www.paypal.com/donate?hosted_button_id=EKK8CQTPJG7WL)
- Bitcoin `bc1qx7v5vvxwnhpl3dssggy0hytcx2rpq5dkkfwyy4`
- Ethereum `0xA8Ebb6e5EC503E90551dD1bdE2d00B6C126eD5C5`
- Tron `TPnGEfkUZ2py6CFkh8wgqqYehJ29EbMWVw`

## License

The code is licensed [MIT](https://opensource.org/licenses/MIT) and the documentation is licensed [CC BY-SA 4.0](https://creativecommons.org/licenses/by-sa/4.0/).
