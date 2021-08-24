FROM alpine:3.8

RUN apk add --no-cache tzdata logrotate

COPY ./entrypoint.sh /
RUN chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]