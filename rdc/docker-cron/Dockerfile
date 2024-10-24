FROM alpine:3.20

RUN apk add --no-cache tzdata logrotate

COPY ./entrypoint.sh /
COPY ./logrotate /etc/logrotate.d

ADD crontabs /var/spool/cron/crontabs/root

RUN chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]