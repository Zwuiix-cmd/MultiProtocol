<?php

namespace MultiVersion\network\proto\v419\packets;

use MultiVersion\network\proto\utils\ReflectionUtils;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\EmotePacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use ReflectionException;

class v419ContainerClosePacket extends ContainerClosePacket {
    public static function fromLatest(ContainerClosePacket $pk) : self{
        $result = new self;
        $result->windowId = $pk->windowId;
        $result->server = $pk->server;
        return $result;
    }

    protected function decodePayload(PacketSerializer $in) : void{
        $this->windowId = $in->getByte();
        $this->server = $in->getBool();
    }

    protected function encodePayload(PacketSerializer $out) : void{
        $out->putByte($this->windowId);
        $out->putBool($this->server);
    }
}