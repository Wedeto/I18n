# Changelog

## 1.1.2

- Add modern serialization methods to plural rules while retaining the legacy serialization API.
- Declare money formatter state explicitly for PHP 8.2 and newer.

## 1.1.1

- Fix the translator helper signature so optional parameters do not precede required parameters.

## 1.1.0

- Add support for PHP 8.1 through PHP 8.5.
- Retain support for PHP 7.4 while explicitly excluding PHP 8.0.
- Update dependencies to Util 1.1.1 and Log and IO 1.1.
- Declare the mbstring requirement and update the test suite to PHPUnit 9.6.
