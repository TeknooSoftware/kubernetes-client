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
use Psr\Http\Message\StreamInterface;
use Symfony\Component\Yaml\Exception\ParseException as YamlParseException;
use Symfony\Component\Yaml\Yaml;
use Teknoo\Kubernetes\Enums\FileFormat;
use Teknoo\Kubernetes\Enums\PatchType;
use Teknoo\Kubernetes\Enums\RequestMethod;
use Teknoo\Kubernetes\Exception\MissingMasterOptionException;
use Teknoo\Kubernetes\Exception\WriteErrorException;
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
use Throwable;

use function base64_decode;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function http_build_query;
use function in_array;
use function is_array;
use function is_dir;
use function is_string;
use function json_decode;
use function json_encode;
use function parse_url;
use function str_contains;
use function substr;
use function tempnam;
use function trim;

use const JSON_FORCE_OBJECT;
use const JSON_THROW_ON_ERROR;
use const PHP_EOL;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 * @author      Marc Lough <http://maclof.com>
 *
 * @method CertificateRepository certificates()
 * @method ClusterRoleBindingRepository clusterRoleBindings()
 * @method ClusterRoleRepository clusterRoles()
 * @method ConfigMapRepository configMaps()
 * @method CronJobRepository cronJobs()
 * @method DaemonSetRepository daemonSets()
 * @method DeploymentRepository deployments()
 * @method EndpointRepository endpoints()
 * @method EventRepository events()
 * @method HorizontalPodAutoscalerRepository horizontalPodAutoscalers()
 * @method IngressRepository ingresses()
 * @method IssuerRepository issuers()
 * @method JobRepository jobs()
 * @method LimitRangeRepository limitRanges()
 * @method NamespaceRepository namespaces()
 * @method NetworkPolicyRepository networkPolicies()
 * @method NodeRepository nodes()
 * @method PersistentVolumeClaimRepository persistentVolumeClaims()
 * @method PersistentVolumeRepository persistentVolume()
 * @method PodRepository pods()
 * @method ReplicaSetRepository replicaSets()
 * @method ReplicationControllerRepository replicationControllers()
 * @method ResourceQuotaRepository resourceQuotas()
 * @method RoleBindingRepository roleBindings()
 * @method RoleRepository roles()
 * @method SecretRepository secrets()
 * @method ServiceAccountRepository serviceAccounts()
 * @method ServiceRepository services()
 * @method StatefulSetRepository statefulsets()
 * @method SubnamespaceAnchorRepository subnamespacesAnchors()
 */
class Client
{
    private const string API_VERSION = 'v1';

    private ?string $master = null;

    private ?string $token = null;

    private bool $verify = true;

    private ?string $caCertificate = null;

    private ?string $clientCertificate = null;

    private ?string $clientKey = null;

    private ?int $timeout = null;

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
     * @var callable|null
     */
    private static $tmpNameFunction = null;

    private static ?string $tmpDir = null;

    /**
     * @param array<string, string|bool|int> $options
     */
    public function __construct(
        array $options = [],
        ?RepositoryRegistry $repositoryRegistry = null,
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
                timeout: $this->timeout,
            ),
            $this->httpRequestFactory ?? Psr17FactoryDiscovery::findRequestFactory(),
            $this->httpStreamFactory ?? Psr17FactoryDiscovery::findStreamFactory(),
        );
    }

    /**
     * @param array<string, string|bool|int> $options
     */
    public function setOptions(array $options, bool $reset = false): self
    {
        if ($reset) {
            $this->master = null;
            $this->token = null;
            $this->caCertificate = null;
            $this->clientCertificate = null;
            $this->clientKey = null;
            $this->timeout = null;
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

        if (isset($options['timeout'])) {
            $this->timeout = (int) $options['timeout'];
        }

        if (isset($options['namespace'])) {
            $this->namespace = (string) $options['namespace'];
        }

        if (isset($options['verify'])) {
            $this->verify = !empty($options['verify']);
        }

        if (empty($this->master)) {
            throw new MissingMasterOptionException("Error, master option is mandatory for this client");
        }

        return $this;
    }

    /**
     * @param string|array<string, mixed> $content
     * @return array<string, mixed>
     */
    protected static function parseContent(
        string|array $content,
        FileFormat $format = FileFormat::Yaml,
    ): array {
        try {
            $result = match (true) {
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

            /** @var array<string, mixed> $result */
            return $result;
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
    }

    /**
     * @param array<string, mixed> $content
     * @return array<string, array<string, string>>
     */
    protected static function extractContexts(array $content): array
    {
        $contexts = [];
        if (isset($content['contexts']) && is_array($content['contexts'])) {
            foreach ($content['contexts'] as $context) {
                if (
                    is_array($context)
                    && isset($context['name']) && is_string($context['name'])
                    && isset($context['context']) && is_array($context['context'])
                ) {
                    $contexts[$context['name']] = $context['context'];
                }
            }
        }

        if ($contexts === []) {
            throw new InvalidArgumentException('KubeConfig parse error - No contexts are defined.');
        }

        /** @var array<string, array<string, string>> $contexts */
        return $contexts;
    }

    /**
     * @param array<string, mixed> $content
     * @param array<string, string> $context
     * @return array<string, bool|string>
     */
    protected static function extractCluster(array $content, array &$context): array
    {
        $clusters = [];
        if (isset($content['clusters']) && is_array($content['clusters'])) {
            foreach ($content['clusters'] as $cluster) {
                if (
                    is_array($cluster)
                    && isset($cluster['name']) && is_string($cluster['name'])
                    && isset($cluster['cluster']) && is_array($cluster['cluster'])
                ) {
                    $clusters[$cluster['name']] = $cluster['cluster'];
                }
            }
        }

        if ($clusters === []) {
            throw new InvalidArgumentException('KubeConfig parse error - No clusters are defined.');
        }

        if (!isset($clusters[$context['cluster']])) {
            throw new InvalidArgumentException(
                'KubeConfig parse error - The cluster "' . $context['cluster'] . '" is undefined.'
            );
        }

        /** @var array<string, array<string, string>> $clusters */
        return $clusters[$context['cluster']];
    }

    /**
     * @param array<string, mixed> $content
     * @param array<string, string> $context
     * @return array<string, bool|string>
     */
    protected static function extractUser(array $content, array &$context): array
    {
        if (!isset($context['user'])) {
            throw new InvalidArgumentException(
                'KubeConfig parse error - The current context is missing the user attribute.'
            );
        }

        $users = [];
        if (isset($content['users']) && is_array($content['users'])) {
            foreach ($content['users'] as $user) {
                if (
                    is_array($user)
                    && isset($user['name']) && is_string($user['name'])
                    && isset($user['user']) && is_array($user['user'])
                ) {
                    $users[$user['name']] = $user['user'];
                }
            }
        }

        if ($users === []) {
            throw new InvalidArgumentException('KubeConfig parse error - No users are defined.');
        }

        if (!isset($users[$context['user']])) {
            throw new InvalidArgumentException(
                'KubeConfig parse error - The user "' . $context['user'] . '" is undefined.'
            );
        }

        /** @var array<string, array<string, string>> $users */
        return $users[$context['user']];
    }

    /**
     * @param string|array<string, mixed> $content
     * @throws JsonException
     * @throws Exception
     */
    public static function loadFromKubeConfig(
        string|array $content,
        FileFormat $format = FileFormat::Yaml,
        ?RepositoryRegistry $repositoryRegistry = null,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $httpRequestFactory = null,
        ?StreamFactoryInterface $httpStreamFactory = null,
    ): self {
        $content = self::parseContent($content, $format);

        $contexts = self::extractContexts($content);
        if (!isset($content['current-context'])) {
            throw new InvalidArgumentException('KubeConfig parse error - Missing current context attribute.');
        }

        $currentContext = $content['current-context'];
        if (!is_string($currentContext) || !isset($contexts[$currentContext])) {
            if (!is_string($currentContext)) {
                throw new InvalidArgumentException(
                    'KubeConfig parse error - The current context is invalid.'
                );
            }

            throw new InvalidArgumentException(
                'KubeConfig parse error - The current context "' . $currentContext . '" is undefined.'
            );
        }

        $context = $contexts[$currentContext];

        if (!isset($context['cluster'])) {
            throw new InvalidArgumentException(
                'KubeConfig parse error - The current context is missing the cluster attribute.'
            );
        }

        $cluster = self::extractCluster($content, $context);
        $user = self::extractUser($content, $context);

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
                (string) base64_decode(
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
                (string) base64_decode(
                    (string) $user['client-certificate-data'],
                    true,
                )
            );
        }

        if (isset($user['client-key-data'])) {
            $options['client_key'] = self::getTempFilePath(
                'client-key.pem',
                (string) base64_decode(
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
        ?RepositoryRegistry $repositoryRegistry = null,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $httpRequestFactory = null,
        ?StreamFactoryInterface $httpStreamFactory = null,
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

    public static function setTmpNameFunction(?callable $tmpNameFunction): void
    {
        self::$tmpNameFunction = $tmpNameFunction;
    }

    public static function setTmpDir(?string $tmpDir): void
    {
        if (null !== $tmpDir && !is_dir($tmpDir)) {
            throw new InvalidArgumentException("$tmpDir is not a valid directory");
        }

        self::$tmpDir = $tmpDir;
    }

    /**
     * @throws Exception
     */
    private static function getTempFilePath(string $fileName, string $fileContent): string
    {
        if (null === self::$tmpNameFunction) {
            self::$tmpNameFunction = tempnam(...);
        }

        if (null === self::$tmpDir) {
            self::$tmpDir = sys_get_temp_dir();
        }

        $tempFilePath = (string) (self::$tmpNameFunction)(self::$tmpDir, 'kubernetes-client-' . $fileName);

        if (false === file_put_contents($tempFilePath, $fileContent)) {
            // @codeCoverageIgnoreStart
            throw new WriteErrorException('Failed to write content to temp file: ' . $tempFilePath);
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
     * @param array<string, int|string|null> $query
     */
    private function makeUri(
        string $uri,
        array $query = [],
        bool $namespace = true,
        ?string $apiVersion = null
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

        if ([] !== $query) {
            $requestUri .= '?' . http_build_query($query);
        }

        return $requestUri;
    }

    /**
     * @param array<string, int|string|null> $query
     * @param StreamInterface|string|array<string, mixed>|null $body
     * @throws \Http\Client\Exception
     * @throws ApiServerException
     */
    private function makeRequest(
        RequestMethod $method,
        string $uri,
        array $query = [],
        StreamInterface|string|array|null $body = null,
        bool $namespace = true,
        ?string $apiVersion = null
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

                if (!empty(parse_url($token)['scheme'])) {
                    throw new InvalidArgumentException("Error, Url are not allowed in token path for `{$this->token}`");
                }

                if (file_exists($token)) {
                    $token = trim((string) file_get_contents($token));
                }

                if (str_contains($token, PHP_EOL)) {
                    throw new InvalidArgumentException("Error, the token in `{$this->token}` is multiline");
                }

                $headers['Authorization'] = 'Bearer ' . trim($token);
            }

            if (is_array($body)) {
                $body = (string) json_encode($body, JSON_FORCE_OBJECT | JSON_THROW_ON_ERROR);
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
     * @param array<string, int|string|null> $query
     * @param StreamInterface|string|array<string, mixed>|null $body
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
        StreamInterface|string|array|null $body = null,
        bool $namespace = true,
        ?string $apiVersion = null
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
        $result = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);

        /** @var array<string, string|null> $result */
        return $result;
    }

    /**
     * @param array<string, string|null> $query
     * @param StreamInterface|string|array<string, mixed>|null $body
     * @throws \Http\Client\Exception
     * @throws BadRequestException
     * @throws ApiServerException
     */
    public function sendStringableRequest(
        RequestMethod $method,
        string $uri,
        array $query = [],
        StreamInterface|string|array|null $body = null,
        bool $namespace = true,
        ?string $apiVersion = null
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
     * @param StreamInterface|string|array<string, mixed>|null $body
     */
    public function sendStreamableRequest(
        RequestMethod $method,
        string $uri,
        array $query = [],
        StreamInterface|string|array|null $body = null,
        bool $namespace = true,
        ?string $apiVersion = null
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

        return $this->classInstances[$name] = new $class($this);
    }
}
