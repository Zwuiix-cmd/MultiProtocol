<?php

namespace MultiVersion\network\proto\v419\packets;

use MultiVersion\network\proto\utils\ReflectionUtils;
use pocketmine\network\mcpe\protocol\LevelChunkPacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\ChunkPosition;
use pocketmine\utils\Limits;

class v419LevelChunkPacket extends LevelChunkPacket{

	private const CLIENT_REQUEST_FULL_COLUMN_FAKE_COUNT = Limits::UINT32_MAX;
	private const CLIENT_REQUEST_TRUNCATED_COLUMN_FAKE_COUNT = Limits::UINT32_MAX - 1;
	private const MAX_BLOB_HASHES = 64;

	public static function fromLatest(LevelChunkPacket $pk) : self{
		$npk = new self();
		ReflectionUtils::setProperty(LevelChunkPacket::class, $npk, "chunkPosition", $pk->getChunkPosition());
		ReflectionUtils::setProperty(LevelChunkPacket::class, $npk, "dimensionId", $pk->getDimensionId());
		ReflectionUtils::setProperty(LevelChunkPacket::class, $npk, "subChunkCount", $pk->getSubChunkCount());
		ReflectionUtils::setProperty(LevelChunkPacket::class, $npk, "clientSubChunkRequestsEnabled", $pk->isClientSubChunkRequestEnabled());
		ReflectionUtils::setProperty(LevelChunkPacket::class, $npk, "usedBlobHashes", $pk->getUsedBlobHashes());
		ReflectionUtils::setProperty(LevelChunkPacket::class, $npk, "extraPayload", $pk->getExtraPayload());
		return $npk;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		ReflectionUtils::setProperty(LevelChunkPacket::class, $this, "chunkPosition", ChunkPosition::read($in));

		$subChunkCountButNotReally = $in->getUnsignedVarInt();
		if($subChunkCountButNotReally === self::CLIENT_REQUEST_FULL_COLUMN_FAKE_COUNT){
			ReflectionUtils::setProperty(LevelChunkPacket::class, $this, "clientSubChunkRequestsEnabled", true);
			ReflectionUtils::setProperty(LevelChunkPacket::class, $this, "subChunkCount", PHP_INT_MAX);
		}elseif($subChunkCountButNotReally === self::CLIENT_REQUEST_TRUNCATED_COLUMN_FAKE_COUNT){
			ReflectionUtils::setProperty(LevelChunkPacket::class, $this, "clientSubChunkRequestsEnabled", true);
			ReflectionUtils::setProperty(LevelChunkPacket::class, $this, "subChunkCount", $in->getLShort());
		}else{
			ReflectionUtils::setProperty(LevelChunkPacket::class, $this, "clientSubChunkRequestsEnabled", false);
			ReflectionUtils::setProperty(LevelChunkPacket::class, $this, "subChunkCount", $subChunkCountButNotReally);
		}

		$cacheEnabled = $in->getBool();
		if($cacheEnabled){
			$usedBlobHashes = [];
			$count = $in->getUnsignedVarInt();
			if($count > self::MAX_BLOB_HASHES){
				throw new PacketDecodeException("Expected at most " . self::MAX_BLOB_HASHES . " blob hashes, got " . $count);
			}
			for($i = 0; $i < $count; ++$i){
				$usedBlobHashes[] = $in->getLLong();
			}
			ReflectionUtils::setProperty(LevelChunkPacket::class, $this, "usedBlobHashes", $usedBlobHashes);
		}
		ReflectionUtils::setProperty(LevelChunkPacket::class, $this, "extraPayload", $in->getString());
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$this->getChunkPosition()->write($out);

		if($this->isClientSubChunkRequestEnabled()){
			if($this->getSubChunkCount() === PHP_INT_MAX){
				$out->putUnsignedVarInt(self::CLIENT_REQUEST_FULL_COLUMN_FAKE_COUNT);
			}else{
				$out->putUnsignedVarInt(self::CLIENT_REQUEST_TRUNCATED_COLUMN_FAKE_COUNT);
				$out->putLShort($this->getSubChunkCount());
			}
		}else{
			$out->putUnsignedVarInt($this->getSubChunkCount());
		}

		$out->putBool($this->getUsedBlobHashes() !== null);
		if($this->getUsedBlobHashes() !== null){
			$out->putUnsignedVarInt(count($this->getUsedBlobHashes()));
			foreach($this->getUsedBlobHashes() as $hash){
				$out->putLLong($hash);
			}
		}
		$out->putString($this->getExtraPayload());
	}
}