<?php

return [
    'email_whitelist' => array_map(
        trim(...),
        explode(',', env('SIGN_UP_EMAIL_WHITELIST', 'ryanennns@gmail.com')),
    ),
];
