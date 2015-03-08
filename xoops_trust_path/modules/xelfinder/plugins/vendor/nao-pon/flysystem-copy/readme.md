# Flysystem Adapter for AWS S3 SDK v2

[![Author](http://img.shields.io/badge/author-@frankdejonge-blue.svg?style=flat-square)](https://twitter.com/frankdejonge)
[![Build Status](https://img.shields.io/travis/thephpleague/flysystem-copy/master.svg?style=flat-square)](https://travis-ci.org/thephpleague/flysystem-copy)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/thephpleague/flysystem-copy.svg?style=flat-square)](https://scrutinizer-ci.com/g/thephpleague/flysystem-copy/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/thephpleague/flysystem-copy.svg?style=flat-square)](https://scrutinizer-ci.com/g/thephpleague/flysystem-copy)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Packagist Version](https://img.shields.io/packagist/v/league/flysystem-copy.svg?style=flat-square)](https://packagist.org/packages/league/flysystem-copy)
[![Total Downloads](https://img.shields.io/packagist/dt/league/flysystem-copy.svg?style=flat-square)](https://packagist.org/packages/league/flysystem-copy)


## Installation

```bash
composer require league/flysystem-copy
```

## Usage

```php
use Barracuda\Copy\API;
use League\Flysystem\Filesystem;
use League\Flysystem\Copy\CopyAdapter as Adapter;

$client = new API($consumerKey, $consumerSecret, $accessToken, $tokenSecret);
$filesystem = new Filesystem(new Adapter($client, 'optional/path/prefix'));
```
