<?php

/*
 * Kubernetes Client.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 *
 * @link        http://teknoo.software/kubernetes-client Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @author      Marc Lough <http://maclof.com>
 */

declare(strict_types=1);

namespace Teknoo\Kubernetes;

use BadMethodCallException;
use Exception;
use Http\Client\Common\HttpMethodsClient;
use Http\Client\Common\HttpMethodsClientInterface;
use Http\Client\Exception\HttpException;
use Http\Client\Exception\TransferException as HttpTransferException;
use Http\Discovery\Psr17FactoryDiscovery;
use InvalidArgumentException;
use JsonException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;
use Symfony\Component\Yaml\Exception\ParseException as YamlParseException;
use Symfony\Component\Yaml\Yaml;
use Teknoo\Kubernetes\Enums\FileFormat;
use Teknoo\Kubernetes\Enums\PatchType;
use Teknoo\Kubernetes\Enums\RequestMethod;
use Teknoo\Kubernetes\Exceptions\ApiServerException;
use Teknoo\Kubernetes\Exceptions\BadRequestException;
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
use Throwable;

use function base64_decode;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function http_build_query;
use function in_array;
use function is_a;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function substr;
use function sys_get_temp_dir;
use function tempnam;
use function trim;

use const JSON_FORCE_OBJECT;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 *
 * @link        http://teknoo.software/kubernetes-client Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @author      Marc Lough <http://maclof.com>
 *
 * @method NodeRepository nodes()
 * @method QuotaRepository quotas()
 * @method PodRepository pods()
 * @method ReplicaSetRepository replicaSets()
 * @method ReplicationControllerRepository replicationControllers()
 * @method ServiceRepository services()
 * @method SecretRepository secrets()
 * @method EventRepository events()
 * @method ConfigMapRepository configMaps()
 * @method EndpointRepository endpoints()
 * @method PersistentVolumeClaimRepository persistentVolumeClaims()
 * @method PersistentVolumeRepository persistentVolume()
 * @method JobRepository jobs()
 * @method CronJobRepository cronJobs()
 * @method DaemonSetRepository daemonSets()
 * @method DeploymentRepository deployments()
 * @method IngressRepository ingresses()
 * @method NamespaceRepository namespaces()
 * @method NetworkPolicyRepository networkPolicies()
 * @method HorizontalPodAutoscalerRepository horizontalPodAutoscalers()
 * @method CertificateRepository certificates()
 * @method IssuerRepository issuers()
 * @method ServiceAccountRepository serviceAccounts()
 * @method RoleRepository roles()
 * @method RoleBindingRepository roleBindings()
 * @method ClusterRoleRepository clusterRoles()
 * @method ClusterRoleBindingRepository clusterRoleBindings()
 * @method SubnamespaceAnchorRepository subnamespacesAnchors()
 */
class Client
{
    private const API_VERSION = 'v1';

    private ?string $master = null;

    private ?string $token = null;

    private ?bool $verify = true;

    private ?string $caCertificate = null;

    private ?string $clientCertificate = null;

    private ?string $clientKey = null;

    private string $namespace = 'default';

    private ?HttpMethodsClientInterface $httpMethodsClient = null;

    private RepositoryRegistry $classRegistry;

    /**
     * @var array<string, Repository>
     */
    private array $classInstances = [];

    /**
     * @var array<string, string>
     */
    private array $patchHeaders = ['Content-Type' => 'application/strategic-merge-patch+json'];

    /**
     * @param array<string, string|bool> $options
     */
    public function __construct(
        array $options = [],
        RepositoryRegistry $repositoryRegistry = null,
        private ?ClientInterface $httpClient = null,
        private ?RequestFactoryInterface $httpRequestFactory = null,
        private ?StreamFactoryInterface $httpStreamFactory = null,
    ) {
        $this->setOptions($options);
        $this->classRegistry = $repositoryRegistry ?? new RepositoryRegistry();
    }

    private function getHttpMethodsClients(): HttpMethodsClientInterface
    {
        if (null !== $this->httpMethodsClient) {
            return $this->httpMethodsClient;
        }

        if (null !== $this->caCertificate && !file_exists($this->caCertificate)) {
            $this->caCertificate = self::getTempFilePath('ca-cert', $this->caCertificate);
        }

        if (null !== $this->clientCertificate && !file_exists($this->clientCertificate)) {
            $this->clientCertificate = self::getTempFilePath('client-cert', $this->clientCertificate);
        }

        if (null !== $this->clientKey && !file_exists($this->clientKey)) {
            $this->clientKey = self::getTempFilePath('client-cert', $this->clientKey);
        }

        return $this->httpMethodsClient = new HttpMethodsClient(
            $this->httpClient ?? HttpClientDiscovery::find(
                verify: true === $this->verify,
                caCertificate: $this->caCertificate,
                clientCertificate: $this->clientCertificate,
                clientKey: $this->clientKey,
            ),
            $this->httpRequestFactory ?? Psr17FactoryDiscovery::findRequestFactory(),
            $this->httpStreamFactory ?? Psr17FactoryDiscovery::findStreamFactory(),
        );
    }

    /**
     * @param array<string, string|bool> $options
     */
    public function setOptions(array $options, bool $reset = false): self
    {
        if ($reset) {
            $this->master = null;
            $this->token = null;
            $this->caCertificate = null;
            $this->clientCertificate = null;
            $this->clientKey = null;
            $this->namespace = 'default';
            $this->verify = true;
            $this->httpMethodsClient = null;
        }

        if (isset($options['master'])) {
            $this->master = (string) $options['master'];
        }

        if (isset($options['token'])) {
            $this->token = (string) $options['token'];
        }

        if (isset($options['ca_cert'])) {
            $this->caCertificate = (string) $options['ca_cert'];
        }

        if (isset($options['client_cert'])) {
            $this->clientCertificate = (string) $options['client_cert'];
        }

        if (isset($options['client_key'])) {
            $this->clientKey = (string) $options['client_key'];
        }

        if (isset($options['namespace'])) {
            $this->namespace = (string) $options['namespace'];
        }

        if (isset($options['verify'])) {
            $this->verify = !empty($options['verify']);
        }

        if (empty($this->master)) {
            throw new RuntimeException("Error, master option is mandatory for this client");
        }

        return $this;
    }

    /**
     * @param string|array<string, array<string, string>|string> $content
     * @throws JsonException
     * @throws Exception
     */
    public static function loadFromKubeConfig(
        string|array $content,
        FileFormat $format = FileFormat::Yaml,
        RepositoryRegistry $repositoryRegistry = null,
        ClientInterface $httpClient = null,
        RequestFactoryInterface $httpRequestFactory = null,
        StreamFactoryInterface $httpStreamFactory = null,
    ): self {
        try {
            $content = match (true) {
                FileFormat::Array === $format && !is_array($content) => throw new InvalidArgumentException(
                    'KubeConfig is not an array.'
                ),
                FileFormat::Array === $format && is_array($content) => $content,
                FileFormat::Json === $format && !is_string($content) => throw new InvalidArgumentException(
                    'JSON attributes must be provided as a JSON encoded string.'
                ),
                FileFormat::Json === $format && is_string($content) => json_decode(
                    json: $content,
                    associative: true,
                    flags: JSON_THROW_ON_ERROR,
                ),
                FileFormat::Yaml === $format && !is_string($content) => throw new InvalidArgumentException(
                    'YAML attributes must be provided as a YAML encoded string.'
                ),
                FileFormat::Yaml === $format && is_string($content) => Yaml::parse($content),
            };
        } catch (JsonException $jsonException) {
            throw new InvalidArgumentException(
                message: 'Failed to parse JSON encoded KubeConfig: ' . $jsonException->getMessage(),
                previous: $jsonException,
            );
        } catch (YamlParseException $yamlParseException) {
            throw new InvalidArgumentException(
                message: 'Failed to parse YAML encoded KubeConfig: ' . $yamlParseException->getMessage(),
                previous: $yamlParseException,
            );
        } catch (Throwable $error) {
            throw $error;
        }

        $contexts = [];
        if (isset($content['contexts']) && is_array($content['contexts'])) {
            foreach ($content['contexts'] as $context) {
                $contexts[$context['name']] = $context['context'];
            }
        }

        if ($contexts === []) {
            throw new InvalidArgumentException('KubeConfig parse error - No contexts are defined.');
        }

        $clusters = [];
        if (isset($content['clusters']) && is_array($content['clusters'])) {
            foreach ($content['clusters'] as $cluster) {
                $clusters[$cluster['name']] = $cluster['cluster'];
            }
        }

        if ($clusters === []) {
            throw new InvalidArgumentException('KubeConfig parse error - No clusters are defined.');
        }

        $users = [];
        if (isset($content['users']) && is_array($content['users'])) {
            foreach ($content['users'] as $user) {
                $users[$user['name']] = $user['user'];
            }
        }

        if ($users === []) {
            throw new InvalidArgumentException('KubeConfig parse error - No users are defined.');
        }

        if (!isset($content['current-context'])) {
            throw new InvalidArgumentException('KubeConfig parse error - Missing current context attribute.');
        }

        if (!isset($contexts[$content['current-context']])) {
            throw new InvalidArgumentException(
                'KubeConfig parse error - The current context "' . $content['current-context'] . '" is undefined.'
            );
        }

        $context = $contexts[$content['current-context']];

        if (!isset($context['cluster'])) {
            throw new InvalidArgumentException(
                'KubeConfig parse error - The current context is missing the cluster attribute.'
            );
        }

        if (!isset($clusters[$context['cluster']])) {
            throw new InvalidArgumentException(
                'KubeConfig parse error - The cluster "' . $context['cluster'] . '" is undefined.'
            );
        }

        $cluster = $clusters[$context['cluster']];

        if (!isset($context['user'])) {
            throw new InvalidArgumentException(
                'KubeConfig parse error - The current context is missing the user attribute.'
            );
        }

        if (!isset($users[$context['user']])) {
            throw new InvalidArgumentException(
                'KubeConfig parse error - The user "' . $context['user'] . '" is undefined.'
            );
        }

        $user = $users[$context['user']];

        $options = [];

        if (!isset($cluster['server'])) {
            throw new InvalidArgumentException(
                'KubeConfig parse error - The cluster "' . $context['cluster'] . '" is missing the server attribute.'
            );
        }

        $options['master'] = $cluster['server'];

        if (isset($cluster['certificate-authority-data'])) {
            $options['ca_cert'] = self::getTempFilePath(
                'ca-cert.pem',
                base64_decode(
                    (string) $cluster['certificate-authority-data'],
                    true,
                )
            );
        }

        if (
            !isset($cluster['certificate-authority-data'])
            && str_contains((string) $options['master'], 'https://')
        ) {
            $options['verify'] = false;
        }

        if (isset($user['client-certificate-data'])) {
            $options['client_cert'] = self::getTempFilePath(
                'client-cert.pem',
                base64_decode(
                    (string) $user['client-certificate-data'],
                    true,
                )
            );
        }

        if (isset($user['client-key-data'])) {
            $options['client_key'] = self::getTempFilePath(
                'client-key.pem',
                base64_decode(
                    (string) $user['client-key-data'],
                    true,
                )
            );
        }

        return new self(
            options: $options,
            repositoryRegistry: $repositoryRegistry,
            httpClient: $httpClient,
            httpRequestFactory: $httpRequestFactory,
            httpStreamFactory: $httpStreamFactory,
        );
    }

    /**
     * @throws JsonException
     */
    public static function loadFromKubeConfigFile(
        string $filePath,
        RepositoryRegistry $repositoryRegistry = null,
        ClientInterface $httpClient = null,
        RequestFactoryInterface $httpRequestFactory = null,
        StreamFactoryInterface $httpStreamFactory = null,
    ): self {
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException('KubeConfig file does not exist at path: ' . $filePath);
        }

        return self::loadFromKubeConfig(
            content: (string) file_get_contents($filePath),
            format: FileFormat::Yaml,
            repositoryRegistry: $repositoryRegistry,
            httpClient: $httpClient,
            httpRequestFactory: $httpRequestFactory,
            httpStreamFactory: $httpStreamFactory,
        );
    }

    /**
     * @throws Exception
     */
    private static function getTempFilePath(string $fileName, string $fileContent): string
    {
        $fileName = 'kubernetes-client-' . $fileName;

        $tempFilePath = (string) sys_get_temp_dir() . DIRECTORY_SEPARATOR  . $fileName;

        if (false === file_put_contents($tempFilePath, $fileContent)) {
            // @codeCoverageIgnoreStart
            throw new Exception('Failed to write content to temp file: ' . $tempFilePath);
            // @codeCoverageIgnoreEnd
        }

        return $tempFilePath;
    }

    public function setNamespace(string $namespace): self
    {
        $this->namespace = $namespace;

        return $this;
    }

    public function setPatchType(PatchType $patchType = PatchType::Strategic): self
    {
        $this->patchHeaders = match ($patchType) {
            PatchType::Merge => ['Content-Type' => 'application/merge-patch+json'],
            PatchType::Json => ['Content-Type' => 'application/json-patch+json'],
            PatchType::Strategic => ['Content-Type' => 'application/strategic-merge-patch+json'],
        };

        return $this;
    }

    /**
     * @param array<string, string|null> $query
     */
    private function makeUri(
        string $uri,
        array $query = [],
        bool $namespace = true,
        string $apiVersion = null
    ): string {
        if (!empty($apiVersion)) {
            $baseUri = 'apis/' . $apiVersion;
        } else {
            $baseUri = 'api/' . self::API_VERSION;
        }

        if (!empty($namespace)) {
            $baseUri .= '/namespaces/' . $this->namespace;
        }

        if ('/healthz' === $uri || '/version' === $uri) {
            $requestUri = $this->master . $uri;
        } else {
            $requestUri = $this->master . '/' . $baseUri . $uri;
        }

        if (is_array($query) && [] !== $query) {
            $requestUri .= '?' . http_build_query($query);
        }

        return $requestUri;
    }

    /**
     * @param array<string, string|null> $query
     * @throws \Http\Client\Exception
     * @throws ApiServerException
     */
    private function makeRequest(
        RequestMethod $method,
        string $uri,
        array $query = [],
        mixed $body = null,
        bool $namespace = true,
        string $apiVersion = null
    ): ResponseInterface {
        try {
            $requestUri = $this->makeUri(
                uri: $uri,
                query: $query,
                namespace: $namespace,
                apiVersion: $apiVersion,
            );

            $headers = [];

            if (RequestMethod::Patch === $method) {
                $headers = $this->patchHeaders;
            }

            if (RequestMethod::Post === $method || RequestMethod::Put === $method) {
                $headers['Content-Type'] = 'application/json';
            }

            if (!empty($this->token)) {
                $token = $this->token;
                if (file_exists($token)) {
                    $token = (string) file_get_contents($token);
                }

                $headers['Authorization'] = 'Bearer ' . trim($token);
            }

            if (is_array($body)) {
                $body = json_encode($body, JSON_FORCE_OBJECT);
            }

            $response = $this->getHttpMethodsClients()->send(
                method: $method->value,
                uri: $requestUri,
                headers: $headers,
                body: $body
            );

            // Error Handling
            if (500 <= $response->getStatusCode()) {
                $msg = substr((string) $response->getBody(), 0, 1200); // Limit maximum chars
                throw new ApiServerException("Server responded with 500 Error: " . $msg, 500);
            }

            if (in_array($response->getStatusCode(), [401, 403], true)) {
                $msg = substr((string) $response->getBody(), 0, 1200); // Limit maximum chars
                throw new ApiServerException("Authentication Exception: " . $msg, $response->getStatusCode());
            }

            if (400 <= $response->getStatusCode()) {
                $msg = substr((string) $response->getBody(), 0, 1200); // Limit maximum chars
                throw new ApiServerException($msg, $response->getStatusCode());
            }

            return $response;
        } catch (HttpTransferException $httpTransferException) {
            if (!$httpTransferException instanceof HttpException) {
                throw new BadRequestException($httpTransferException->getMessage(), 500, $httpTransferException);
            }

            $response = $httpTransferException->getResponse();
            $responseBody = (string) $response->getBody();

            throw new BadRequestException($responseBody, $response->getStatusCode(), $httpTransferException);
        }
    }

    /**
     * @param array<string, string|null> $query
     * @return array<string, string|null>
     * @throws \Http\Client\Exception
     * @throws BadRequestException
     * @throws ApiServerException
     * @throws JsonException
     */
    public function sendRequest(
        RequestMethod $method,
        string $uri,
        array $query = [],
        mixed $body = null,
        bool $namespace = true,
        string $apiVersion = null
    ): array {
        $response = $this->makeRequest(
            method: $method,
            uri: $uri,
            query: $query,
            body: $body,
            namespace: $namespace,
            apiVersion: $apiVersion,
        );

        $responseBody = (string) $response->getBody();
        return json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, string|null> $query
     * @throws \Http\Client\Exception
     * @throws BadRequestException
     * @throws ApiServerException
     */
    public function sendStringableRequest(
        RequestMethod $method,
        string $uri,
        array $query = [],
        mixed $body = null,
        bool $namespace = true,
        string $apiVersion = null
    ): string {
        $response = $this->makeRequest(
            method: $method,
            uri: $uri,
            query: $query,
            body: $body,
            namespace: $namespace,
            apiVersion: $apiVersion,
        );

        return (string) $response->getBody();
    }

    /**
     * @param array<string, string|null> $query
     */
    public function sendStreamableRequest(
        RequestMethod $method,
        string $uri,
        array $query = [],
        mixed $body = null,
        bool $namespace = true,
        string $apiVersion = null
    ): ResponseInterface {
        return $this->makeRequest(
            method: $method,
            uri: $uri,
            query: $query,
            body: $body,
            namespace: $namespace,
            apiVersion: $apiVersion,
        );
    }

    /**
     * @throws \Http\Client\Exception
     * @throws BadRequestException
     * @throws ApiServerException
     * @throws JsonException
     */
    public function health(): string
    {
        return $this->sendStringableRequest(RequestMethod::Get, '/healthz');
    }

    /**
     * @return array<string, string|null>
     * @throws \Http\Client\Exception
     * @throws BadRequestException
     * @throws ApiServerException
     * @throws JsonException
     */
    public function version(): array
    {
        return $this->sendRequest(RequestMethod::Get, '/version');
    }

    /**
     * @param class-string<Repository> $name
     * @param array<int|string, mixed> $args
     * @return Repository
     */
    public function __call(string $name, array $args): Repository
    {
        if (isset($this->classInstances[$name])) {
            return $this->classInstances[$name];
        }

        if (!isset($this->classRegistry[$name])) {
            throw new BadMethodCallException('No client methods exist with the name: ' . $name);
        }

        $class = $this->classRegistry[$name];

        if (!is_a($class, Repository::class, true)) {
            throw new BadMethodCallException("$class is not a valid Repository class");
        }

        return $this->classInstances[$name] = new $class($this);
    }
}
