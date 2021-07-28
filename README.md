Laravel log to json
==================


[![Author](https://img.shields.io/static/v1?label=Author&message=linkedin&color=<BLUE>)](https://www.linkedin.com/in/behnam-hoseyni-904949164/)
[![Required Laravel Version][ico-laravel]][link-packagist]
[![Required PHP Version][ico-php]][link-packagist]
[![Latest Version on Packagist][ico-version]][link-packagist]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]
[![Today Downloads][ico-today-downloads]][link-downloads]

## Description
Convert Laravel log output to json

## <g-emoji class="g-emoji" alias="arrow_down" fallback-src="https://github.githubassets.com/images/icons/emoji/unicode/2b07.png">⬇️</g-emoji> Installation

You can install the package via composer:

```bash
composer require behnamhosseini/log-to-json
```

You may also publish config file:
```
php artisan vendor:publish
```

## Usage

All you have to do is change a line of code in (`app\Exceptions\Handler.php`) and then you can implement the settings from the configuration file you published (config / logToJson).

```
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
```
to
```
use Behnamhosseini\LogToJson\Handler as ExceptionHandler;

```
