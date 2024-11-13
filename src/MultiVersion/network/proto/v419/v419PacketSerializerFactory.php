<?php

namespace MultiVersion\network\proto\v419;

use MultiVersion\network\proto\chunk\serializer\MVChunkSerializer;
use MultiVersion\network\proto\MVPacketSerializer;
use MultiVersion\network\proto\PacketSerializerFactory;

class v419PacketSerializerFactory implements PacketSerializerFactory{

	public function __construct(
		private MVChunkSerializer $chunkSerializer
	){

	}

	public function newEncoder() : MVPacketSerializer{
		return v419PacketSerializer::newEncoder();
	}

	public function newDecoder(string $buffer, int $offset) : MVPacketSerializer{
		return v419PacketSerializer::newDecoder($buffer, $offset);
	}

	public function getChunkSerializer() : MVChunkSerializer{
		return $this->chunkSerializer;
	}
}