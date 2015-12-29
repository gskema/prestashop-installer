# PrestaShop Installer

A CLI tool which downloads and extracts PrestaShop to a specified directory.
Can install alternative demo images.

Inspired by [laravel/installer](https://github.com/laravel/installer).

## Usage

To install this tool on your system, run:

```
composer global require "gskema/prestashop-installer=~2.0"
```

Make sure to place the `~/.composer/vendor/bin` directory in your PATH
so the `prestashop` executable can be located by your system.

Once installed, you may create new prestashop installation with this command:

```
Usage:
  prestashop new [<folder>] [--release=<release>] [--fixture=<fixture>]

Options:
  --release  Sets which PrestaShop release archive to download.
             Some examples of values: 1.6.1.3, 1.6.0.9, 1.6.1.0-rc4, 1.5.6.3
  --fixture  Overrides demo data images: product, category, banner images with specified fixture images.
             Available values: [starwars, got, tech]

Examples:
  prestashop new                                       // Downloads latest PrestaShop to current directory
  prestashop new shop1                                 // Downloads latest PrestaShop to ./shop1 directory
  prestashop new shop1 --release=1.6.0.9               // Downloads PrestaShop 1.6.0.9 to ./shop1 directory
  prestashop new shop1 --release=1.6.0.9 --fixture=got // Downloads PrestaShop 1.6.0.9 to ./shop1 directory
                                                       // and replaces demo data images
                                                       // with Game of Thrones images
```
