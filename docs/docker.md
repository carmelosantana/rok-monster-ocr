# Docker based setup

**This guide is won't cover how to install docker**

If you are familiar with docker, there is a way to run this tool inside a container.

Requirements:

* Docker Desktop (Or Docker-CE) install
* `./media/` folder with assets you want to scan;
* Instructions below assumes you have `media/` folder in your current working dir.

## Running docker based setup

```bash
export IMAGE="ghcr.io/carmelosantana/rok-monster-ocr:main"
export TEMPLATE="gov-more-info-kills"
# make sure you have `examples` folder in your current working dir.
# this mounts `examples` folder to `/app/media` inside the image
docker run -it -v $(pwd)/examples:/app/media $IMAGE $TEMPLATE_NAME
```


## Building new docker image

```bash
# building image (you need to do this once, it will take a while)
export IMAGE="rok-ocr"
export TEMPLATE="gov-more-info-kills"
docker build -t $IMAGE .

# using built image (mount folder with images into /app/media)
docker run -it -v $(pwd)/media:/app/media $IMAGE $TEMPLATE
```
