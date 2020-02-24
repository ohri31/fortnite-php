# Fortnite-PHP Wrapper
Interact with the official Fortnite API using PHP.

[![Packagist](https://img.shields.io/packagist/l/doctrine/orm.svg)]()
[![Packagist](https://img.shields.io/packagist/v/Tustin/fortnite-php.svg)]()

## Installation
Pull in the project using composer:
`composer require Tustin/fortnite-php`

## Usage
Create a basic test script to ensure everything was installed properly
```php
<?php

require_once 'vendor/autoload.php';

use Fortnite\Auth;
use Fortnite\Account;
use Fortnite\Mode;
use Fortnite\Language;
use Fortnite\Platform;

// Authenticate
$auth = Auth::login('epic_email@domain.com','password');

// Output each stat for all applicable platforms
var_dump($auth->stats);

// Grab someone's stats
$sandy = $auth->stats->lookup('sandalzrevenge');
s
```


## Contributing
Fortnite now utilizes SSL certificate pinning in their Windows client in newer versions. I suggest using the iOS mobile app to do any future API reversing as both cheat protections on the Windows client make it difficult to remove the certificate pinning. If SSL certificate pinning is added to the iOS version, I could easily provide a patch to remove that as the iOS version doesn't contain any anti-cheat.
