# WEB Customization

* The apache2 folder here can be used to set apache conf and sites.
Any provided entries will overlay on the default
 filesystem.

* The php folder can be used to inject `.ini` files into the `php/conf.d` directory
 
* the startup-scripts is useful for any file moving or other changes.  By default, the contents
of this folder will be located in `/etc/container-config-override` inside the web container
