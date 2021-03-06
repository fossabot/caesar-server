<?php

declare(strict_types=1);

namespace App\Services\File;

use App\Entity\Directory;
use App\Entity\Item;
use App\Repository\ItemRepository;
use App\Repository\TeamRepository;

final class ItemMoveResolver
{
    /**
     * @var TeamRepository
     */
    private $teamRepository;
    /**
     * @var ItemRepository
     */
    private $itemRepository;

    public function __construct(TeamRepository $teamRepository, ItemRepository $itemRepository)
    {
        $this->teamRepository = $teamRepository;
        $this->itemRepository = $itemRepository;
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function preMove(Item $item): void
    {
        $team = $this->teamRepository->findOneByDirectory($item->getParentList());
        if (is_null($team)) {
            return;
        }

        $this->removeChildren($item);
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function move(Item $item, Directory $toDirectory): void
    {
        $this->preMove($item);
        $item->setPreviousList($item->getParentList());
        $item->setParentList($toDirectory);
        $team = $this->teamRepository->findOneByDirectory($toDirectory);
        $item->setTeam($team);
    }

    private function removeChildren(Item $item): void
    {
        $items = $this->itemRepository->findByParentDirectoryAndParent($item);

        foreach ($items as $item) {
            $this->itemRepository->remove($item);
        }
    }
}
