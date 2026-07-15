<?php

return [
    'admin_password' => env('ADMIN_PASSWORD'),
    'email_whitelist' => array_map(
        trim(...),
        explode(',', env('SIGN_UP_EMAIL_WHITELIST', 'ryanennns@gmail.com')),
    ),
];
