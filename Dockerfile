FROM ubuntu:20.10
ENV TZ=Europe/Vilnius
ENV DEBIAN_FRONTEND=noninteractive

# preload script dependencies
RUN apt update && apt upgrade -y && apt install -y sudo software-properties-common wget curl tzdata

# add code
ADD . /app
WORKDIR /app

# run installer
RUN bash ./install.sh y

# set helper script as entrypoint
ENTRYPOINT [ "/app/docker-entrypoint.sh" ]
