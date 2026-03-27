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

namespace kirathefenix98AP\Pets\session;

use kirathefenix98AP\Pets\entity\PetBase;
use kirathefenix98AP\Pets\provider\DataBase;
use kirathefenix98AP\Pets\utils\TypeHolder;
use pocketmine\player\Player;
use pocketmine\Server;

class Session
{

    private PetBase|null $pet = null;

    public function __construct(
        private readonly string $playerName,
        private string|null $petType = null,
        private string|null $petName = null
    ) {}

    public function getPlayerName(): string
    {
        return $this->playerName;
    }

    public function getPlayer(): Player|null
    {
        return Server::getInstance()->getPlayerExact($this->playerName);
    }

    public function getPet(): PetBase|null
    {
        return $this->pet;
    }

    public function getPetType(): string|null
    {
        return $this->petType;
    }

    public function setPetName(string|null $name = null): void
    {
        $this->petName = $name;
    }

    public function getPetName(): string|null
    {
        return $this->petName;
    }

    public function havePet(): bool
    {
        return $this->getPet() !== null;
    }

    public function createPet(string $petType, string $petName = null): void
    {
        $player = $this->getPlayer();

        if ($player === null or !$player->isOnline()) return;

        $class = TypeHolder::getClass($petType);

        if ($class === null) return;

        /** @var PetBase $entity */
        $entity = new $class($player->getLocation(), null, $player);

        $entity->setOwner($player);
        $entity->setRotation(0.0, 0.0);
        $entity->setNameTag(($petName === null ? $entity->getName() : $petName));
        $entity->setNameTagVisible();
        $entity->setNameTagAlwaysVisible();
        $entity->spawnToAll();

        $this->petType = $petType;

        $this->petName = $petName;

        $this->pet = $entity;

        DataBase::getInstance()->insert($this->playerName, $this->petType, $this->petName);
    }

    public function respawnPet(): void
    {
        if ($this->petType === null) return;

        $player = $this->getPlayer();

        if ($player === null or !$player->isOnline()) return;

        if (!$player->hasPermission((TypeHolder::getTypePerm($this->petType) !== null ? TypeHolder::getTypePerm($this->petType) : "unknown.pet"))) return;

        $this->createPet($this->petType, $this->petName);
    }

    public function close(bool $updateData = false): void
    {
        $pet = $this->getPet();

        if ($pet instanceof PetBase) {
            $pet->fastClose();

            $this->pet = null;
        }

        if ($updateData) {
            $this->petType = null;

            DataBase::getInstance()->insert($this->playerName, null, $this->petName);
        }
    }

    public static function create(array $row): self
    {
        return new self(
            $row['playerName'],
            $row['petType'],
            $row['petName']
        );
    }
}
