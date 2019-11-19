# Installing External Modules to this Directory

This directory is mounted as a [volume](https://docs.docker.com/storage/volumes/) in your REDCap instance. The source code for any module you download will appear in this directory in its own subdirectory with its [semantic version](https://semver.org/) appended to the directory name, multiple versions of the same module will have distinct directories for each version.  
If you are developing your own module, you should copy the repository to this location to use it in your REDCap docker instance, `0.0.0` is encouraged for development versions of modules (e.g. the folder should appear as `modules/my_custom_module_v0.0.0`).
