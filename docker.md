# How to use docker based runtime

```bash
# building image (you need to do this once, it will take a while)
export IMAGE="rok-ocr"
docker build -t $IMAGE .

# using built image (mount folder with images into /app/media)
docker run -it -v $(pwd)/media:/app/media $IMAGE governor-info-kills
```
