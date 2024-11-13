<?php

namespace MultiVersion\network;

use Closure;
use InvalidArgumentException;
use MultiVersion\network\proto\batch\MVBatch;
use MultiVersion\network\proto\chunk\MVChunkCache;
use MultiVersion\network\proto\MVLoginPacketHandler;
use MultiVersion\network\proto\PacketTranslator;
use MultiVersion\network\proto\utils\ReflectionUtils;
use pocketmine\event\server\DataPacketDecodeEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\lang\KnownTranslationFactory;
use pocketmine\lang\Translatable;
use pocketmine\network\mcpe\compression\CompressBatchPromise;
use pocketmine\network\mcpe\compression\Compressor;
use pocketmine\network\mcpe\compression\DecompressionException;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\encryption\DecryptionException;
use pocketmine\network\mcpe\encryption\EncryptionContext;
use pocketmine\network\mcpe\EntityEventBroadcaster;
use pocketmine\network\mcpe\handler\InGamePacketHandler;
use pocketmine\network\mcpe\handler\PacketHandler;
use pocketmine\network\mcpe\handler\SessionStartPacketHandler;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\PacketBroadcaster;
use pocketmine\network\mcpe\PacketRateLimiter;
use pocketmine\network\mcpe\PacketSender;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\CraftingDataPacket;
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\Packet;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\serializer\PacketBatch;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use pocketmine\network\mcpe\protocol\types\CompressionAlgorithm;
use pocketmine\network\NetworkSessionManager;
use pocketmine\network\PacketHandlingException;
use pocketmine\player\PlayerInfo;
use pocketmine\player\UsedChunkStatus;
use pocketmine\Server;
use pocketmine\timings\Timings;
use pocketmine\utils\BinaryDataException;
use pocketmine\utils\BinaryStream;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Utils;
use ReflectionException;

class MVNetworkSession extends NetworkSession{

	private const INCOMING_PACKET_BATCH_PER_TICK = 10;
	private const INCOMING_PACKET_BATCH_BUFFER_TICKS = 1000;

	private const INCOMING_GAME_PACKETS_PER_TICK = 10;
	private const INCOMING_GAME_PACKETS_BUFFER_TICKS = 1000;

	private PacketRateLimiter $packetBatchLimiter;
	private PacketRateLimiter $gamePacketLimiter;

	private PacketTranslator $pkTranslator;

	private bool $enableCompression = true;

	private bool $isFirstPacket = true;

	public function __construct(Server $server, NetworkSessionManager $manager, PacketPool $packetPool, PacketSender $sender, PacketBroadcaster $broadcaster, EntityEventBroadcaster $entityEventBroadcaster, Compressor $compressor, TypeConverter $typeConverter, string $ip, int $port){
		$this->packetBatchLimiter = new PacketRateLimiter("Packet Batches", self::INCOMING_PACKET_BATCH_PER_TICK, self::INCOMING_PACKET_BATCH_BUFFER_TICKS, 5_000_000);
		$this->gamePacketLimiter = new PacketRateLimiter("Game Packets", self::INCOMING_GAME_PACKETS_PER_TICK, self::INCOMING_GAME_PACKETS_BUFFER_TICKS, 5_000_000);
		parent::__construct($server, $manager, $packetPool, $sender, $broadcaster, $entityEventBroadcaster, $compressor, $typeConverter, $ip, $port);
		$this->setHandler(new MVLoginPacketHandler(
			Server::getInstance(),
			$this,
			function(PlayerInfo $info) : void{
				ReflectionUtils::setProperty(NetworkSession::class, $this, "info", $info);
				$this->getLogger()->info(Server::getInstance()->getLanguage()->translate(KnownTranslationFactory::pocketmine_network_session_playerName(TextFormat::AQUA . $info->getUsername() . TextFormat::RESET)));
				$this->getLogger()->setPrefix("NetworkSession: " . $this->getDisplayName());
				ReflectionUtils::getProperty(NetworkSession::class, $this, "manager")->markLoginReceived($this);
			},
			function(bool $authenticated, bool $authRequired, Translatable|string|null $error, ?string $clientPubKey) : void{
				ReflectionUtils::invoke(NetworkSession::class, $this, "setAuthenticationStatus", $authenticated, $authRequired, $error, $clientPubKey);
			},
			$this->onSessionStartSuccess(...)
		));
	}

	/**
	 * @throws ReflectionException
	 */
	private function onSessionStartSuccess() : void{
		$this->getLogger()->debug("Session start handshake completed, awaiting login packet");
		$this->flushSendBuffer(true);
		$this->enableCompression = true;
		$this->setHandler(new MVLoginPacketHandler(
			Server::getInstance(),
			$this,
			function(PlayerInfo $info) : void{
				ReflectionUtils::setProperty(NetworkSession::class, $this, "info", $info);
				$this->getLogger()->info(Server::getInstance()->getLanguage()->translate(KnownTranslationFactory::pocketmine_network_session_playerName(TextFormat::AQUA . $info->getUsername() . TextFormat::RESET)));
				$this->getLogger()->setPrefix("NetworkSession: " . $this->getDisplayName());
				ReflectionUtils::getProperty(NetworkSession::class, $this, "manager")->markLoginReceived($this);
			},
			function(bool $authenticated, bool $authRequired, Translatable|string|null $error, ?string $clientPubKey) : void{
				ReflectionUtils::invoke(NetworkSession::class, $this, "setAuthenticationStatus", $authenticated, $authRequired, $error, $clientPubKey);
			},
			$this->onSessionStartSuccess(...)
		));
	}

	/**
	 * @throws ReflectionException
	 */
	public function setPacketTranslator(PacketTranslator $pkTranslator) : void{
		$this->pkTranslator = $pkTranslator;
		EncryptionContext::$ENABLED = $pkTranslator::ENCRYPTION_CONTEXT;
		ReflectionUtils::setProperty(NetworkSession::class, $this, "packetPool", $this->pkTranslator->getPacketPool());
		ReflectionUtils::setProperty(NetworkSession::class, $this, "broadcaster", $this->pkTranslator->getBroadcaster());
		ReflectionUtils::setProperty(NetworkSession::class, $this, "entityEventBroadcaster", $this->pkTranslator->getEntityEventBroadcaster());
		ReflectionUtils::setProperty(NetworkSession::class, $this, "compressor", $this->pkTranslator->getCompressor());
		ReflectionUtils::setProperty(NetworkSession::class, $this, "typeConverter", $this->pkTranslator->getTypeConverter());
	}

	public function getPacketTranslator() : PacketTranslator{
		return $this->pkTranslator;
	}

	/**
	 * @throws ReflectionException
	 */
	public function handleEncoded(string $payload) : void{
		if(!ReflectionUtils::getProperty(NetworkSession::class, $this, "connected")){
			return;
		}

		Timings::$playerNetworkReceive->startTiming();
		try{
			//$this->packetBatchLimiter->decrement();

			$cipher = ReflectionUtils::getProperty(NetworkSession::class, $this, "cipher");
			if($cipher !== null){
				Timings::$playerNetworkReceiveDecrypt->startTiming();
				try{
					$payload = $cipher->decrypt($payload);
				}catch(DecryptionException $e){
					$this->getLogger()->debug("Encrypted packet: " . base64_encode($payload));
					throw PacketHandlingException::wrap($e, "Packet decryption error");
				}finally{
					Timings::$playerNetworkReceiveDecrypt->stopTiming();
				}
			}

			if(strlen($payload) < 1){
				throw new PacketHandlingException("No bytes in payload");
			}

			if($this->enableCompression){
				Timings::$playerNetworkReceiveDecompress->startTiming();
				if(!$this->isFirstPacket && !$this->pkTranslator::OLD_COMPRESSION){
					$compressionType = ord($payload[0]);
					$compressed = substr($payload, 1);
					if($compressionType === CompressionAlgorithm::NONE){
						$decompressed = $compressed;
					}elseif($compressionType === $this->getCompressor()->getNetworkId()){
						try{
							$decompressed = $this->getCompressor()->decompress($compressed);
						}catch(DecompressionException $e){
							$this->getLogger()->debug("Failed to decompress packet: " . base64_encode($compressed));
							throw PacketHandlingException::wrap($e, "Compressed packet batch decode error");
						}finally{
							Timings::$playerNetworkReceiveDecompress->stopTiming();
						}
					}else{
						throw new PacketHandlingException("Packet compressed with unexpected compression type $compressionType");
					}
				}else{
					try{
						$decompressed = $this->getCompressor()->decompress($payload);
					}catch(DecompressionException $e){
						if($this->isFirstPacket){
							$this->getLogger()->debug("Failed to decompress packet: " . base64_encode($payload));

							$this->enableCompression = false;
							$this->setHandler(new MVLoginPacketHandler(
								Server::getInstance(),
								$this,
								function(PlayerInfo $info) : void{
									ReflectionUtils::setProperty(NetworkSession::class, $this, "info", $info);
									$this->getLogger()->info(Server::getInstance()->getLanguage()->translate(KnownTranslationFactory::pocketmine_network_session_playerName(TextFormat::AQUA . $info->getUsername() . TextFormat::RESET)));
									$this->getLogger()->setPrefix("NetworkSession: " . $this->getDisplayName());
									ReflectionUtils::getProperty(NetworkSession::class, $this, "manager")->markLoginReceived($this);
								},
								function(bool $authenticated, bool $authRequired, Translatable|string|null $error, ?string $clientPubKey) : void{
									ReflectionUtils::invoke(NetworkSession::class, $this, "setAuthenticationStatus", $authenticated, $authRequired, $error, $clientPubKey);
								},
								$this->onSessionStartSuccess(...)
							));

							$decompressed = $payload;
						}else{
							$this->getLogger()->debug("Failed to decompress packet: " . base64_encode($payload));
							throw PacketHandlingException::wrap($e, "Compressed packet batch decode error");
						}
					}finally{
						Timings::$playerNetworkReceiveDecompress->stopTiming();
					}
				}
			}else{
				$decompressed = $payload;
			}

			try{
				$stream = new BinaryStream($decompressed);
				foreach(PacketBatch::decodeRaw($stream) as $buffer){
					//$this->gamePacketLimiter->decrement();
					$packet = ReflectionUtils::getProperty(NetworkSession::class, $this, "packetPool")->getPacket($buffer);
					if($packet === null){
						$this->getLogger()->debug("Unknown packet: " . base64_encode($buffer));
						throw new PacketHandlingException("Unknown packet received");
					}
					try{
						$this->handleDataPacket($packet, $buffer);
					}catch(PacketHandlingException $e){
						$this->getLogger()->debug($packet->getName() . ": " . base64_encode($buffer));
						throw PacketHandlingException::wrap($e, "Error processing " . $packet->getName());
					}
				}
			}catch(PacketDecodeException|BinaryDataException $e){
				$this->getLogger()->logException($e);
				throw PacketHandlingException::wrap($e, "Packet batch decode error");
			}finally{
				$this->isFirstPacket = false;
			}
		}finally{
			Timings::$playerNetworkReceive->stopTiming();
		}
	}

	public function handleDataPacket(Packet $packet, string $buffer) : void{
		if(!isset($this->pkTranslator)){
			parent::handleDataPacket($packet, $buffer);
			return;
		}

		if(!$packet instanceof ServerboundPacket){
			throw new PacketDecodeException("Unexpected non-serverbound packet");
		}

		$timings = Timings::getReceiveDataPacketTimings($packet);
		$timings->startTiming();

		try{
			$ev = new DataPacketDecodeEvent($this, $packet->pid(), $buffer);
			$ev->call();
			if($ev->isCancelled()){
				return;
			}

			$decodeTimings = Timings::getDecodeDataPacketTimings($packet);
			$decodeTimings->startTiming();
			try{
				$stream = $this->getPacketTranslator()->getPacketSerializerFactory()->newDecoder($buffer, 0);
				try{
					$packet->decode($stream);
				}catch(PacketDecodeException $e){
					throw PacketHandlingException::wrap($e);
				}
				if(!$stream->feof()){
					$remains = substr($stream->getBuffer(), $stream->getOffset());
					$this->getLogger()->debug("Still " . strlen($remains) . " bytes unread in " . $packet->getName() . ": " . bin2hex($remains));
				}
			}finally{
				$decodeTimings->stopTiming();
			}

			$packet = $this->getPacketTranslator()->handleIncoming(clone $packet);
			if($packet === null){
				return;
			}

			$ev = new DataPacketReceiveEvent($this, $packet);
			$ev->call();
			if(!$ev->isCancelled()){
				$handlerTimings = Timings::getHandleDataPacketTimings($packet);
				$handlerTimings->startTiming();
				try{
					if($this->getHandler() === null || !$packet->handle($this->getHandler())){
						$this->getLogger()->debug("Unhandled " . $packet->getName() . ": " . base64_encode($stream->getBuffer()));
					}
				}finally{
					$handlerTimings->stopTiming();
				}
			}
		}finally{
			$timings->stopTiming();
		}
	}

	/**
	 * @throws ReflectionException
	 */
	public function sendDataPacket(ClientboundPacket $packet, bool $immediate = false) : bool{
		if(!isset($this->pkTranslator)){
			return parent::sendDataPacket($packet, $immediate);
		}

		if(!ReflectionUtils::getProperty(NetworkSession::class, $this, "connected")){
			return false;
		}

		//Basic safety restriction. TODO: improve this
		if(!ReflectionUtils::getProperty(NetworkSession::class, $this, "loggedIn") and !$packet->canBeSentBeforeLogin()){
			throw new InvalidArgumentException("Attempted to send " . get_class($packet) . " to " . $this->getDisplayName() . " too early");
		}

		$timings = Timings::getSendDataPacketTimings($packet);
		$timings->startTiming();
		try{
			$ev = new DataPacketSendEvent([$this], [$packet]);
			$ev->call();
			if($ev->isCancelled()){
				return false;
			}

			$packets = $ev->getPackets();

			foreach($packets as $packet){
				$pk = $this->getPacketTranslator()->handleOutgoing(clone $packet);
				if($pk === null){
					continue;
				}
				if($packet instanceof CraftingDataPacket){
					$this->addToSendBuffer($this->getPacketTranslator()->getCraftingDataCache());
					continue;
				}
				$this->addToSendBuffer(self::encodePacketTimed($this->getPacketTranslator()->getPacketSerializerFactory()->newEncoder(), $pk));
			}
			if($immediate){
				$this->flushSendBuffer(true);
			}

			return true;
		}finally{
			$timings->stopTiming();
		}
	}

	/**
	 * @throws ReflectionException
	 */
	private function flushSendBuffer(bool $immediate = false) : void{
		$sendBuffer = ReflectionUtils::getProperty(NetworkSession::class, $this, "sendBuffer");
		if(count($sendBuffer) > 0){
			Timings::$playerNetworkSend->startTiming();
			try{
				$syncMode = null;
				if($immediate){
					$syncMode = true;
				}elseif(ReflectionUtils::getProperty(NetworkSession::class, $this, "forceAsyncCompression")){
					$syncMode = false;
				}

				$stream = new BinaryStream();
				PacketBatch::encodeRaw($stream, $sendBuffer);

				if($this->enableCompression && isset($this->pkTranslator)){
					$promise = MVBatch::prepareBatch($stream->getBuffer(), $this->getPacketTranslator(), $this->getCompressor(), $syncMode, Timings::$playerNetworkSendCompressSessionBuffer);
				}else{
					$promise = new CompressBatchPromise();
					$promise->resolve($stream->getBuffer());
				}

				ReflectionUtils::setProperty(NetworkSession::class, $this, "sendBuffer", []);
				ReflectionUtils::invoke(NetworkSession::class, $this, "queueCompressedNoBufferFlush", $promise, $immediate);
			}finally{
				Timings::$playerNetworkSend->stopTiming();
			}
		}
	}

	/**
	 * Instructs the networksession to start using the chunk at the given coordinates. This may occur asynchronously.
	 *
	 * @param int                      $chunkX
	 * @param int                      $chunkZ
	 * @param Closure                  $onCompletion To be called when chunk sending has completed.
	 *
	 * @phpstan-param Closure() : void $onCompletion
	 */
	public function startUsingChunk(int $chunkX, int $chunkZ, Closure $onCompletion) : void{
		Utils::validateCallableSignature(function() : void{
		}, $onCompletion);

		$world = $this->getPlayer()->getLocation()->getWorld();
		MVChunkCache::getInstance($world, $this->getCompressor(), $this->getPacketTranslator())->request($chunkX, $chunkZ)->onResolve(
		//this callback may be called synchronously or asynchronously, depending on whether the promise is resolved yet
			function(CompressBatchPromise $promise) use ($world, $onCompletion, $chunkX, $chunkZ) : void{
				if(!$this->isConnected()){
					return;
				}
				$currentWorld = $this->getPlayer()->getLocation()->getWorld();
				if($world !== $currentWorld or ($status = $this->getPlayer()->getUsedChunkStatus($chunkX, $chunkZ)) === null){
					$this->getLogger()->debug("Tried to send no-longer-active chunk $chunkX $chunkZ in world " . $world->getFolderName());
					return;
				}
				if(!$status->equals(UsedChunkStatus::REQUESTED_SENDING())){
					//TODO: make this an error
					//this could be triggered due to the shitty way that chunk resends are handled
					//right now - not because of the spammy re-requesting, but because the chunk status reverts
					//to NEEDED if they want to be resent.
					return;
				}
				$world->timings->syncChunkSend->startTiming();
				try{
					$this->queueCompressed($promise);
					$onCompletion();
				}finally{
					$world->timings->syncChunkSend->stopTiming();
				}
			}
		);
	}

	/**
	 * @throws ReflectionException
	 */
	public function tick() : void{
		if(!$this->isConnected()){
			ReflectionUtils::invoke(NetworkSession::class, $this, "dispose");
			return;
		}

		if(ReflectionUtils::getProperty(NetworkSession::class, $this, "info") === null){
			if(time() >= ReflectionUtils::getProperty(NetworkSession::class, $this, "connectTime") + 10){
				$this->disconnectWithError(KnownTranslationFactory::pocketmine_disconnect_error_loginTimeout());
			}

			return;
		}

		$player = ReflectionUtils::getProperty(NetworkSession::class, $this, "player");
		if($player !== null){
			$player->doChunkRequests();

			$dirtyAttributes = $player->getAttributeMap()->needSend();
			$this->getEntityEventBroadcaster()->syncAttributes([$this], $player, $dirtyAttributes);
			foreach($dirtyAttributes as $attribute){
				//TODO: we might need to send these to other players in the future
				//if that happens, this will need to become more complex than a flag on the attribute itself
				$attribute->markSynchronized();
			}
		}
		Timings::$playerNetworkSendInventorySync->startTiming();
		try{
			$this->getInvManager()?->flushPendingUpdates();
		}finally{
			Timings::$playerNetworkSendInventorySync->stopTiming();
		}

		$this->flushSendBuffer();
	}

	public function queueCompressed(CompressBatchPromise|string $payload, bool $immediate = false) : void{
		Timings::$playerNetworkSend->startTiming();
		try{
			$this->flushSendBuffer($immediate); //Maintain ordering if possible
			ReflectionUtils::invoke(NetworkSession::class, $this, "queueCompressedNoBufferFlush", $payload, $immediate);
		}finally{
			Timings::$playerNetworkSend->stopTiming();
		}
	}

	/**
	 * @throws ReflectionException
	 */
	public function setHandler(?PacketHandler $handler) : void{
		if(ReflectionUtils::getProperty(NetworkSession::class, $this, "connected")){ //TODO: this is fine since we can't handle anything from a disconnected session, but it might produce surprises in some cases
			if($handler instanceof InGamePacketHandler && ($handle = $this->getPacketTranslator()->handleInGame($this)) !== null){
				$handler = $handle;
			}
			ReflectionUtils::setProperty(NetworkSession::class, $this, "handler", $handler);
			if(($handler = ReflectionUtils::getProperty(NetworkSession::class, $this, "handler")) !== null){
				$handler->setUp();
			}
		}
	}


    private function sendDisconnectPacket(Translatable|string $message) : void{
        if($message instanceof Translatable){
            $translated = Server::getInstance()->getLanguage()->translate($message);
        }else{
            $translated = $message;
        }
        $this->sendDataPacket(DisconnectPacket::create(0, $translated, ""));
    }

    /**
     * Disconnects the session, destroying the associated player (if it exists).
     *
     * @param Translatable|string      $reason                  Shown in the server log - this should be a short one-line message
     * @param Translatable|string|null $disconnectScreenMessage Shown on the player's disconnection screen (null will use the reason)
     */
    public function disconnect(Translatable|string $reason, Translatable|string|null $disconnectScreenMessage = null, bool $notify = true) : void{
        $this->tryDisconnect(function() use ($reason, $disconnectScreenMessage, $notify) : void{
            if($notify){
                $this->sendDisconnectPacket($disconnectScreenMessage ?? $reason);
            }
            if($this->getPlayer() !== null){
                $this->getPlayer()->onPostDisconnect($reason, null);
            }
        }, $reason);
    }

    public function disconnectWithError(Translatable|string $reason, Translatable|string|null $disconnectScreenMessage = null) : void{
        $errorId = implode("-", str_split(bin2hex(random_bytes(6)), 4));

        $this->disconnect(
            reason: KnownTranslationFactory::pocketmine_disconnect_error($reason, $errorId)->prefix(TextFormat::RED),
            disconnectScreenMessage: KnownTranslationFactory::pocketmine_disconnect_error($disconnectScreenMessage ?? $reason, $errorId),
        );
    }

    public function onClientDisconnect(Translatable|string $reason) : void{
        $this->tryDisconnect(function() use ($reason) : void{
            $this->getPlayer()?->onPostDisconnect($reason, null);
        }, $reason);
    }

	public function onPlayerDestroyed(Translatable|string $reason, Translatable|string $disconnectScreenMessage) : void{
		$this->tryDisconnect(function() use ($disconnectScreenMessage) : void{
			ReflectionUtils::invoke(NetworkSession::class, $this, "sendDisconnectPacket", $disconnectScreenMessage);
		}, $reason);
	}

	/**
	 * @phpstan-param Closure() : void $func
	 */
	public function tryDisconnect(Closure $func, Translatable|string $reason) : void{
		if(ReflectionUtils::getProperty(NetworkSession::class, $this, "connected") && !ReflectionUtils::getProperty(NetworkSession::class, $this, "disconnectGuard")){
			ReflectionUtils::setProperty(NetworkSession::class, $this, "disconnectGuard", true);
			$func();
			ReflectionUtils::setProperty(NetworkSession::class, $this, "disconnectGuard", false);
			$this->flushSendBuffer(true);
			ReflectionUtils::getProperty(NetworkSession::class, $this, "sender")->close();
			$disposeHooks = ReflectionUtils::getProperty(NetworkSession::class, $this, "disposeHooks");
			foreach($disposeHooks as $callback){
				$callback();
			}
			$disposeHooks->clear();
			$this->setHandler(null);
			ReflectionUtils::setProperty(NetworkSession::class, $this, "connected", false);

			$this->getLogger()->info(Server::getInstance()->getLanguage()->translate(KnownTranslationFactory::pocketmine_network_session_close($reason)));
		}
	}
}