<?php

namespace MultiVersion\network\proto;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

abstract class MVPacketSerializer extends PacketSerializer{

	final public static function newEncoder() : self{
		return new static();
	}

	final public static function newDecoder(string $buffer, int $offset) : self{
		return new static($buffer, $offset);
	}
}