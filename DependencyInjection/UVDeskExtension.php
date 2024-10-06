<?php

namespace Webkul\UVDesk\MailboxBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class UVDeskExtension extends Extension
{
    /**
     * @return string
    */
    public function getAlias(): string
    {
        return 'uvdesk_mailbox';
    }

    /**
     * @return ConfigurationInterface
    */
    public function getConfiguration(array $configs, ContainerBuilder $container): ?ConfigurationInterface
    {
        return new Configuration();
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        // Load bundle configurations
        $configuration = $this->getConfiguration($configs, $container);

        foreach ($this->processConfiguration($configuration, $configs) as $param => $value) {
            switch ($param) {
                case 'emails':
                    foreach ($value as $field => $fieldValue) {
                        $container->setParameter("uvdesk.emails.$field", $fieldValue);
                    }

                    break;
                case 'default_mailbox':
                    $container->setParameter("uvdesk.default_mailbox", $value);

                    break;
                case 'mailboxes':
                    $container->setParameter("uvdesk.mailboxes", array_keys($value));

                    foreach ($value as $mailboxId => $mailboxDetails) {
                        $container->setParameter("uvdesk.mailboxes.$mailboxId", $mailboxDetails);
                    }

                    break;
                default:
                    break;
            }
        }
    }
}
