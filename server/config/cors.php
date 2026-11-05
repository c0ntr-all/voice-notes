<?php

return [

    /*
    | Токен передаётся в Authorization. Куки не используются, поэтому
    | credentials выключены: браузер не принимает их вместе с origin *.
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
