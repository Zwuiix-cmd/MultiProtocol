<?php

namespace MultiVersion\network\proto\v486;

use MultiVersion\network\proto\utils\ReflectionUtils;
use MultiVersion\network\proto\v486\packets\types\v486ItemStackRequestExecutor;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\inventory\transaction\TransactionCancelledException;
use pocketmine\inventory\transaction\TransactionValidationException;
use pocketmine\network\mcpe\handler\InGamePacketHandler;
use pocketmine\network\mcpe\handler\ItemStackRequestExecutor;
use pocketmine\network\mcpe\handler\ItemStackRequestProcessException;
use pocketmine\network\mcpe\InventoryManager;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\EmotePacket;
use pocketmine\network\mcpe\protocol\ItemStackRequestPacket;
use pocketmine\network\mcpe\protocol\ItemStackResponsePacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequest;
use pocketmine\network\mcpe\protocol\types\inventory\stackresponse\ItemStackResponse;
use pocketmine\network\mcpe\protocol\types\PlayerAction;
use pocketmine\network\PacketHandlingException;
use pocketmine\player\Player;
use pocketmine\utils\Utils;
use ReflectionException;

class v486InGamePacketHandler extends InGamePacketHandler{
	private InventoryManager $inventoryManager;

	public function __construct(Player $player, NetworkSession $session, InventoryManager $inventoryManager){
		$this->inventoryManager = $inventoryManager;
		parent::__construct($player, $session, $inventoryManager);
	}


	private function handleSingleItemStackRequest(ItemStackRequest $request) : ItemStackResponse{
		if(count($request->getActions()) > 60){
			throw new PacketHandlingException("Too many actions in ItemStackRequest");
		}
		$executor = new v486ItemStackRequestExecutor($this->getPlayer(), $this->getSession()->getInvManager(), $request);
		try{
			$transaction = $executor->generateInventoryTransaction();
			$result = $this->executeInventoryTransaction($transaction, $request->getRequestId());
		}catch(ItemStackRequestProcessException $e){
			$result = false;
			$this->getSession()->getLogger()->debug("ItemStackRequest #" . $request->getRequestId() . " failed: " . $e->getMessage());
			$this->getSession()->getLogger()->debug(implode("\n", Utils::printableExceptionInfo($e)));
			$this->inventoryManager->requestSyncAll();
		}

		if(!$result){
			return new ItemStackResponse(ItemStackResponse::RESULT_ERROR, $request->getRequestId());
		}
		return $executor->buildItemStackResponse();
	}

	private function executeInventoryTransaction(InventoryTransaction $transaction, int $requestId) : bool{
		$this->getPlayer()->setUsingItem(false);

		$this->inventoryManager->setCurrentItemStackRequestId($requestId);
		$this->inventoryManager->addTransactionPredictedSlotChanges($transaction);
		try{
			$transaction->execute();
		}catch(TransactionValidationException $e){
			$this->inventoryManager->requestSyncAll();
			$logger = $this->getSession()->getLogger();
			$logger->debug("Invalid inventory transaction $requestId: " . $e->getMessage());

			return false;
		}catch(TransactionCancelledException){
			$this->getSession()->getLogger()->debug("Inventory transaction $requestId cancelled by a plugin");

			return false;
		}finally{
			$this->inventoryManager->syncMismatchedPredictedSlotChanges();
			$this->inventoryManager->setCurrentItemStackRequestId(null);
		}

		return true;
	}

	public function handleItemStackRequest(ItemStackRequestPacket $packet) : bool{
        #getEmoteLengthTicks
		$responses = [];
		if(count($packet->getRequests()) > 80){
			//TODO: we can probably lower this limit, but this will do for now
			throw new PacketHandlingException("Too many requests in ItemStackRequestPacket");
		}
		foreach($packet->getRequests() as $request){
			$responses[] = $this->handleSingleItemStackRequest($request);
		}

		$this->getSession()->sendDataPacket(ItemStackResponsePacket::create($responses));

		return true;
	}

	public function handleEmote(EmotePacket $packet): bool{
		$this->getPlayer()->emote($packet->getEmoteId(), 20);
		return true;
	}

	private function getSession() : NetworkSession{
		return ReflectionUtils::getProperty(InGamePacketHandler::class, $this, "session");
	}

	private function getPlayer() : Player{
		return ReflectionUtils::getProperty(InGamePacketHandler::class, $this, "player");
	}
}
