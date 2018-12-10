## 1.2.0 ##

* Privacy API implemented. The plugin does not store any personal data.
* Fixed bug #87 - invalid language file name for activity modules.

## 1.1.1 ##

* Fixed a bug leading to generating the provider.php file with a syntax error in some
  cases.

## 1.1.0 ##

* Added support to generate privacy API related code (refer to cli/example.yaml).
  Special thanks to Michael Hughes for the initial implementation.
* Improved the component type and name fields usability - autodisplay the plugin type
  prefix so that it is more intuitive what the name field should hold.
* Added support to generate plugins requiring Moodle 3.4 and 3.5
* Make mustache loader path configurable, allowing better integration with moosh.
  Credit goes to Tomasz Muras.

## 1.0.0 ##

* Added support to generate plugins requiring Moodle 3.3 and 3.2.
* Added support for setting default values of some recipe file form fields
* Fixed the risk of having the generated ZIP file corrupted with debugging data
* Fixed some formal coding style violations


## 0.9.0 ##

* Initial version submitted to the Moodle plugins directory as a result of
  GSOC 2016
