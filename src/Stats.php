<?php
namespace Fortnite;

use Fortnite\FortniteClient;
use Fortnite\Account;

use Fortnite\Model\FortniteStats;

use Fortnite\Exception\UserNotFoundException;
use Fortnite\Exception\StatsNotFoundException;

use GuzzleHttp\Exception\GuzzleException;

class Stats {
    private $access_token;
    public $account_id;
    public $display_name;

    public $keyboardmouse;
    public $gamepad;
    public $touch;
    
    CONST SOLO_MODES = ["defaultsolo", "slide_solo", "bots_defaultsolo", "highexplosives_solo", "slide_solo", "deimos_solo_winter", "showdownalt_solo", "sneaky_solo", "flyexplosives_solo", "snipers_solo", "comp_solo", "ww_solo", "bounty_solo", "low_solo", "goose_solo_24", "blitz_solo", "close_solo", "bling_solo"];
    CONST DUO_MODES = ["defaultduo", "defaultduos", "bots_defaultduo", "bots_defaultduos", "highexplosives_duos", "slide_duos", "demios_duos_winter", "shutdownalt_duos", "sneaky_duos", "flyexplosives_duos", "snipers_duos", "comp_duos", "ww_duos", "bounty_duos", "low_duos", "goose_duos_24", "blitz_duo", "close_duo", "bling_duos"];
    CONST SQUAD_MODES = ["defaultsquad", "defaultsquads", "bots_defaultsquad", "bots_defaultsquads", "highexplosives_squads", "slide_squads", "deimos_squad_winter", "shutdownalt_squads", "sneaky_quads", "flyexplosives_quads", "snipers_quads", "comp_quads", "ww_quads", "bounty_quads", "low_quads","goose_quads_24","blitz_squad", "close_squad", "bling_squads"];

    /**
     * Constructs a new Fortnite\Stats instance.
     * @param string $access_token OAuth2 Access token
     * @param string $account_id   Epic account id
     */
    public function __construct($access_token, $account_id = null) 
    {
        $this->access_token = $access_token;
        $this->account_id = $account_id;
        $data = (!!$account_id) ? $this->fetch($this->account_id) : [];
        
        if (array_key_exists("touch", $data)) $this->touch = $data["touch"];
        if (array_key_exists("gamepad", $data)) $this->gamepad = $data["gamepad"];
        if (array_key_exists("keyboardmouse", $data)) $this->keyboardmouse = $data["keyboardmouse"];
    }

    /**
     * Fetches stats for the current user.
     * @param  string $account_id   Account id
     * @return object               The stats data
     */
    private function fetch($account_id) 
    {
        // if the accoutn ID is not set yet
        if(!$account_id) {
            return null;
        }

        try {
            // endpoint initilization 
            $endpointStats = FortniteClient::FORTNITE_STATS_API . $account_id;

            // initilize data & display name 
            $data = FortniteClient::sendFortniteGetRequest($endpointStats, $this->access_token);
            $stats = (array) $data->stats;

            // initilized platforms and compiled arrays
            $compiled = [];
            $platforms = [];

            // loop over the stat object and comiple them
            foreach($stats as $key => $stat) {
                $parsed = $this->parseStatItem($key, $stat);
            
                if(!empty($parsed)) {
                    if(isset($compiled[$parsed['platform']][$parsed['mode']][$parsed['name']])) {
                        if($parsed['name'] !== "lastmodified") {
                            $compiled[$parsed['platform']][$parsed['mode']][$parsed['name']] += $parsed['value'];
                        } else {
                            // if it is time, find the last time, not aggragate 
                            if($compiled[$parsed['platform']][$parsed['mode']][$parsed['name']] < $parsed['value']) {
                                $compiled[$parsed['platform']][$parsed['mode']][$parsed['name']] = $parsed['value'];
                            }                                                           
                        }
                    } else {
                        $compiled = array_merge_recursive($compiled, $parsed['result']);
                    }
                } 
            }

            /**
             * Loop over comipled data and put it iont 
             */
            foreach($compiled as $key => $platform) {
                $platforms[$key] = new Platform($platform);
            }

            return $platforms;
        } catch(\Exception $e) {
            \Log::error($e);
            throw new UserNotFoundException("Unable to find the stats for this user. Check if the account is connected properly.");
        } 
    }

    /**
     * Lookup by the User account ID 
     *
     * @param string $account_id 
     */
    public function lookup($account_id) 
    {
        return new self(
            $this->access_token, 
            $account_id
        );
    }

    /**
     * Parses a stat string into a mapped array.
     * @param  string $stat The stat string
     * @return array        The mapped stat array
     */
    private function parseStatItem($key, $value): array 
    {
        //
        // Example stat name:
        // br_placetop5_ps4_m0_p10
        // {type}_{name}_{platform}_playlist_{mode (mutliple modes)}
        // 

        if(!strpos($key, '_playlist_')) {
            return [];
        }

        // split the sting into two 
        $splitted = explode("_playlist_", $key);

        // split the first part 
        $pieces = explode("_", $splitted[0]);

        // isolate the required stat parameters 
        $name = $pieces[1]; // specific type of data
        $platform = $pieces[2]; // either gamepad or touch
        $gamemode = $splitted[1]; // fetch the wole 
        
        if(!in_array($gamemode, self::SOLO_MODES) && !in_array($gamemode, self::DUO_MODES) && !in_array($gamemode, self::SQUAD_MODES)) {
            return [];
        }

        $mode = "";

        switch($gamemode) {
            case in_array($gamemode, self::DUO_MODES):
                $mode = "duo";
                break;
            case in_array($gamemode, self::SQUAD_MODES):
                $mode = "squad";
                break;
            default:
                $mode = "solo";
        }

        $result = [];
        $result[$platform][$mode][$name] = $value;

        return [
            'result' => $result,
            'platform' => $platform,
            'name' => $name,
            'mode' => $mode,
            'value' => $value
        ];
    }

    /** 
     * NOTE (18/02/2020): 
     *
     * The Lookup API Endpoint got deprecated in 02/20 - so that one can't be used any more. 
     * The Fetch stats was expanded in order to cover the correspoding exceptions * 
     */

    /**
     * Lookup a user by their Epic display name.
     * @param  string $username Display name to search
     * @return object           New instance of Fortnite\Stats
     */
    /* public function lookup($username) {
        try {
            $data = FortniteClient::sendFortniteGetRequest(FortniteClient::FORTNITE_PERSONA_API . 'public/account/lookup?q=' . urlencode($username),
                                                        $this->access_token);
            return new self($this->access_token, $data->id);
        } catch (GuzzleException $e) {
            if ($e->getResponse()->getStatusCode() == 404) throw new UserNotFoundException('User ' . $username . ' was not found.');
            throw $e; //If we didn't get the user not found status code, just re-throw the error.
        }
    }

    //TODO (Tustin): Make this not redundant
    public static function lookupWithToken($username, $access_token) {
        try {
            $data = FortniteClient::sendFortniteGetRequest(FortniteClient::FORTNITE_PERSONA_API . 'public/account/lookup?q=' . urlencode($username),
                                                        $access_token);
            return new self($access_token, $data->id);
        } catch (GuzzleException $e) {
            if ($e->getResponse()->getStatusCode() == 404) throw new UserNotFoundException('User ' . $username . ' was not found.');
            throw $e; //If we didn't get the user not found status code, just re-throw the error.
        }
    } */
}