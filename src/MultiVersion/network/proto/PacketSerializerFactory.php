<?php

namespace MultiVersion\network\proto;

use MultiVersion\network\proto\chunk\serializer\MVChunkSerializer;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

interface PacketSerializerFactory{

	public function newEncoder() : PacketSerializer;

	public function newDecoder(string $buffer, int $offset) : PacketSerializer;

	public function getChunkSerializer() : MVChunkSerializer;
}