# Working with External Modules

This directory is mounted as a [volume](https://docs.docker.com/storage/volumes/) in your REDCap instance. The source code for any module you download will appear in this directory in its own subdirectory with its [semantic version](https://semver.org/) appended to the directory name, multiple versions of the same module will have distinct directories for each version.

## Installing External Modules to this directory
If you are developing your own - or improving someone else's - module, you should `git clone` the repository (if it already exists) to this directory to use it in your REDCap Docker instance. After cloning or creating the module you are doing development work on, you will need to append a version to it as `_vX.Y.Z` or REDCap will not recognize it. `0.0.0` is encouraged for development versions of modules (e.g. a module named My Custom Module should be stored in a directory named `my_custom_module_v0.0.0`).  
This can be done on the command line in this directory:
```bash
mv my_custom_module my_custom_module_v0.0.0
```
Once you have manually added a module directory here it may be activated in the **Control Center** under **External Modules**.
