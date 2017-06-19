<h1 align="center">Cuttle</h1>

<p align="center">:page_with_curl: A multi-module log wrapper.</p>

<p align="center">
<a href="https://travis-ci.org/overtrue/cuttle"><img src="https://travis-ci.org/overtrue/cuttle.svg?branch=master" alt="Build Status"></a>
<a href="https://packagist.org/packages/overtrue/cuttle"><img src="https://poser.pugx.org/overtrue/cuttle/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/overtrue/cuttle"><img src="https://poser.pugx.org/overtrue/cuttle/v/unstable.svg" alt="Latest Unstable Version"></a>
<a href="https://scrutinizer-ci.com/g/overtrue/cuttle/?branch=master"><img src="https://scrutinizer-ci.com/g/overtrue/cuttle/badges/quality-score.png?b=master" alt="Scrutinizer Code Quality"></a>
<a href="https://scrutinizer-ci.com/g/overtrue/cuttle/?branch=master"><img src="https://scrutinizer-ci.com/g/overtrue/cuttle/badges/coverage.png?b=master" alt="Code Coverage"></a>
<a href="https://packagist.org/packages/overtrue/cuttle"><img src="https://poser.pugx.org/overtrue/cuttle/downloads" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/overtrue/cuttle"><img src="https://poser.pugx.org/overtrue/cuttle/license" alt="License"></a>
</p>


## Requirements

- PHP >= 5.5

## Installing

```shell
$ composer require "overtrue/cuttle"
```

## Usage

```php
use Overtrue\Cuttle\Cuttle;

$config = [
    'default' => 'foo', // default channel
        
    'formatters' => [
        'dashed' => [
            'formatter' => \Monolog\Formatter\LineFormatter::class, // default
            'format' => "%datetime% - %channel%.%level_name% - %message%\n" 
        ],
    ],
    'handlers' => [
        'file' => [
            'handler' => \Monolog\Handler\StreamHandler::class,  // default
            'formatter' => 'dashed',
            'stream' => '/tmp/demo.log',
            'level' => 'info',
        ],
        'console' => [
            'formatter' => 'dashed',
            'stream' => 'php://stdout',
            'level' => 'debug',
        ],
    ],
    'channels' => [
        'foo' => [
            'handlers' => ['console', 'file'],
        ],
        'bar' => [
            'handlers' => ['file'], 
        ],
    ],
];

$cuttle = new Cuttle($config);

$cuttle->info('hello'); // channel: foo
$cuttle->channel('bar')->debug('debug message.');

// aslias of channel($name)
// ->of('bar')
// ->from('bar')
```

## License

MIT