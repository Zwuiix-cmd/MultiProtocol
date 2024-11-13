<?php

namespace MultiVersion\network\proto\v486;

use MultiVersion\network\proto\chunk\serializer\MVChunkSerializer;
use MultiVersion\network\proto\MVPacketSerializer;
use MultiVersion\network\proto\PacketSerializerFactory;

class v486PacketSerializerFactory implements PacketSerializerFactory{

	public function __construct(
		private MVChunkSerializer $chunkSerializer
	){

	}

	public function newEncoder() : MVPacketSerializer{
		return v486PacketSerializer::newEncoder();
	}

	public function newDecoder(string $buffer, int $offset) : MVPacketSerializer{
		return v486PacketSerializer::newDecoder($buffer, $offset);
	}

	public function getChunkSerializer() : MVChunkSerializer{
		return $this->chunkSerializer;
	}
}