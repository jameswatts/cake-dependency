Quick Start
===========

This quick start guide will help you get ready to use the **Dependency** plugin in your application.

Requirements
------------

In order to use the plugin you must be using a *2.x* version of *CakePHP* on a server installed with *PHP* *5.3* or higher.

The plugin doesn't have any other additional requirements or dependencies.

Installation
------------

To install the plugin, place the files in a directory labeled "Dependency/" in your "app/Plugin/" directory.

If you're using **git** for version control, you may want to add the **Dependency** plugin as a submodule on your repository. To do so, run the following command from the base of your repository:

```
git submodule add git@github.com:jameswatts/cake-dependency.git app/Plugin/Dependency
```

After doing so, you will see the submodule in your changes pending, plus the file ".gitmodules". Simply commit and push to your repository.

To initialize the submodule(s) run the following command:

```
git submodule update --init --recursive
```

To retrieve the latest updates to the plugin, assuming you're using the "master" branch, go to "app/Plugin/Dependency" and run the following command:

```
git pull origin master
```

If you're using another branch, just change "master" for the branch you are currently using.

If any updates are added, go back to the base of your own repository, commit and push your changes. This will update your repository to point to the latest updates to the plugin.

The plugin also provides a "composer.json" file, to easily use the plugin through the **Composer** dependency manager.

Configuration
-------------

The plugin requires the bootstrap to load in order to store dependency configurations in the ```app/Config/dependency.php``` file. If you haven't already, add the following line to your bootstrap file:

```
CakePlugin::load('Dependency', array(
	'bootstrap' => true
));
```

