FROM golang:alpine AS builder

MAINTAINER andy123@stanford.edu

# Install MailHog:
RUN apk --no-cache add --virtual build-dependencies \
    git \
  && mkdir -p /root/gocode \
  && export GOPATH=/root/gocode \
  && go get github.com/mailhog/MailHog \
  && mv /root/gocode/bin/MailHog /usr/local/bin \
  && rm -rf /root/gocode \
  && apk del --purge build-dependencies

RUN adduser -D -u 1000 mailhog

WORKDIR /home/mailhog

COPY entrypoint.sh /
RUN chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]

EXPOSE 1025 8025
