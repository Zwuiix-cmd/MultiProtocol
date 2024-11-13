<?php

namespace MultiVersion\network\proto\latest;

use MultiVersion\network\proto\chunk\serializer\MVChunkSerializer;
use MultiVersion\network\proto\MVPacketSerializer;
use MultiVersion\network\proto\PacketSerializerFactory;

class LatestPacketSerializerFactory implements PacketSerializerFactory{

	public function __construct(
		private MVChunkSerializer $chunkSerializer
	){

	}

	public function newEncoder() : MVPacketSerializer{
		return LatestPacketSerializer::newEncoder();
	}

	public function newDecoder(string $buffer, int $offset) : MVPacketSerializer{
		return LatestPacketSerializer::newDecoder($buffer, $offset);
	}

	public function getChunkSerializer() : MVChunkSerializer{
		return $this->chunkSerializer;
	}
}