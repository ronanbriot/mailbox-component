<?php

namespace Webkul\UVDesk\MailboxBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Webkul\UVDesk\MailboxBundle\Utils\MailboxConfiguration;
use Webkul\UVDesk\MailboxBundle\Services\MailboxService;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class MailboxChannelXHR extends AbstractController
{
    public function loadMailboxesXHR(Request $request, MailboxService $mailboxService)
    {
        $mailboxConfiguration = $mailboxService->parseMailboxConfigurations();

        $defaultMailbox = $mailboxConfiguration->getDefaultMailbox();

        $collection = array_map(function ($mailbox) use ($defaultMailbox) {
            return [
                'id' => $mailbox->getId(),
                'name' => $mailbox->getName(),
                'isEnabled' => $mailbox->getIsEnabled(),
                'isDefault' => !empty($defaultMailbox) && $defaultMailbox->getId() == $mailbox->getId(),
                'isEmailDeliveryDisabled' => $mailbox->getIsEmailDeliveryDisabled() ? $mailbox->getIsEmailDeliveryDisabled() : false,
            ];
        }, array_values($mailboxConfiguration->getMailboxes()));

        return new JsonResponse($collection ?? []);
    }

    public function removeMailboxConfiguration($id, Request $request, MailboxService $mailboxService, TranslatorInterface $translator)
    {
        $mailbox = null;
        $mailboxConfiguration = $mailboxService->parseMailboxConfigurations();

        foreach ($mailboxConfiguration->getMailboxes() as $configuredMailbox) {
            if ($configuredMailbox->getId() == $id) {
                $mailbox = $configuredMailbox;

                break;
            }
        }

        if (empty($mailbox)) {
            return new JsonResponse([
                'alertClass' => 'danger',
                'alertMessage' => "No mailbox configuration was found for id '$id'.",
            ], 404);
        }

        $updatedMailboxConfiguration = new MailboxConfiguration();

        foreach ($mailboxConfiguration->getMailboxes() as $configuredMailbox) {
            if ($configuredMailbox->getId() == $id) {
                continue;
            }

            $updatedMailboxConfiguration->addMailbox($configuredMailbox);
        }

        file_put_contents($mailboxService->getPathToConfigurationFile(), (string) $updatedMailboxConfiguration);

        return new JsonResponse([
            'alertClass' => 'success',
            'alertMessage' => $translator->trans('Mailbox configuration removed successfully.'),
        ]);
    }

    public function processMailXHR(Request $request, MailboxService $mailboxService)
    {
        if ("POST" != $request->getMethod()) {
            return new JsonResponse([
                'success' => false, 
                'message' => 'Request not supported.'
            ], 500);
        } else if (null == $request->get('email')) {
            return new JsonResponse([
                'success' => false, 
                'message' => 'Missing required email data in request content.'
            ], 500);
        }

        try {
            $processedThread = $mailboxService->processMail($request->get('email'));
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false, 
                'message' => $e->getMessage()
            ], 500);
        }

        $responseMessage = $processedThread['message'];

        if (!empty($processedThread['content']['from'])) {
            $responseMessage = "Received email from <info>" . $processedThread['content']['from']. "</info>. " . $responseMessage;
        }

        if (!empty($processedThread['content']['ticket']) && !empty($processedThread['content']['thread'])) {
            $responseMessage .= " <comment>[tickets/" . $processedThread['content']['ticket'] . "/#" . $processedThread['content']['thread'] . "]</comment>";
        } else if (!empty($processedThread['content']['ticket'])) {
            $responseMessage .= " <comment>[tickets/" . $processedThread['content']['ticket'] . "]</comment>";
        }

        return new JsonResponse([
            'success' => true, 
            'message' => $responseMessage, 
        ]);
    }

    public function processOutlookMailXHR(Request $request, MailboxService $mailboxService)
    {
        if ("POST" != $request->getMethod()) {
            return new JsonResponse([
                'success' => false, 
                'message' => 'Request not supported.'
            ], 500);
        } else if (null == $request->get('email')) {
            return new JsonResponse([
                'success' => false, 
                'message' => 'Missing required email data in request content.'
            ], 500);
        }

        try {
            $processedThread = $mailboxService->processOutlookMail($request->get('email'));
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false, 
                'message' => $e->getMessage(), 
                'params' => $request->get('email')
            ], 500);
        }

        $responseMessage = $processedThread['message'];

        if (!empty($processedThread['content']['from'])) {
            $responseMessage = "Received email from <info>" . $processedThread['content']['from']. "</info>. " . $responseMessage;
        }

        if (!empty($processedThread['content']['ticket']) && !empty($processedThread['content']['thread'])) {
            $responseMessage .= " <comment>[tickets/" . $processedThread['content']['ticket'] . "/#" . $processedThread['content']['ticket'] . "]</comment>";
        } else if (!empty($processedThread['content']['ticket'])) {
            $responseMessage .= " <comment>[tickets/" . $processedThread['content']['ticket'] . "]</comment>";
        }

        return new JsonResponse([
            'success' => true, 
            'message' => $responseMessage, 
        ]);
    }
}
