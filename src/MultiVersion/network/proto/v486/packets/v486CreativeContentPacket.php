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

use MultiVersion\network\proto\v419\packets\v419CreativeContentPacket;
use MultiVersion\network\proto\v486\packets\types\v486CreativeContentEntry;
use pocketmine\network\mcpe\protocol\CreativeContentPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class v486CreativeContentPacket extends CreativeContentPacket {
    public const NETWORK_ID = ProtocolInfo::CREATIVE_CONTENT_PACKET;
    /** @var v486CreativeContentEntry[] */
    public array $entries;

    /**
     * @generate-create-func
     * @param CreativeContentPacket $packet
     * @return v486CreativeContentPacket
     */
    public static function fromLatest(CreativeContentPacket $packet) : self{
        $result = new self;
        $result->entries = $packet->getItems();
        return $result;
    }


    /** @return v486CreativeContentEntry[] */
    public function getEntries() : array{ return $this->entries; }

    protected function decodePayload(PacketSerializer $in) : void{
        $this->entries = [];
        for($i = 0, $len = $in->getUnsignedVarInt(); $i < $len; ++$i){
            $this->entries[] = v486CreativeContentEntry::read($in);
        }
    }

    protected function encodePayload(PacketSerializer $out) : void{
        $out->putUnsignedVarInt(count($this->entries));
        foreach($this->entries as $entry){
            $entry->write($out);
        }
    }

    public function handle(PacketHandlerInterface $handler) : bool{
        return $handler->handleCreativeContent($this);
    }
}