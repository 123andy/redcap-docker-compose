FROM alpine:3.8

RUN apk add --no-cache tzdata logrotate
ENV TZ America/Los_Angeles

COPY entrypoint.sh /
RUN chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]
