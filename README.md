# Laravel Posthog implementation

---
This package provides a simple integration of Posthog in Laravel applications. 

The small package covers both Identify as Capture (events) requests which can be triggered manual or automatically using an Event Listener. 

This package uses the [PostHog / posthog-php](https://github.com/PostHog/posthog-php) package. For more information about Posthog, check their [documentation](https://posthog.com/docs).

## Installation

You can install the package via composer:

```bash
composer require qodenl/laravel-posthog
```

You can publish the config file with:

```bash
php artisan vendor:publish --provider="QodeNL\LaravelPosthog\PosthogServiceProvider"  
```

After publishing the content, set your API key and Host in your .env file:

```bash
POSTHOG_KEY=
POSTHOG_HOST=https://posthog.com
POSTHOG_ENABLED=true
```

Make sure you copy the correct host from Posthog. 

Posthog is enabled by default, but you can disable it with the POSTHOG_ENABLED env variable. 

Make sure to disable Posthog for local/testing environments. 

## Usage

### Manual events

```php
use QodeNL\LaravelPosthog\Facades\Posthog;

Posthog::capture('event name', ['property' => 'value']);
```

### Automatic events 

You can add the `PosthogListener::class` listener to your `EventServiceProvider`. The package will create an capture automatically when the event happens. 

By default, all `fillable` attributes from a model (available in the event) will be sent to Posthog as properties.

You can specify which attributes you want to send to Posthog by adding a `PosthogAttributes` property to your Model.

```php
public $posthogAttributes = [
    'first_name',
    'last_name',
];
```

Attributes in the `hidden` property will always be ignored. 

### Identify

Events will be sent to Posthog with a unique ID for anonymous users. When the user is recognized (usually on log in), 
you should trigger the `identify` method to link the unique ID to the user.

You can pass additional information about the user to be stored in his profile. 

```php
use QodeNL\LaravelPosthog\Facades\Posthog;

Posthog::identify('email@user.com', ['first_name' => 'John', 'last_name' => 'Doe']);
```

### Queue / jobs

All above actions will be executed by jobs. Be sure you've enabled and configured [queues](https://laravel.com/docs/10.x/queues) for your applications. 

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- Christian Schoenmakers (Qode BV - Netherlands) (https://github.com/christianschoenmakers)
