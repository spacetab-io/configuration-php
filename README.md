PHP Configuration
-----------------

[![CircleCI](https://circleci.com/gh/spacetab-io/configuration-php/tree/master.svg?style=svg)](https://circleci.com/gh/spacetab-io/configuration-php/tree/master)
[![codecov](https://codecov.io/gh/spacetab-io/configuration-php/branch/master/graph/badge.svg)](https://codecov.io/gh/spacetab-io/configuration-php)

Configuration module for PHP that supports multiple application stages like `dev`, `prod` or `something else` 
and overrides the `defaults` stage.

## Installation

```bash
composer require spacetab-io/configuration
```

## Usage

By default, path to configuration directory and application stage
loading from `/app/configuration` with `local` stage.

1) Simple
```php
<?php
use Spacetab\Configuration\Configuration;

$conf = new Configuration();
$conf->load();

var_dump($conf->all()); // get all config
echo $conf->get('foo.bar'); // get nested key use dot notation
echo $conf['foo.bar']; // the same, but use ArrayAccess interface.
```

Supported dot-notation syntax with an asterisk in `get` method. 
You can read about it here: https://github.com/spacetab-io/obelix-php

2) If u would like override default values, you can pass 2 arguments to
class constructor or set up use setters.

```php
<?php
use Spacetab\Configuration\Configuration;

$conf = new Configuration(__DIR__ . '/configuration', 'test');
$conf->load();

$conf->get('key'); // full example on the top
```

3) If the operating system has an env variables `CONFIG_PATH` and `STAGE`,
then values for the package will be taken from there.

```bash
export CONFIG_PATH=/configuration
export STAGE=prod
```

```php
<?php
use Spacetab\Configuration\Configuration;

$conf = new Configuration();
$conf->load(); // loaded files from /configuration for prod stage.

$conf->get('key'); // full example on the top
```

4) If u want to see logs and see how load process working,
pass you application logger to the following method:

```php
<?php
use Spacetab\Configuration\Configuration;

$conf = new Configuration();
$conf->setLogger($monolog); // PSR compatible logger.
$conf->load();

$conf->get('key'); // full example on the top
```

If you want you can use [this package](https://github.com/spacetab-io/logger-php) for logs:

```bash
composer require spacetab-io/logger
```

That all.

## CLI utility

Also, you can install simple small cli-utility to dump total results of merged config.
It possible with multiple ways:

1) Install to `/usr/local/bin` as global binary

```bash
L=/usr/local/bin/st-conf && sudo curl -L https://github.com/spacetab-io/configuration-php/releases/download/2.2.0/st-conf.phar -o $L && sudo chmod +x $L
```

2) Install library as global composer requirements

First step:
```bash
composer global require spacetab-io/configuration-php
```

It will be installed to `~/.composer` directory.

If you have `~/.composer/vendor/bin` in globals path, you can try run command:
```bash
st-conf help dump
```

Otherwise, you can be register that directory:
```bash
echo 'export PATH=~/.composer/vendor/bin:$PATH' >> ~/.bash_profile
source ~/.bash_profile
```

### CLI Usage

```bash
Description:
  Dump loaded configuration

Usage:
  dump [options] [--] [<path> [<stage>]]

Arguments:
  path                   Configuration directory path
  stage                  Configuration $STAGE

Options:
  -l, --inline[=INLINE]  The level where you switch to inline YAML [default: 10]
  -s, --indent[=INDENT]  The amount of spaces to use for indentation of nested nodes [default: 2]
  -d, --debug            Debug
  -h, --help             Display this help message
  -q, --quiet            Do not output any message
  -V, --version          Display this application version
      --ansi             Force ANSI output
      --no-ansi          Disable ANSI output
  -n, --no-interaction   Do not ask any interactive question
  -v|vv|vvv, --verbose   Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  Example of usage: `st-conf dump`. Options --inline=10 (nesting level) and --indent=2. If [path] and [stage] arguments not passed will be used global env variables CONFIG_PATH and STAGE.
```

## Depends

* \>= PHP 7.4 (for prev version use `2.x` releases of library)
* Composer for install package

## License

The MIT License

Copyright © 2020 spacetab.io, Inc. https://spacetab.io

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
