<?php

declare(strict_types=1);

namespace App\Share;

use App\Entity\Share;
use App\Entity\User;
use App\Event\EntityListener\ShareLinkCreatedListener;
use App\Share\Event\ShareCreatedEvent;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;

final class ShareManager
{
    /**
     * @var Security
     */
    private $security;
    /**
     * @var UserManagerInterface
     */
    private $userManager;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(
        Security $security,
        UserManagerInterface $userManager,
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->security = $security;
        $this->userManager = $userManager;
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function updateShare(Share $share, User $user): Share
    {
        if (!$this->security->getUser() instanceof User) {
            throw new AccessDeniedException('Access denied to the method');
        }

        $share->setOwner($this->security->getUser());
        $share->setUser($user);

        $this->entityManager->persist($share);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(ShareCreatedEvent::NAME, new ShareCreatedEvent($share));

        return $share;
    }

    public function editShare(string $shareId, Share $shareNew): Share
    {
        /** @var Share $share */
        $share = $this->entityManager->getRepository(Share::class)->find($shareId);
        if (!$this->security->getUser() instanceof User || $this->security->getUser() !== $share->getOwner()) {
            throw new AccessDeniedException('Access denied to the method');
        }

        $oldLink = $share->getLink();
        $shareLink = $shareNew->getLink();
        if ($shareLink && $shareLink !== $oldLink) {
            $method = $oldLink ? ShareLinkCreatedListener::METHOD_CREATE : ShareLinkCreatedListener::METHOD_UPDATE;
            $this->dispathLinkCreatedEvent($share, $method);
        }

        $share->setLink($shareNew->getLink());
        $share->setSharedItems(new ArrayCollection());
        foreach ($shareNew->getSharedItems() as $shareItem) {
            $share->addSharedItem($shareItem);
        }

        $this->entityManager->persist($share);
        $this->entityManager->flush();

        return $share;
    }

    public function dispathLinkCreatedEvent(Share $share, string $method)
    {
        $linkCreatedEvent = new GenericEvent($share, ['method' => $method]);
        $this->eventDispatcher->dispatch(ShareLinkCreatedListener::EVENT_NAME, $linkCreatedEvent);
    }
}
