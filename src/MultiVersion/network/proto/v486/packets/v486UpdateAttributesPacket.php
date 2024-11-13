<?php

/*
 * This file is part of BedrockProtocol.
 * Copyright (C) 2014-2022 PocketMine Team <https://github.com/pmmp/BedrockProtocol>
 *
 * BedrockProtocol is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);

namespace MultiVersion\network\proto\v486\packets;

use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\entity\Attribute;
use pocketmine\network\mcpe\protocol\types\entity\UpdateAttribute;
use pocketmine\network\mcpe\protocol\UpdateAttributesPacket;
use function array_values;

class v486UpdateAttributesPacket extends UpdateAttributesPacket{
    public const NETWORK_ID = ProtocolInfo::UPDATE_ATTRIBUTES_PACKET;

    public int $actorRuntimeId;
    /** @var Attribute[] */
    public array $entries = [];
    public int $tick = 0;

    /**
     * @generate-create-func
     * @param Attribute[] $entries
     */
    public static function fromLatest(UpdateAttributesPacket $packet) : self{
        $result = new self;
        $result->actorRuntimeId = $packet->actorRuntimeId;
        $result->entries = array_map(function (UpdateAttribute $attribute){
            return new Attribute($attribute->getId(), $attribute->getMin(), $attribute->getMax(), $attribute->getCurrent(), $attribute->getDefault(), $attribute->getModifiers());
        },$packet->entries);
        $result->tick = $packet->tick;
        return $result;
    }

    protected function decodePayload(PacketSerializer $in) : void{
        $this->actorRuntimeId = $in->getActorRuntimeId();
        $this->entries = $in->getAttributeList();
        $this->tick = $in->getUnsignedVarLong();
    }

    protected function encodePayload(PacketSerializer $out) : void{
        $out->putActorRuntimeId($this->actorRuntimeId);
        $out->putAttributeList(...array_values($this->entries));
        $out->putUnsignedVarLong($this->tick);
    }

    public function handle(PacketHandlerInterface $handler) : bool{
        return $handler->handleUpdateAttributes($this);
    }
}