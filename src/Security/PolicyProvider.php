<?php

declare(strict_types=1);

namespace CodeRhapsodie\IbexaMigrationUiBundle\Security;

use Ibexa\Bundle\Core\DependencyInjection\Security\PolicyProvider\YamlPolicyProvider;

final class PolicyProvider extends YamlPolicyProvider
{
    /**
     * @return list<string>
     */
    protected function getFiles(): array
    {
        return [__DIR__ . '/../Resources/config/policies.yaml'];
    }
}
