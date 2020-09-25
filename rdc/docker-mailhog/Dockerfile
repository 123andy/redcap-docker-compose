FROM alpine:3.7

RUN set -x \
  && adduser -D -u 1000 mailhog \
  && apk add --no-cache ca-certificates \
  && apk add --no-cache --virtual build-dependencies go git musl-dev \
  && mkdir -p /tmp/gocode \
  && GOPATH=/tmp/gocode go get github.com/mailhog/MailHog \
  && mv /tmp/gocode/bin/MailHog /usr/local/bin/ \
  && apk --no-cache del --purge build-dependencies \
  && rm -rf /tmp/*

EXPOSE 1025 8025

COPY entrypoint.sh /
RUN chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]
