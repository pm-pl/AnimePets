<?php

/*
 *  A paid plugin for PocketMine-MP.
 *
 *   _           _ _   _    ___   ___  _____             
 *  | |         (_) | | |  / _ \ / _ \|  __ \            
 *  | |     __ _ _| |_| |_| (_) | (_) | |  | | _____   __
 *  | |    / _` | | __| '_ \__, |> _ <| |  | |/ _ \ \ / /
 *  | |___| (_| | | |_| | | |/ /| (_) | |__| |  __/\ V / 
 *  |______\__,_|_|\__|_| |_/_/  \___/|_____/ \___| \_/  
 *  
 *  Copyright (c) Laith98Dev
 *  
 *  Youtube: Laith Youtuber
 *  Discord: Laith98Dev#0695 or @u.oo
 *  Github: Laith98Dev
 *  Email: spt.laithdev@gamil.com
 *  Donate: https://paypal.me/Laith113
 *  
 *  This plugin was sold under the Laith98Dev Publication License
 *  
 *  The terms of the license must be adhered to and never violated
 *  any violation of license permissions will not be negotiated.
 *  
 *  You will receive the license file with the plugin
 *  and it will also be inside the plugin.
 *  
 *  Since you have this plugin means that you have purchased it
 *  and you are prohibited from using it
 *  as a commercial product, distributing it, selling it or changing the rights
 *  or the name of the original developer
 *  which is Laith98Dev and it only for private use.
 *  
 *  You can also find the license from here
 *  <https://github.com/Laith98Dev/Paid-Services-License/blob/main/LICENSE>.
 *  
 */

namespace kirathefenix98AP\Pets\utils;

use customiesdevs\customies\entity\CustomiesEntityFactory;
use kirathefenix98AP\Pets\entity\walking\Artemis;
use kirathefenix98AP\Pets\entity\walking\Chopper;
use kirathefenix98AP\Pets\entity\walking\Diana;
use kirathefenix98AP\Pets\entity\walking\Hamtaro;
use kirathefenix98AP\Pets\entity\walking\Hawk;
use kirathefenix98AP\Pets\entity\walking\Luna;
use kirathefenix98AP\Pets\entity\walking\Pochita;
use kirathefenix98AP\Pets\entity\walking\Sunny;

use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\PermissionManager;

class TypeHolder {

    public static array $types = [   
        "kuma:artemis"  => Artemis::class,
        "kuma:tony_chopper"  => Chopper::class,
        "kuma:diana"  => Diana::class,
        "kuma:hamtaro"  => Hamtaro::class,
        "kuma:captain_hawk"  => Hawk::class,
        "kuma:luna"  => Luna::class,
        "kuma:pochita"  => Pochita::class,
        "kuma:thousand_sunny"  => Sunny::class
        
    ];

    public static function getClass(string $type)
    {
        return self::$types[strtolower($type)] ?? null;
    }

    public static function getType($type)
    {
        foreach (self::$types as $type_ => $class)
        {
            if(strtolower($type::class) == strtolower($class))
            {
                return $type_;
            }
        }

        return null;
    }

    public static function getTypes(): array
    {
        return array_keys(self::$types);
    }


    public static function init(): void
    {
        foreach (self::$types as $name => $class){

            CustomiesEntityFactory::getInstance()->registerEntity($class, $name);

            $root = PermissionManager::getInstance()->getPermission(DefaultPermissions::ROOT_OPERATOR);
            if(($perm = TypeHolder::getTypePerm($name)) !== null){
                $root->addChild($perm, true);
            }
        }        
    }

    public static function getTypePerm(string $type): ?string
    {
        if(self::getClass($type) !== null)
        {
            return "animepets.use." . strtolower($type);
        }

        return null;
    }
}