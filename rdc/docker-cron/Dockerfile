FROM alpine:3.15

RUN apk add --no-cache tzdata logrotate

COPY ./entrypoint.sh /
COPY ./logrotate /etc/logrotate.d

RUN chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]