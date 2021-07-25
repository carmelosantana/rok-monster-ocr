# for configuration using --build-arg
ARG TAG=20.04
ARG TZ=UTC

FROM ubuntu:${TAG}
# Set default env
ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=${TZ}

# preload script dependencies
RUN apt-get update && apt-get upgrade -y && apt-get install -y sudo software-properties-common wget curl tzdata

# add code
ADD . /app
WORKDIR /app

# run installer
RUN bash ./install.sh y

# set helper script as entrypoint
ENTRYPOINT [ "/app/docker-entrypoint.sh" ]
