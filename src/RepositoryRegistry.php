<?php

/*
 * Kubernetes Client.
 *
 * LICENSE
 *
 * This source file is subject to the 3-Clause BSD license
 * it is available in LICENSE file at the root of this package
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 *
 * @link        https://teknoo.software/libraries/kubernetes-client Project website
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 * @author      Marc Lough <http://maclof.com>
 */

declare(strict_types=1);

namespace Teknoo\Kubernetes;

use ArrayAccess;
use Countable;
use InvalidArgumentException;
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
use Teknoo\Kubernetes\Repository\LimitRangeRepository;
use Teknoo\Kubernetes\Repository\NamespaceRepository;
use Teknoo\Kubernetes\Repository\NetworkPolicyRepository;
use Teknoo\Kubernetes\Repository\NodeRepository;
use Teknoo\Kubernetes\Repository\PersistentVolumeClaimRepository;
use Teknoo\Kubernetes\Repository\PersistentVolumeRepository;
use Teknoo\Kubernetes\Repository\PodRepository;
use Teknoo\Kubernetes\Repository\ResourceQuotaRepository;
use Teknoo\Kubernetes\Repository\ReplicaSetRepository;
use Teknoo\Kubernetes\Repository\ReplicationControllerRepository;
use Teknoo\Kubernetes\Repository\Repository;
use Teknoo\Kubernetes\Repository\RoleBindingRepository;
use Teknoo\Kubernetes\Repository\RoleRepository;
use Teknoo\Kubernetes\Repository\SecretRepository;
use Teknoo\Kubernetes\Repository\ServiceAccountRepository;
use Teknoo\Kubernetes\Repository\ServiceRepository;
use Teknoo\Kubernetes\Repository\StatefulSetRepository;
use Teknoo\Kubernetes\Repository\SubnamespaceAnchorRepository;

use function is_a;
use function is_string;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
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
        'configMaps' => ConfigMapRepository::class,
        'endpoints' => EndpointRepository::class,
        'events' => EventRepository::class,
        'limitRanges' => LimitRangeRepository::class,
        'namespaces' => NamespaceRepository::class,
        'nodes' => NodeRepository::class,
        'persistentVolume' => PersistentVolumeRepository::class,
        'persistentVolumeClaims' => PersistentVolumeClaimRepository::class,
        'pods' => PodRepository::class,
        'replicaSets' => ReplicaSetRepository::class,
        'replicationControllers' => ReplicationControllerRepository::class,
        'resourceQuotas' => ResourceQuotaRepository::class,
        'secrets' => SecretRepository::class,
        'serviceAccounts' => ServiceAccountRepository::class,
        'services' => ServiceRepository::class,

        // batch/v1
        'jobs' => JobRepository::class,

        // batch/v2
        'cronJobs' => CronJobRepository::class,

        // apps/v1
        'deployments' => DeploymentRepository::class,
        'statefulsets' => StatefulSetRepository::class,

        // extensions/v1
        'daemonSets' => DaemonSetRepository::class,
        'ingresses' => IngressRepository::class,

        // autoscaling/v2
        'horizontalPodAutoscalers' => HorizontalPodAutoscalerRepository::class,

        // networking.k8s.io/v1
        'networkPolicies' => NetworkPolicyRepository::class,

        // certmanager.k8s.io/v1
        'certificates' => CertificateRepository::class,
        'issuers' => IssuerRepository::class,

        //rbac.authorization.k8s.io/v1
        'roles' => RoleRepository::class,
        'roleBindings' => RoleBindingRepository::class,
        'clusterRoles' => ClusterRoleRepository::class,
        'clusterRoleBindings' => ClusterRoleBindingRepository::class,

        //hnc.x-k8s.io/v1
        'subnamespacesAnchors' => SubnamespaceAnchorRepository::class,
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
        if (!is_string($value)) {
            throw new InvalidArgumentException('$value must be a valid Repository Class');
        }

        if (!is_a($value, Repository::class, true)) {
            throw new InvalidArgumentException("$value is not a valid Repository class");
        }

        $this->map[(string) $offset] = $value;
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
