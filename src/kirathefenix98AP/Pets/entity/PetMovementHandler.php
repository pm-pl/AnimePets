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

use kirathefenix98AP\Pets\entity\pathfinder\algorithm\path\PathPoint;
use kirathefenix98AP\Pets\entity\pathfinder\entity\navigator\handler\MovementHandler;
use kirathefenix98AP\Pets\entity\pathfinder\entity\navigator\Navigator;
use pocketmine\block\Slab;
use pocketmine\block\Stair;
use pocketmine\block\utils\SlabType;
use pocketmine\math\Vector3;

class PetMovementHandler extends MovementHandler {

    public function handle(Navigator $navigator, PathPoint $pathPoint): void{
        /** @var PetBase $entity */
        $entity = $navigator->getEntity();
        $location = $entity->getLocation();
        $jumpTicks = $navigator->getJumpTicks();
        $speed = $navigator->getSpeed();

        if($entity->getPosition()->getWorld()->getFolderName() !== $location->getWorld()->getFolderName()){
            return;
        }
        
        if($entity->getRider() == null && $entity->getOwner()?->getPosition()->distance($entity->getPosition()) <= 2.6){
            $xDist = $pathPoint->x - $location->x;
            $zDist = $pathPoint->z - $location->z;
            $yaw = rad2deg(atan2(-$xDist, $zDist));
            $entity->setRotation($yaw, 0);
            $entity->setWalking(false);
            return;
        }

        if($entity->isOnGround() || $jumpTicks === 0) {
            $motion = $entity->getMotion();
            if($jumpTicks <= 0) {
                $navigator->resetJumpTicks(-1);
                $xDist = $pathPoint->x - $location->x;
                $zDist = $pathPoint->z - $location->z;
                $yaw = rad2deg(atan2(-$xDist, $zDist));

                $entity->setRotation($yaw, 0);

                $x = -1 * sin(deg2rad($yaw));
                $z = cos(deg2rad($yaw));
                $directionVector = (new Vector3($x, 0, $z))->normalize()->multiply($speed);

                $motion->x = $directionVector->x;
                $motion->z = $directionVector->z;

                $lastPathPoint = $navigator->getPathResult()->getPathPoint($navigator->getIndex() + 1);
                if($lastPathPoint !== null) {
                    if($entity->isCollidedHorizontally){
                        $block = $location->getWorld()->getBlock($location);
                        $isFullBlock = false;
                        if(!$block instanceof Slab && !$block instanceof Stair){
                            $facing = $entity->getHorizontalFacing();
                            $frontBlock = $location->getWorld()->getBlock($location->add(0, 0.5, 0)->getSide($facing));
                            if(!$frontBlock->canBeFlowedInto()) {
                                if((!$frontBlock instanceof Slab || $frontBlock->getSlabType()->equals(SlabType::TOP()) || $frontBlock->getSlabType()->equals(SlabType::DOUBLE())) && (!$frontBlock instanceof Stair || $frontBlock->isUpsideDown() || $frontBlock->getFacing() !== $facing)){
                                    $motion->y = 0.42 + $this->gravity;
                                    $navigator->resetJumpTicks(5);
                                    $isFullBlock = true;
                                }
                            } else  {
                                $isFullBlock = true;
                            }
                        }
                        if(!$isFullBlock) {
                            $motion->y = 0.3 + $this->gravity;
                            $navigator->resetJumpTicks(2);
                        }

                        if($motion->y > 0) {
                            $motion->x /= 3;
                            $motion->z /= 3;
                        }
                    }
                }
                
                $entity->setWalking(true);
                $entity->setMotion($motion);
            }
            if($entity->fallDistance > 0.0) {
                $motion->x = 0;
                $motion->z = 0;
                $entity->setMotion($motion);
                $entity->setWalking(false);
            }
        }
    }
}