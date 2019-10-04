<?php

declare(strict_types=1);

namespace App\Event\EventSubscriber;

use App\Entity\User;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;

final class RemoveDirectoriesDeletedUserSubscriber implements EventSubscriber
{

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return string[]
     */
    public function getSubscribedEvents()
    {
        return [
            Events::preRemove,
        ];
    }

    public function preRemove(LifecycleEventArgs $args): void
    {
        $user = $args->getObject();

        if (!$user instanceof User)
        {
            return;
        }

        $args->getObjectManager()->remove($user->getLists());
        $args->getObjectManager()->remove($user->getTrash());
        $args->getObjectManager()->remove($user->getInbox());
    }
}