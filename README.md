# PrestaShop Installer

A CLI tool which downloads and extracts PrestaShop to a specified directory.

Inspired by [laravel/installer](https://github.com/laravel/installer).

## Usage

To install PrestaShop, run this command:

```
./prestashop new my-shop
```

or

```
php prestashop new my-shop
```

## Options

A specific version may be provided as an optional second argument:

```
new <folder> [<version>]
```

If *version* is not provided, the latest PrestaShop version will be downloaded.