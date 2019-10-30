<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Prismic repository api url.
    |--------------------------------------------------------------------------
    |
    | This value is the api url of your Prismic repository. You can find this
    | url on: https://REPOSITORY-NAME.prismic.io/settings/apps/
    |
 */

    'url' => env('PRISMIC_ELOQUENT_URL', ''),
    /*
    |--------------------------------------------------------------------------
    | Prismic repository access token.
    |--------------------------------------------------------------------------
    |
    | This value is the api access token of your Prismic repository. You
    | can find this url on: https://REPOSITORY-NAME.prismic.io/settings/apps/
    |
     */
    'access_token' => env('PRISMIC_ELOQUENT_ACCESS_TOKEN', ''),
    /*
    |--------------------------------------------------------------------------
    | Caching layer
    |--------------------------------------------------------------------------
    |
    | These are the caching options. By default caching is disabled. The
    | default caching layer is provided by this package. You do not have to
    | configure anything else. The caching layer uses your configured laravel
    | caching options.
    |
    | Please see the service provider on how to override the caching layer.
    |
     */
    'cache' => [
        // Enable or disable the caching layer.
        'enabled' => env('PRISMIC_ELOQUENT_ENABLE_CACHE', false),

        // This is the prefix string used on cache keys.
        'prefix' => 'prismiceloquent',

        // Time to live of the cache. Setting this value to 0 will cache it 'forever'.
        'ttl' => 300,
    ],
];
