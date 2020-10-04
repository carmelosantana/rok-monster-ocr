# Rise of Kingdoms CLI Tools

Command lines tools to construct data for [rok.monster](https://rok.monster). By analyzing recorded game play we can extract various data points such as governor power, deaths, kills and more. This can help for various kingdom statistics or fairly distributing rewards during [KvK](https://rok.guide/the-lost-kingdom-kvk/).

## Requirements

- Ability to record game play.
- Game play and recording recommend resolution of at least 1920x1080.
- php 7.4
- Tesseract
- FFmpeg

## Example

As an example we'll use the Governor More Info screen as our source for data. 

This will give us the following data:

- Name
- Power
- Total kills
- Deaths
- Kills (per troop type)

1. Record the governor(s) **More Info** screen located in their profile. Kills per troop type can also be captured by pressing **(?)** by total kills.
2. Copy video to `ROK_PATH_INPUT` as defined in `config.php`.
3. Run job:

    ```bash
    php rok.php --job=governor_more_info
    ```

4. Check `ROK_PATH_OUTPUT` for files containing Governor statistics.
