<?php

/*
 * Kubernetes Client.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * it is available in LICENSE file at the root of this package
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 *
 * @link        http://teknoo.software/kubernetes-client Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @author      Marc Lough <http://maclof.com>
 */

declare(strict_types=1);

namespace Teknoo\Kubernetes;

use ArrayAccess;
use Countable;
use Teknoo\Kubernetes\Repository\CertificateRepository;
use Teknoo\Kubernetes\Repository\ClusterRoleBindingRepository;
use Teknoo\Kubernetes\Repository\ClusterRoleRepository;
use Teknoo\Kubernetes\Repository\ConfigMapRepository;
use Teknoo\Kubernetes\Repository\CronJobRepository;
use Teknoo\Kubernetes\Repository\DaemonSetRepository;
use Teknoo\Kubernetes\Repository\DeploymentRepository;
use Teknoo\Kubernetes\Repository\EndpointRepository;
use Teknoo\Kubernetes\Repository\EventRepository;
use Teknoo\Kubernetes\Repository\HorizontalPodAutoscalerRepository;
use Teknoo\Kubernetes\Repository\IngressRepository;
use Teknoo\Kubernetes\Repository\IssuerRepository;
use Teknoo\Kubernetes\Repository\JobRepository;
use Teknoo\Kubernetes\Repository\NamespaceRepository;
use Teknoo\Kubernetes\Repository\NetworkPolicyRepository;
use Teknoo\Kubernetes\Repository\NodeRepository;
use Teknoo\Kubernetes\Repository\PersistentVolumeClaimRepository;
use Teknoo\Kubernetes\Repository\PersistentVolumeRepository;
use Teknoo\Kubernetes\Repository\PodRepository;
use Teknoo\Kubernetes\Repository\QuotaRepository;
use Teknoo\Kubernetes\Repository\ReplicaSetRepository;
use Teknoo\Kubernetes\Repository\ReplicationControllerRepository;
use Teknoo\Kubernetes\Repository\Repository;
use Teknoo\Kubernetes\Repository\RoleBindingRepository;
use Teknoo\Kubernetes\Repository\RoleRepository;
use Teknoo\Kubernetes\Repository\SecretRepository;
use Teknoo\Kubernetes\Repository\ServiceAccountRepository;
use Teknoo\Kubernetes\Repository\ServiceRepository;
use Teknoo\Kubernetes\Repository\SubnamespaceAnchorRepository;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @author      Marc Lough <http://maclof.com>
 *
 * @implements ArrayAccess<string, class-string<Repository>>
 */
class RepositoryRegistry implements ArrayAccess, Countable
{
    /**
     * @var array<string, class-string<Repository>>
     */
    private array $map = [
        'nodes'                  => NodeRepository::class,
        'quotas'                 => QuotaRepository::class,
        'pods'                   => PodRepository::class,
        'replicaSets'            => ReplicaSetRepository::class,
        'replicationControllers' => ReplicationControllerRepository::class,
        'services'               => ServiceRepository::class,
        'secrets'                => SecretRepository::class,
        'events'                 => EventRepository::class,
        'configMaps'             => ConfigMapRepository::class,
        'endpoints'              => EndpointRepository::class,
        'persistentVolume'       => PersistentVolumeRepository::class,
        'persistentVolumeClaims' => PersistentVolumeClaimRepository::class,
        'namespaces'             => NamespaceRepository::class,
        'serviceAccounts'         => ServiceAccountRepository::class,

        // batch/v1
        'jobs'                   => JobRepository::class,

        // batch/v2
        'cronJobs'               => CronJobRepository::class,

        // apps/v1
        'deployments'            => DeploymentRepository::class,

        // extensions/v1
        'daemonSets'             => DaemonSetRepository::class,
        'ingresses'              => IngressRepository::class,

        // autoscaling/v2
        'horizontalPodAutoscalers'  => HorizontalPodAutoscalerRepository::class,

        // networking.k8s.io/v1
        'networkPolicies'        => NetworkPolicyRepository::class,

        // certmanager.k8s.io/v1
        'certificates'           => CertificateRepository::class,
        'issuers'                => IssuerRepository::class,

        //rbac.authorization.k8s.io/v1
        'roles'                  => RoleRepository::class,
        'roleBindings'              => RoleBindingRepository::class,
        'clusterRoles'              => ClusterRoleRepository::class,
        'clusterRoleBindings'      => ClusterRoleBindingRepository::class,

        //hnc.x-k8s.io/v1
        'subnamespacesAnchors'   => SubnamespaceAnchorRepository::class,
    ];

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->map[$offset]);
    }

    public function offsetGet(mixed $offset): ?string
    {
        return $this->map[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->map[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->map[$offset]);
    }

    public function count(): int
    {
        return count($this->map);
    }
}
