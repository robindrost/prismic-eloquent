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
];
