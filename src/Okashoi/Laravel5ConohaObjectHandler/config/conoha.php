<?php

return [
    'tenant_id'     => env('CONOHA_TENANT_ID'),
    'username'      => env('CONOHA_USERNAME'),
    'password'      => env('CONOHA_PASSWORD'),
    'base_uri'      => env('CONOHA_BASE_URI', 'https://object-storage.tyo1.conoha.io/v1/'),
    'auth_endpoint' => env('CONOHA_AUTH_ENDPOINT', 'https://identity.tyo1.conoha.io/v2.0/tokens'),
];
