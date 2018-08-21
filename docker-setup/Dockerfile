FROM alpine:latest

MAINTAINER andy123@stanford.edu

RUN apk add --no-cache unzip \
  mysql-client \
  && rm -rf /tmp/*

COPY entrypoint.sh /
RUN chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]
