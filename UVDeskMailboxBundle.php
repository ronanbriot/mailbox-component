<?php

namespace Webkul\UVDesk\MailboxBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Webkul\UVDesk\MailboxBundle\DependencyInjection\UVDeskExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class UVDeskMailboxBundle extends Bundle
{
    /**
     * @return ExtensionInterface
    */
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new UVDeskExtension();
    }

    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        // $container->addCompilerPass(new UVDeskAutomationCompilers\WorkflowPass());
        // $container->addCompilerPass(new UVDeskAutomationCompilers\PreparedResponsePass());
    }
}
