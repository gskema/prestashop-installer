# PrestaShop Installer

[![Build Status](https://travis-ci.org/gskema/prestashop-installer.svg?branch=master)](https://travis-ci.org/gskema/prestashop-installer)
[![Join the chat at https://gitter.im/gskema/prestashop-installer](https://badges.gitter.im/gskema/prestashop-installer.svg)](https://gitter.im/gskema/prestashop-installer?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
[![Packagist](https://img.shields.io/packagist/dt/gskema/prestashop-installer.svg)]()
[![GitHub release](https://img.shields.io/github/release/gskema/prestashop-installer.svg)]()

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

**Warning!**

Installing multiple global composer tools may cause dependency conflicts.
You may need to install global tools separately and use `bin-dir`
**composer.json** option (if you wish to avoid dependency conflicts).

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

## Fixture screenshots

### Star Wars
![Fixture - Star Wars](http://i.imgur.com/lCw0nQh.png "Demo data fixture: Star Wars")

### Game of Thrones
![Fixture - Game of Thrones](http://i.imgur.com/GuPah7n.png "Demo data fixture: Game of Thrones")

### Tech - Electronics
![Fixture - Tech](http://i.imgur.com/kykWw06.png "Demo data fixture: Technology, Electronics")
