<?php

require_once 'vendor/autoload.php';

use Fortnite\Auth;

$auth = Auth::login('email@email.email', 'password');

$i = 1;
foreach ($auth->store->get()->storefronts as $data) {
    if (isset($data->catalogEntries[0]->title)) {
        if (strpos($data->catalogEntries[0]->title, 'Llama') !== false) {
            if ($i > 1) {
                echo $data->catalogEntries[0]->title . "\n";
                echo $data->catalogEntries[0]->prices[0]->finalPrice . " Vbucks ";
                echo "[ Limit: " . $data->catalogEntries[0]->dailyLimit . " ]\n";
                echo $data->catalogEntries[0]->description . "\n\n";
            } else { }
            $i++;
        }
    }
}

/**
 * Try out your own way to get the data, or use my bot ;)
 * https://fortnite.tchverheul.nl/
 */
