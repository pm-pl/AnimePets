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

namespace kirathefenix98AP\Pets\command;

use kirathefenix98AP\Pets\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginOwned;
use pocketmine\utils\TextFormat;

class PetsCommand extends Command implements PluginOwned
{

    public function __construct(
        private Main $plugin
    ) {
        parent::__construct("animepets", "AnimePets manage command by KIRATHEFENIX98");
        $this->setPermission("animepets.command");
    }

    public function getOwningPlugin(): Main
    {
        return $this->plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage("run command in-game only!");
            return;
        }

        if (!$this->getOwningPlugin()->canSetPet($sender->getWorld())) {
            $sender->sendMessage(TextFormat::RED . 'This command is not available in this world.');

            return;
        }

        $session = Main::getInstance()->getSession($sender);

        if ($session === null) {
            $sender->sendMessage(TextFormat::RED . 'Error code 1: Could not get your database, try again');

            return;
        }

        $this->getOwningPlugin()->OpenMainForm($sender, $session);
    }
}
