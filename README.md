# Rise of Kingdoms OCR Tools

[![Discord](https://img.shields.io/discord/768180228710465598)](https://discord.gg/drhxwVQ) [![License: MIT](https://img.shields.io/github/license/carmelosantana/rok-monster-ocr)](https://opensource.org/licenses/MIT)

- [Rise of Kingdoms OCR Tools](#rise-of-kingdoms-ocr-tools)
  - [Community](#community)
  - [Kingdom Statistics](#kingdom-statistics)
    - [Features](#features)
    - [Limitations](#limitations)
  - [Process](#process)
  - [Install](#install)
    - [Official Support](#official-support)
    - [Automated](#automated)
    - [Manual](#manual)
      - [Software](#software)
      - [Tessdata](#tessdata)
      - [rok-monster-ocr](#rok-monster-ocr)
  - [Community Installs](#community-installs)
  - [Usage](#usage)
    - [Start a job via CLI](#start-a-job-via-cli)
  - [Arguments](#arguments)
  - [Supporters](#supporters)
  - [Funding](#funding)
  - [License](#license)

## Community

👋 Join our [Discord](https://discord.gg/drhxwVQ) and discuss automated data collection for your kingdom today!

## Kingdom Statistics

Command line tools to help collect player statistics from [Rise of Kingdoms](https://rok.lilithgames.com/en). By analyzing screenshots we can extract various data points such as governor power, deaths, kills and more. This can help with various kingdom statistics or fairly distributing [KvK](https://rok.guide/the-lost-kingdom-kvk/) rewards.

![Sample](https://carmelosantana.com/wp-content/uploads/2020/11/rok-monster-cli-v0.2.0.png)

*Results may vary.*

### Features

- Character recognition by [Tesseract](https://github.com/tesseract-ocr/tesseract)
- Fast hash based image comparison
- Live results with adb

### Limitations

- Best results with a 16:9 image at 1920*1080.
- English language is preferred as coordinate information lines up most accurately with English.

## Process

Below is a quick overview of the actions performed while executing the job `governor-more-info-kills`.

1. **rok-monster-ocr** will iterate through each screenshot and compare the input image to a known fingerprint of the image containing data to capture.
2. If a match is made the image is prepared per the instructions declared in the OCR profile. *Each cropped segment represents a single data point.*
   - This process is repeated for each image in the `input_path`.
3. By default on completion a table is printed via CLI and a CSV file is saved in the current working directory.

> 📌 Input path can be defined in `.env` file or via CLI argument `--input_path`.

## Install

### Official Support

- Ubuntu 20.04.2

### Automated

One line install.

```bash
curl -sSL https://raw.githubusercontent.com/carmelosantana/rok-monster-ocr/master/install.sh | bash -s y
```

This will install all necessary dependencies and start a small demo project.

---

Alternatively you can clone the repository and review the install script before executing.

```bash
git clone https://github.com/carmelosantana/rok-monster-ocr/
cd rok-monster-ocr
sudo bash install.sh
```

### Manual

Requirements:

- [PHP](https://www.php.net/manual/en/install.php)
  - GD extension
- [Composer](https://getcomposer.org/)
- [ImageMagick](https://imagemagick.org/)
- [Tesseract](https://github.com/tesseract-ocr/tesseract)
  - [Tessdata](https://github.com/tesseract-ocr/tessdata)

#### Software

This assumes you already have [PHP](https://www.php.net/manual/en/install.php) installed with access to [Composer](https://getcomposer.org/).

```bash
sudo apt install imagemagick tesseract-ocr
```

*This does not represent the complete install instructions for all dependencies. Please review the [install script](https://github.com/carmelosantana/rok-monster-ocr/blob/master/install.sh) for detailed installation instructions.*

#### Tessdata

Using [tessdata](https://github.com/tesseract-ocr/tessdata) or [tessdata_best](https://github.com/tesseract-ocr/tessdata_best) models from the [Tesseract](https://github.com/tesseract-ocr) repositories have produced better results than the models provided by `apt-get`. You can download select languages or clone the repository and set this path with in the `.env` file or via CLI argument `--tessdata`.

Alternatively you can install via `apt-get`. Some issues may include:

- Incorrect user permissions
- Models don't work with legacy engine
- Can be less accurate

```bash
sudo apt install tesseract-ocr-all
```

#### rok-monster-ocr

```bash
git clone https://github.com/carmelosantana/rok-monster-ocr
cd rok-monster-ocr
composer install
```

## Community Installs

These install methods are contributions made available by the community with no official support. It's the contributor's discretion to offer support via [Discord](https://discord.gg/drhxwVQ).

| Method | Author                              | README                               |
| ------ | ----------------------------------- | ------------------------------------ |
| Docker | [xor22h](https://github.com/xor22h) | [docker.md](./docs/docker.md) |

## Usage

### Start a job via CLI

1. Capture the necessary screens specified per the given job. In this example we need the **Governor More Info** profile screen.
2. Move all images to your "Input Path" as defined in your `.env` file or via CLI argument `--input_path`.
    >*By default a "Media" folder is created in your current working directory if no directory is supplied.*
3. Run job:

    ```bash
    php rok.php --job=governor-more-info-kills --input_path=DIR_WITH_SCREENSHOTS
    ```

    | Value                      | Description                                     |
    | -------------------------- | ----------------------------------------------- |
    | `governor-more-info-kills` | Default job for collecting kill points.         |
    | `DIR_WITH_SCREENSHOTS`     | Full or relative path to DIR with screen shots. |

4. Check your current working directory for any output files.

## Arguments

| Argument          | Value    | Default              | Description                                                                                                  |
| ----------------- | -------- | -------------------- | ------------------------------------------------------------------------------------------------------------ |
| debug             | `bool`   | `0`                  | Prints raw OCR reading per image. Uses local `--tmp_path` and preserves cropped images.                      |
| job               | `string` | *Required*           | Name of job defined as defined in [rok-monster-schema](https://github.com/carmelosantana/rok-monster-schema) |
| input_path        | `string` | *Required*           | Media source path or file                                                                                    |
| tmp_path          | `string` | `sys_get_temp_dir()` | Temp directory for images manipulated during processing                                                      |
| oem               | `int`    | `0`                  | OCR Engine Mode                                                                                              |
| psm               | `int`    | `0`                  | Page Segmentation Method                                                                                     |
| tessdata          | `string` | `null`               | User defined location for tessdata. Defaults to system installation path.                                    |
| compare_to_sample | `bool`   | `1`                  | Enable compare to sample                                                                                     |

- *bool as `0\1` or `true\false` or `on\off`*

## Supporters

Honorable mentions for [community](https://discord.gg/drhxwVQ) members who've donated time and resources to **rok-monster-ocr** or [rokmonster.com](https://rokmonster.com).

- BouchB
- j7johnny
- Star Lordツ

## Funding

If you find this project useful or use it ~~in a commercial environment~~ to manage your kingdom please consider donating today with one of the following options.

- [PayPal](https://www.paypal.com/donate?hosted_button_id=EKK8CQTPJG7WL)
- Bitcoin `bc1qx7v5vvxwnhpl3dssggy0hytcx2rpq5dkkfwyy4`
- Ethereum `0xA8Ebb6e5EC503E90551dD1bdE2d00B6C126eD5C5`
- Tron `TPnGEfkUZ2py6CFkh8wgqqYehJ29EbMWVw`

## License

The code is licensed [MIT](https://opensource.org/licenses/MIT) and the documentation is licensed [CC BY-SA 4.0](https://creativecommons.org/licenses/by-sa/4.0/).

> *[Rise of Kingdoms](https://rok.lilithgames.com/en) is a trademark of Lilith Games.*
