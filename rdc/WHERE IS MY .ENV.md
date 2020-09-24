Most file explorers hide "dot" files.  But, trust me, they are there.  This folder contains a .env-example file that should be copied and used to customize REDCap Docker Compose to your environment.  

On a mac, use the terminal app, as in:
```
$ cd redcap-docker-compose/rdc
$ ls -latr
-rw-r--r--   1 andy123  staff  1068 Sep 23 14:07 LICENSE
-rw-r--r--   1 andy123  staff  2912 Sep 23 14:07 docker-compose.yml
drwxr-xr-x   4 andy123  staff   128 Sep 23 14:07 docker-cron
drwxr-xr-x   5 andy123  staff   160 Sep 23 14:07 docker-mailhog
drwxr-xr-x   3 andy123  staff    96 Sep 23 14:07 docker-mysql
drwxr-xr-x   5 andy123  staff   160 Sep 23 14:07 docker-web
drwxr-xr-x   7 andy123  staff   224 Sep 23 14:07 documentation
drwxr-xr-x   7 andy123  staff   224 Sep 23 14:07 redcap-overrides
drwxr-xr-x   9 andy123  staff   288 Sep 23 14:07 ..
-rw-r--r--   1 andy123  staff  6602 Sep 23 14:25 .env-example
-rw-r--r--   1 andy123  staff     0 Sep 23 14:26 WHERE IS MY .ENV.md

$ cp .env-example .env
```

On a Windows PC:  
The file explorer will likely refuse to open any dot file, but [git-bash](https://gitforwindows.org/) or any IDE with a builtin terminal should allow you to run the commands above.
