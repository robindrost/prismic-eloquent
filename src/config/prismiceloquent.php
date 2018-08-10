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
    | Document resolver
    |--------------------------------------------------------------------------
    |
    | These settings apply to the document resolver. Please change the settings
    | bellow if you do not use the default namespace \App\ModelName or use
    | custom model names that do not match the content type.
    |
    */
    'document_resolver' => [
        /*
        |--------------------------------------------------------------------------
        | Content type namespace.
        |--------------------------------------------------------------------------
        |
        | You can either change it here for global use, or specify it on a custom
        | document resolver that is returned from the getDocumentResolver in your
        | models.
        |
        | You can also leave the namespace empty and define full class paths
        | below.
        */
        'namespace' => '\\App\\',

        /*
        |--------------------------------------------------------------------------
        | Content type model relationship.
        |--------------------------------------------------------------------------
        |
        | By default the document resolver will transform a document to a
        | model based on the name content type name of the document.
        |
        | You will need to specify the content type and model in case your models
        | do not match content types.
        |
        */
        'models' => [
            // 'content_type_name' => ModelName::class
        ],
    ],
];
