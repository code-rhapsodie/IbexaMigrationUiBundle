<?php

declare(strict_types=1);

namespace CodeRhapsodie\IbexaMigrationUiBundle;

use CodeRhapsodie\IbexaMigrationUiBundle\DependencyInjection\Compiler\IbexaMigrationClassesPass;
use CodeRhapsodie\IbexaMigrationUiBundle\Security\PolicyProvider;
use Ibexa\Bundle\Core\DependencyInjection\IbexaCoreExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class CodeRhapsodieIbexaMigrationUiBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new IbexaMigrationClassesPass());

        // Registers the "migration_ui / view" policy so it can be assigned to roles.
        if ($container->hasExtension('ibexa')) {
            $extension = $container->getExtension('ibexa');
            if ($extension instanceof IbexaCoreExtension) {
                $extension->addPolicyProvider(new PolicyProvider());
            }
        }
    }
}
