<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\Message\BufferedMessage;
use App\Form\Request\Message\MessageType;
use App\Mailer\MailRegistry;
use App\Model\DTO\Message\InstantMessage;
use App\Model\Request\Message\MessageRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class MessengerController extends AbstractController
{
    /**
     * @Route(path="/api/notification/buffered", methods={"POST"})
     * @param Request $request
     * @return null
     */
    public function bufferedNotice(Request $request)
    {
        if ('dev' !== getenv('APP_ENV')) {
            return null;
        }

        $messageRequest = new MessageRequest();
        $form = $this->createForm(MessageType::class, $messageRequest);
        $form->submit($request->request->all());
        if (!$form->isValid()) {
            return $form;
        }

        $this->dispatchMessage(new BufferedMessage($messageRequest->getTemplate(), $messageRequest->getRecipients(), $messageRequest->getContent()));

        return null;
    }

    /**
     * @Route(path="/api/notification/instant", methods={"POST"})
     * @param Request $request
     * @return null
     */
    public function instantNotice(Request $request)
    {
        if ('dev' !== getenv('APP_ENV')) {
            return null;
        }

        $messageRequest = new MessageRequest();
        $form = $this->createForm(MessageType::class, $messageRequest);
        $form->submit($request->request->all());
        if (!$form->isValid()) {
            return $form;
        }

        $this->dispatchMessage(new InstantMessage($messageRequest->getTemplate(), $messageRequest->getRecipients(), $messageRequest->getContent()));

        return null;
    }
}