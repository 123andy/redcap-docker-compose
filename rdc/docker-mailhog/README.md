<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Contents**  *generated with [DocToc](https://github.com/thlorenz/doctoc)*

- [MailHog docker image](#mailhog-docker-image)
  - [Changelog](#changelog)
  - [Links](#links)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

# MailHog docker image

Here is an unofficial Dockerfile for [MailHog][mailhog].

It is based on an image by [tophfr/docker-mailhog][dockerhubpage] and uses [Alpine][alpinehubpage] and customized by andy123.

## Changelog
- 2022-10-06 Fixed go compile issues
- 2018-08-03 Changed entrypoint.sh script to set permissions for maildir volume if present

## Links

  [mailhog]: https://github.com/mailhog/MailHog/ "Web and API based SMTP testing" 
  [dockerhubpage]: https://hub.docker.com/r/tophfr/mailhog/ "MailHog docker hub page"
  [alpinehubpage]: https://hub.docker.com/_/alpine/ "A minimal Docker image based on Alpine Linux with a complete package index and only 5 MB in size!"