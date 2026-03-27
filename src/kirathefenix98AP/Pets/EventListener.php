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

namespace kirathefenix98AP\Pets;

use kirathefenix98AP\Pets\entity\PetBase;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\entity\EntityDespawnEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\player\Player;

class EventListener implements Listener
{

    public function __construct(
        private Main $plugin
    ) {}

    public function getPlugin(): Main
    {
        return $this->plugin;
    }

    public function onJoin(PlayerJoinEvent $event): void
    {
        Main::getInstance()->createSession($event->getPlayer());
    }

    /**
     * @param PlayerQuitEvent $event
     * 
     * @priority LOW
     */
    public function onQuit(PlayerQuitEvent $event): void
    {
        $session = $this->getPlugin()->getSession($event->getPlayer());

        if ($session === null) return;

        $session->close();
    }

    /**public function onMotion(EntityMotionEvent $event)
    {
        $entity = $event->getEntity();
        if ($entity instanceof PetBase) {
            $event->cancel();
        }
    }*/

    public function onInputPacket(DataPacketReceiveEvent $event)
    {
        $packet = $event->getPacket();
        $player = $event->getOrigin()->getPlayer();

        if ($player === null) {
            return;
        }

        if (($session = $this->getPlugin()->getSession($player)) !== null) {
            $pet = $session->getPet();

            if ($pet === null) return;

            if ($packet instanceof PlayerAuthInputPacket) {
                // $event->cancel();
                if ($packet->getInputFlags()->get(PlayerAuthInputFlags::START_JUMPING) || $packet->getInputFlags()->get(PlayerAuthInputFlags::START_SNEAKING)) {
                    if (($rider = $pet->getRider()) !== null) {
                        if (strtolower($rider->getName()) === strtolower($player->getName())) {
                            $pet->setRider($player, false);
                        }
                    }
                }

                if (
                    $packet->getInputFlags()->get(PlayerAuthInputFlags::UP) ||
                    $packet->getInputFlags()->get(PlayerAuthInputFlags::DOWN) ||
                    $packet->getInputFlags()->get(PlayerAuthInputFlags::LEFT) ||
                    $packet->getInputFlags()->get(PlayerAuthInputFlags::RIGHT)
                ) {
                    $flag = match (true) {
                        $packet->getInputFlags()->get(PlayerAuthInputFlags::UP) => 0,
                        $packet->getInputFlags()->get(PlayerAuthInputFlags::DOWN) => 1,
                        $packet->getInputFlags()->get(PlayerAuthInputFlags::LEFT) => 2,
                        $packet->getInputFlags()->get(PlayerAuthInputFlags::RIGHT) => 3
                    };

                    if (($driver = $pet->getRider()) !== null) {
                        if (strtolower($driver->getName()) === strtolower($player->getName())) {
                            // if ($packet->getMoveVecX() !== 0.0 && $packet->getMoveVecZ() !== 0.0) {
                            $pet->updateRiderPos(new Vector3($packet->getMoveVecX(), 0, $packet->getMoveVecZ()), $flag);
                            // }
                        }
                    }
                }
            }

            if ($packet instanceof InteractPacket) {
                if ($packet->action === InteractPacket::ACTION_LEAVE_VEHICLE) {
                    if (($rider = $pet->getRider()) !== null) {
                        if (strtolower($rider->getName()) === strtolower($player->getName())) {
                            $pet->setRider($player, false);
                        }
                    }
                }
            }
        }
    }

    public function onEntityDespawn(EntityDespawnEvent $event): void
    {
        $entity = $event->getEntity();
        if ($entity instanceof PetBase) {
            $entity->saveNBT();
        }
    }

    /**
     * @param EntityTeleportEvent $ev
     */
    public function onPlayerTeleport(EntityTeleportEvent $ev): void
    {
        $player = $ev->getEntity();

        if (!$player instanceof Player) return;

        $from = $ev->getFrom()->getWorld();

        $to = $ev->getTo()->getWorld();

        if ($from->getFolderName() === $to->getFolderName()) return;

        $session = $this->getPlugin()->getSession($player);

        if ($session === null) return;

        if (Main::getInstance()->canSetPet($from) and $session->havePet()) $session->close();

        if (Main::getInstance()->canSetPet($to)) $session->respawnPet();
    }
}
