<?php
/*
 * Copyright (c) Matze997
 * All rights reserved.
 * Under GPL license
 */

declare(strict_types=1);

namespace kirathefenix98AP\Pets\entity\pathfinder\entity\navigator\handler;

use kirathefenix98AP\Pets\entity\pathfinder\algorithm\path\PathPoint;
use kirathefenix98AP\Pets\entity\pathfinder\entity\navigator\Navigator;

abstract class MovementHandler {
    protected float $gravity = 0.08;

    public function getGravity(): float{
        return $this->gravity;
    }

    public function setGravity(float $gravity): void{
        $this->gravity = $gravity;
    }

    abstract public function handle(Navigator $navigator, PathPoint $pathPoint): void;
}