# PrestaShop Installer

A CLI tool which downloads and extracts PrestaShop to a specified directory.

Inspired by [laravel/installer](https://github.com/laravel/installer).

## Usage

To install this tool on your system, run:

```
composer global require "gskema/prestashop-installer=~1.0"
```

Make sure to place the ~/.composer/vendor/bin directory in your PATH
so the `prestashop` executable can be located by your system.

Once installed, you may create new prestashop installation with this command:

```
prestashop new my-shop
```

## Options

A specific **release** version to be downloader may be specified via argument:

```
prestashop new <folder> --release=1.6.0.9
```

If **release** version is not provided, the latest PrestaShop will be downloaded.