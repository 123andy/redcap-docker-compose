# MailHog docker image

Here is an unofficial Dockerfile for [MailHog][mailhog].

It is based on an image by [tophfr/docker-mailhog][dockerhubpage] and uses [Alpine][alpinehubpage] and customized by andy123.

## Changelog

- 2018-08-03 Changed entrypoint.sh script to set permissions for maildir volume if present

## Links

  [mailhog]: https://github.com/mailhog/MailHog/ "Web and API based SMTP testing" 
  [dockerhubpage]: https://hub.docker.com/r/tophfr/mailhog/ "MailHog docker hub page"
  [alpinehubpage]: https://hub.docker.com/_/alpine/ "A minimal Docker image based on Alpine Linux with a complete package index and only 5 MB in size!"