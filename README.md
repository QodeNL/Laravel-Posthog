# Laravel Posthog implementation

[![Latest Version on Packagist](https://img.shields.io/packagist/v/QodeNL/laravel-posthog.svg?style=flat-square)](https://packagist.org/packages/QodeNL/laravel-posthog)
[![Total Downloads](https://img.shields.io/packagist/dt/QodeNL/laravel-posthog.svg?style=flat-square)](https://packagist.org/packages/QodeNL/laravel-posthog)

This package provides a simple integration of Posthog in Laravel applications. 

The package covers both Identify as Capture (events) requests which can be triggered manual or automatically using an Event Listener. 

You can also easily integrate Feature Flags within your application.

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

### Alias 

If you want to assign a session ID to a user (for example a front-end session ID) you can use the `alias` method. 

The Session ID argument will be assigned to the auto-generated ID of the user.

```php
Posthog::alias('Session ID here');
```

## Feature flags

### Laravel Pennant

This package also includes a custom driver for the [Laravel Pennant](https://laravel.com/docs/11.x/pennant) package. With this custom driver, you can use Laravel Pennant and listen to the feature flags set in Posthog. 

To use this driver, simply add the following to your .env file. You don't need to create the database migration.
```text
PENNANT_STORE=posthog
```

Also, add the store to the `stores` array in `config/pennant.php`:

```php
    'stores' => [
        'posthog' => [
            'driver' => 'posthog',
        ],
    ],
```

You can use the Laravel Pennant package as you would normally do. However, some features, like enabling a feature for a user, are not supported by the Posthog driver. A `PosthogFeatureException` will be thrown when you try to use these features.

#### Example: Check if feature is enabled

```php
Laravel\Pennant\Feature::active('myFeatureFlagKey'); // true
```

### Check feature flags

If you don't want to use Laravel Pennant, you can also implement the feature flags using our Facade:

#### Get all feature flags

```php
use QodeNL\LaravelPosthog\Facades\Posthog;

Posthog::getAllFlags();
```

Get all feature flags with boolean if enabled for user or not.

#### Check if feature is enabled

```php
use QodeNL\LaravelPosthog\Facades\Posthog;

Posthog::isFeatureEnabled('myFeatureFlagKey');
```

Check if feature is enabled for user. Returns boolean.

#### Get feature flag

```php
use QodeNL\LaravelPosthog\Facades\Posthog;

Posthog::getFeatureFlag('myFeatureFlagKey');
```

Get feature flag. Returns `false` if feature is disabled. Returns `true` (or `payload` if set).  

#### Optional attributes

You can pass `groups`, `personProperties` and `groupProperties` to the isFeatureEnabled and getFeatureFlag functions. 

Please check the [Posthog PHP documentation](https://posthog.com/docs/libraries/php#advanced-overriding-server-properties) for more information. 

In the Posthog config you can configure if [events](https://posthog.com/docs/libraries/php#method-2-set-send_feature_flags-to-true) should be sent to Posthog and if you want to [evaluate events locally](https://posthog.com/docs/libraries/php#local-evaluation).

### Queue / jobs

The capture, identify and alias actions are executed by jobs. Be sure you've enabled and configured [queues](https://laravel.com/docs/10.x/queues) for your applications.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- Christian Schoenmakers (Qode BV - Netherlands) (https://github.com/christianschoenmakers)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.