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

namespace kirathefenix98AP\Pets\entity;

use kirathefenix98AP\Pets\entity\pathfinder\algorithm\AlgorithmSettings;
use kirathefenix98AP\Pets\entity\pathfinder\entity\navigator\Navigator;
use kirathefenix98AP\Pets\Main;
use pocketmine\block\BlockLegacyIds;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\TreeRoot;
use pocketmine\player\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\inventory\Inventory;
use pocketmine\scheduler\ClosureTask;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\Slab;
use pocketmine\block\Stair;
use pocketmine\block\utils\SlabType;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\object\ItemEntity;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\SetActorLinkPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityLink;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\Server;

abstract class PetBase extends Living
{

    public Navigator $navigator;

    public float $height = 1.4;

    public float $width = 0.4;

    public float $speed = 0.4;

    public float $maxJumpHeight = 1.2;

    public bool $isWalk = false;
    public bool $isFlying = false;

    public array $canPassThrough = [
        BlockTypeIds::AIR => true,
        BlockTypeIds::VINES => true
    ];

    public InvMenu $petInventoryMenu;
    public ?Player $rider = null;

    public ?Vector3 $lastRiderPos = null;
    public ?Vector3 $nextRiderPos = null;

    public int $jumpTicks = 0;
    public ?string $ownerName = null;

    public function __construct(
        Location $loc,
        ?CompoundTag $nbt = null,
        private ?Player $owner = null
    ) {
        $this->ownerName = $owner?->getName();

        parent::__construct($loc, $nbt);

        $this->navigator = new Navigator(
            $this,
            new PetMovementHandler(),
            null,
            (new AlgorithmSettings())
                ->setTimeout(0.05)
                ->setMaxTicks(0)
                ->setJumpHeight($this->maxJumpHeight)
        );

        $this->navigator->setSpeed($this->speed);
    }

    abstract public static function getNetworkTypeId(): string;

    abstract public function getName(): string;

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo($this->height, $this->width);
    }

    public function fastClose()
    {
        parent::close();
    }

    public function getSpeed(): float
    {
        return $this->speed;
    }

    public function getOwner(): ?Player
    {
        return $this->owner;
    }

    public function getOwnerName(): ?string
    {
        return $this->ownerName;
    }

    public function setOwnerName(?string $name): void
    {
        $this->ownerName = $name;
    }

    public function setOwner(?Player $player): void
    {
        $this->owner = $player;

        if ($player !== null) {
            $this->ownerName = $player->getName();
        }
    }

    public function isOwner(Player $player)
    {
        return $player === $this->getOwner();
    }

    public function getRider(): ?Player
    {
        return $this->rider;
    }

    public function setRider(Player $player, bool $val = true)
    {
        if ($val) {
            $this->rider = $player;

            $pk = SetActorLinkPacket::create(
                new EntityLink(
                    $this->getId(),
                    $player->getId(),
                    EntityLink::TYPE_RIDER,
                    true,
                    true,
                    0
                )
            );

            $player->getNetworkProperties()->setVector3(EntityMetadataProperties::RIDER_SEAT_POSITION, new Vector3(0, $this->getInitialSizeInfo()->getHeight() + 1, 0));
            $player->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::RIDING, true);

            foreach ($player->getServer()->getOnlinePlayers() as $p) {
                if (!$p->isConnected() || !$p->isOnline()) continue;
                $p->getNetworkSession()->sendDataPacket($pk);
            }
        } else {
            $this->rider = null;

            $pk = SetActorLinkPacket::create(
                new EntityLink(
                    $this->getId(),
                    $player->getId(),
                    EntityLink::TYPE_REMOVE,
                    true,
                    true,
                    0
                )
            );

            $player->getNetworkProperties()->setVector3(EntityMetadataProperties::RIDER_SEAT_POSITION, new Vector3(0, 0, 0));
            $player->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::RIDING, false);

            foreach ($player->getServer()->getOnlinePlayers() as $p) {
                if (!$p->isConnected() || !$p->isOnline()) continue;
                $p->getNetworkSession()->sendDataPacket($pk);
            }
        }
    }

    public function isRider(Player $player)
    {
        return $this->rider === $player;
    }

    public function isWalking(): bool
    {
        return $this->isWalk;
    }

    public function setWalking(bool $val = true)
    {
        $this->isWalk = $val;
    }

    public function isFlying(): bool
    {
        return $this->isFlying;
    }

    public function setFlying(bool $val = true)
    {
        $this->isFlying = $val;
    }

    public function saveInventory(ListTag $petInventoryTag): void
    {
        $nbt = CompoundTag::create()->setTag("PetInventory", $petInventoryTag);
        $file = Main::getInstance()->getDataFolder() . "pets_inventory/" . $this->getOwnerName() . "-" . $this->getName() . ".dat";
        file_put_contents($file, zlib_encode((new LittleEndianNbtSerializer())->write(new TreeRoot($nbt)), ZLIB_ENCODING_GZIP));
    }

    public function getSavedInventory(): ?CompoundTag
    {
        $file = Main::getInstance()->getDataFolder() . "pets_inventory/" . $this->getOwnerName() . "-" . $this->getName() . ".dat";

        if (is_file($file)) {
            $decompressed = @zlib_decode(file_get_contents($file));
            return (new LittleEndianNbtSerializer())->read($decompressed)->mustGetCompoundTag();
        }

        return null;
    }

    public function updateRiderPos(Vector3 $newVector, int $flag)
    {
        if ($this->closed) {
            return;
        }

        $newVector = $newVector->add(0, -1, 0);
        $rider = $this->getRider();
        $this->location->yaw = $rider?->getLocation()->yaw ?? 0.0;
        $this->setRotation($rider?->getLocation()->yaw ?? 0.0, 0);
        // $this->location->pitch = $rider?->getLocation()->pitch ?? 0.0;
        $this->scheduleUpdate();

        $newX = 0;
        $newY = $this->motion->y;
        $newZ = 0;

        $location = $this->getLocation();
        $target = $rider->getDirectionVector()->add(0, -1, 0);

        $xDist = $target->x - $location->x;
        $zDist = $target->z - $location->z;

        $x = -1 * sin(deg2rad($location->yaw));
        $z = cos(deg2rad($location->yaw));

        switch ($flag) {
            case 0:
                // Normal
                break;
            case 1:
                $x = -$x;
                $z = -$z;
                break;
            case 2:
                // LEFT SIDE
                // $x = -$x;
                // $z = $z;
                break;
            case 3:
                // RIGHT SIDE
                // $x = $x;
                // $z = -$z;
                break;
        }

        $jumpTicks = $this->jumpTicks;

        if ($this->isOnGround() || $jumpTicks === 0) {
            if ($jumpTicks <= 0) {
                $this->jumpTicks = -1;

                $directionVector = (new Vector3($x, 0, $z))->normalize()->multiply($this->speed + 0.3);

                $newX = $directionVector->x;
                $newZ = $directionVector->z;

                $last = $this->lastRiderPos;
                // if($last !== null){
                if ($this->isCollidedHorizontally) {
                    $block = $location->getWorld()->getBlock($location);
                    $isFullBlock = false;
                    if (!$block instanceof Slab && !$block instanceof Stair) {
                        $facing = $this->getHorizontalFacing();
                        $frontBlock = $location->getWorld()->getBlock($location->add(0, 0.5, 0)->getSide($facing));
                        if (!$frontBlock->canBeFlowedInto()) {
                            if ((!$frontBlock instanceof Slab || $frontBlock->getSlabType()->equals(SlabType::TOP()) || $frontBlock->getSlabType()->equals(SlabType::DOUBLE())) && (!$frontBlock instanceof Stair || $frontBlock->isUpsideDown() || $frontBlock->getFacing() !== $facing)) {
                                $newY = 0.42 + $this->gravity;
                                // $navigator->resetJumpTicks(5);
                                $this->jumpTicks = 5;
                                $isFullBlock = true;
                            }
                        } else {
                            $isFullBlock = true;
                        }
                    }
                    if (!$isFullBlock) {
                        $newY = 0.3 + $this->gravity;
                        $this->jumpTicks = 2;
                        // $navigator->resetJumpTicks(2);
                    }

                    if ($newY > 0) {
                        $newX /= 3;
                        $newZ /= 3;
                    }
                }
                // }

                $pos = new Vector3($newX, $newY, $newZ);

                $this->lastRiderPos = $this->nextRiderPos;
                $this->nextRiderPos = new Vector3($newX, $newY, $newZ);

                $this->setWalking(true);

                $this->setMotion($pos);
                // $this->move($newX, $newY, $newZ);
                $this->updateMovement();
            }

            if ($this->fallDistance > 0.0) {
                $newX = $newZ = 0;

                $this->setWalking(false);

                $this->move($newX, $newY, $newZ);
                $this->updateMovement();
            }
        }
    }

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);

        $this->setHasGravity();
        $this->setCanClimb();
        $this->setRotation(0.0, 0.0);
        $this->setNameTagVisible();
        $this->setNameTagAlwaysVisible();

        if ($nbt->getTag("OwnerName") !== null) {
            $this->ownerName = $nbt->getString("OwnerName");
        }

        $this->petInventoryMenu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);

        $petInventoryTag = $this->getSavedInventory();
        if ($petInventoryTag !== null) {
            $inv = $petInventoryTag->getListTag("PetInventory");
            if ($inv !== null) {
                /** @var CompoundTag $item */
                foreach ($inv as $item) {
                    $this->petInventoryMenu->getInventory()->setItem($item->getByte("Slot"), Item::nbtDeserialize($item));
                }
            }
        }
    }

    public function saveNBT(): CompoundTag
    {
        $nbt = parent::saveNBT();

        if (($name = $this->getOwnerName()) !== null) {
            $nbt->setString("OwnerName", $name);
        }

        if ($this->petInventoryMenu !== null) {
            $items = [];

            $slotCount = $this->petInventoryMenu->getInventory()->getSize();
            for ($slot = 0; $slot < $slotCount; ++$slot) {
                $item = $this->petInventoryMenu->getInventory()->getItem($slot);
                if (!$item->isNull()) {
                    $items[] = $item->nbtSerialize($slot);
                }
            }

            $this->saveInventory(new ListTag($items, NBT::TAG_Compound));
        }

        return $nbt;
    }

    public function onUpdate(int $currentTick): bool
    {
        if ($this->closed) {
            return false;
        }

        if (($owner = $this->getOwner()) == null) {
            // $this->flagForDespawn();

            if (($name = $this->getOwnerName()) !== null) {
                $player = Server::getInstance()->getPlayerByPrefix($name);
                if ($player !== null) {
                    $this->setOwner($player);
                    //Main::getInstance()->resoreSessioin($player, $this);
                }
            }

            if (($owner = $this->getOwner()) == null) {
                if (!$this->isInvisible()) {
                    $this->setInvisible();
                }

                return false;
            } else {
                if ($this->isInvisible()) {
                    $this->setInvisible(false);
                }
            }
        }

        if ($this->jumpTicks > 0) $this->jumpTicks--;

        $hasUpdate = parent::onUpdate($currentTick);

        if ($owner->getPosition()->distance($this->getPosition()) >= 10 || $this->getWorld()->getFolderName() !== $owner->getWorld()->getFolderName()) {
            $playerOwner = Server::getInstance()->getPlayerByPrefix($this->getOwnerName());
            if ($this->getOwnerName() !== null) {
                if (($rider = $this->getRider()) !== null) {
                    if (strtolower($rider->getName()) === strtolower($this->getOwnerName())) {

                        $this->setRider($playerOwner, false);
                    }
                }
            }
            $this->teleport($playerOwner->getPosition());
        }

        foreach ($this->getWorld()?->getNearbyEntities($this->boundingBox, $this) as $entity) {
            if ($entity instanceof ItemEntity) {
                $distanceSquared = $entity->getEyePos()->distance($this->location);
                if ($distanceSquared > 2) {
                    continue;
                }

                $menu = $this->petInventoryMenu;
                /** @var Inventory|null */
                $inv = $menu?->getInventory();
                if ($inv !== null) {
                    $item = $entity->getItem();
                    if ($inv->canAddItem($item)) {
                        $inv->addItem($item);

                        $this->petInventoryMenu = $menu;

                        if (!$entity->isClosed()) {
                            $entity->flagForDespawn();
                        }
                    }
                }
            }
        }

        $targetVector3 = $this->navigator->getTargetVector3();
        $dis = $this->getRider() !== null ? true : ($targetVector3?->distanceSquared($owner->getPosition()) ?? 0) > 2;
        if ($this->navigator->getTargetVector3() === null || $dis) {
            $pos = $owner->getPosition();

            if ($this->isFlying()) {
                $pos = $pos->add(0, 1, 0);
            }

            $this->navigator->setTargetVector3($pos);
        }

        try {
            if ($this->getRider() == null) {
                $this->navigator->onUpdate();
            }
        } catch (\Throwable $e) {
            $this->flagForDespawn();
            Main::getInstance()->getLogger()->logException($e);
        }

        if (!$this->isWalking()) {
            $xDist = $owner->getPosition()->x - $this->getPosition()->x;
            $zDist = $owner->getPosition()->z - $this->getPosition()->z;
            $yaw = rad2deg(atan2(-$xDist, $zDist));
            $this->setRotation($yaw, 0);
        }
        // elseif($this->isWalking() && ($rider = $this->getRider()) !== null){
        //     $this->setRotation($rider->getLocation()->getYaw(), 0);
        // }

        return $hasUpdate;
    }

    public function attack(EntityDamageEvent $source): void
    {
        $entity = $source->getEntity();

        if ($entity instanceof $this) {
            if ($source instanceof EntityDamageByEntityEvent) {
                $damager = $source->getDamager();
                if ($damager instanceof Player) {
                    if ($this->isOwner($damager)) {
                        if ($damager->isSneaking()) {
                            Main::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($damager): void {
                                $this->petInventoryMenu->send($damager, "AnimePet Inventory");
                            }), 2);
                        } else {
                            if ($this->getRider() == null) {
                                $this->setRider($damager, true);
                            }
                        }
                    }
                }
            }
        }

        $source->cancel();
    }

    protected function broadcastMovement(bool $teleport = false): void
    {
        parent::broadcastMovement($teleport);
    }
}
