<?php

use Laravel\Fortify\Features;

return [
    'guard' => 'web', 'passwords' => 'users', 'username' => 'email', 'email' => 'email',
    'home' => '/dashboard', 'prefix' => '', 'domain' => null, 'middleware' => ['web'], 'views' => true,
    'features' => [Features::registration(), Features::resetPasswords(), Features::emailVerification(), Features::twoFactorAuthentication(['confirm' => true, 'confirmPassword' => true])],
];
