# CHANGELOG

---

<!-- TOC -->

* [1.0.0](#100)
    * [1.0.1](#101)
    * [1.0.2](#102)
    * [1.0.3](#103)

<!-- TOC -->

---

## 1.0.3

### General

- Optimized project's lib package with gitattributes

## 1.0.2

### General

- Added alias support to "locale" variables
- Added the configuration attribute `use_locale_chain` for if it needs to use multiple locales or one
    - Defaults to `false`, set in getters
- Updated templates
    - Now uses PHP markers

### Fixes

- Set the former locale before calling "add...I18n" in `getTranslation`

## 1.0.1

### General

* Updated CI and unit tests
* Updated declaring classes

### Fixes

* Fixed a `str_replace` case in `addTranslatedColumnGetter`

## 1.0.0

### General

* Removed deprecated content introduced in PHP 8.4 and 8.5
* Updated `propel/propel` minimum version to latest stable release (2.0.0) for PHP 8.0 and later
    * Updated `propel/propel` minimum version to beta release (2.0.0-beta4) for PHP 7.4
* Added manual localization on getter and setter methods of Propel
* Refactored a few methods
* Cleaned up templates
