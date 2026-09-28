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
use Teknoo\Kubernetes\Support\TemporaryFiles;
use WeakMap;

use function base64_decode;
use function chmod;
use function dirname;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function http_build_query;
use function in_array;
use function is_array;
use function is_dir;
use function is_string;
use function is_writable;
use function json_decode;
use function json_encode;
use function parse_url;
use function preg_match;
use function rawurlencode;
use function rtrim;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function stream_get_wrappers;
use function strtolower;
use function substr;
use function sys_get_temp_dir;
use function tempnam;
use function trim;

use const DIRECTORY_SEPARATOR;
use const JSON_THROW_ON_ERROR;
use const PHP_URL_SCHEME;

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

    private PatchType $patchType = PatchType::Strategic;

    /**
     * @var callable|null
     */
    private static $tmpNameFunction = null;

    private static ?string $tmpDir = null;

    /**
     * Temporary files (certificates, keys) owned by each client instance. Entries vanish with the client,
     * and the files are removed when their last owner is released.
     *
     * @var WeakMap<Client, TemporaryFiles>|null
     */
    private static ?WeakMap $temporaryFiles = null;

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

    /**
     * A clone keeps using the temporary files of the original instance: they are shared and removed only when
     * the last instance using them is released.
     */
    public function __clone()
    {
        $shared = null;
        foreach (self::getTemporaryFilesMap() as $temporaryFiles) {
            foreach ([$this->caCertificate, $this->clientCertificate, $this->clientKey] as $path) {
                if (null !== $path && $temporaryFiles->contains($path)) {
                    $shared = $temporaryFiles;

                    break 2;
                }
            }
        }

        if (null !== $shared) {
            self::getTemporaryFilesMap()[$this] = $shared;
        }
    }

    /**
     * @return WeakMap<Client, TemporaryFiles>
     */
    private static function getTemporaryFilesMap(): WeakMap
    {
        return self::$temporaryFiles ??= new WeakMap();
    }

    private function getHttpMethodsClients(): HttpMethodsClientInterface
    {
        if (null !== $this->httpMethodsClient) {
            return $this->httpMethodsClient;
        }

        $temporaryFiles = self::getTemporaryFilesMap()[$this] ?? new TemporaryFiles();

        if (null !== $this->caCertificate && !file_exists($this->caCertificate)) {
            $this->caCertificate = self::getTempFilePath('ca-cert', $this->caCertificate, $temporaryFiles);
        }

        if (null !== $this->clientCertificate && !file_exists($this->clientCertificate)) {
            $this->clientCertificate = self::getTempFilePath(
                'client-cert',
                $this->clientCertificate,
                $temporaryFiles,
            );
        }

        if (null !== $this->clientKey && !file_exists($this->clientKey)) {
            $this->clientKey = self::getTempFilePath('client-key', $this->clientKey, $temporaryFiles);
        }

        if (!$temporaryFiles->isEmpty()) {
            self::getTemporaryFilesMap()[$this] = $temporaryFiles;
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

            $temporaryFilesMap = self::getTemporaryFilesMap();
            unset($temporaryFilesMap[$this]);
        }

        if (isset($options['master'])) {
            $this->master = rtrim((string) $options['master'], '/');
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

        if (
            isset($options['ca_cert'])
            || isset($options['client_cert'])
            || isset($options['client_key'])
            || isset($options['timeout'])
            || isset($options['verify'])
        ) {
            // The HTTP client is built with these options, it must be rebuilt on the next request
            $this->httpMethodsClient = null;
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
        }

        if (!is_array($result)) {
            throw new InvalidArgumentException('KubeConfig parse error - The document must decode to an array.');
        }

        /** @var array<string, mixed> $result */
        return $result;
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
     * Decodes a base64 attribute of a kubeconfig (certificate or key), refusing invalid content instead of
     * silently producing an empty certificate.
     */
    private static function decodeBase64Attribute(mixed $value, string $attribute): string
    {
        $decoded = false;
        if (is_string($value)) {
            $decoded = base64_decode($value, true);
        }

        if (false === $decoded) {
            throw new InvalidArgumentException(
                'KubeConfig parse error - The attribute "' . $attribute . '" is not a valid base64 encoded value.'
            );
        }

        return $decoded;
    }

    /**
     * Builds a client from a kubeconfig document (YAML, JSON or array). Supported attributes of the current
     * context: the cluster server, certificate-authority(-data) and insecure-skip-tls-verify, the user token,
     * tokenFile, client-certificate(-data) and client-key(-data), and the namespace of the context. Embedded data
     * takes precedence over file paths, which are resolved against $baseDirectory when they are relative
     * (the directory of the kubeconfig file for loadFromKubeConfigFile()).
     *
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
        ?string $baseDirectory = null,
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

        // If the client can not be built, this local object is released and the files already written are removed
        $temporaryFiles = new TemporaryFiles();

        if (isset($cluster['certificate-authority-data'])) {
            $options['ca_cert'] = self::getTempFilePath(
                'ca-cert.pem',
                self::decodeBase64Attribute($cluster['certificate-authority-data'], 'certificate-authority-data'),
                $temporaryFiles,
            );
        }

        if (isset($user['client-certificate-data'])) {
            $options['client_cert'] = self::getTempFilePath(
                'client-cert.pem',
                self::decodeBase64Attribute($user['client-certificate-data'], 'client-certificate-data'),
                $temporaryFiles,
            );
        }

        if (isset($user['client-key-data'])) {
            $options['client_key'] = self::getTempFilePath(
                'client-key.pem',
                self::decodeBase64Attribute($user['client-key-data'], 'client-key-data'),
                $temporaryFiles,
            );
        }

        // File paths are used when no embedded data is given
        if (!isset($options['ca_cert']) && isset($cluster['certificate-authority'])) {
            $options['ca_cert'] = self::resolveKubeConfigPath(
                $cluster['certificate-authority'],
                'certificate-authority',
                $baseDirectory,
            );
        }

        if (!isset($options['client_cert']) && isset($user['client-certificate'])) {
            $options['client_cert'] = self::resolveKubeConfigPath(
                $user['client-certificate'],
                'client-certificate',
                $baseDirectory,
            );
        }

        if (!isset($options['client_key']) && isset($user['client-key'])) {
            $options['client_key'] = self::resolveKubeConfigPath($user['client-key'], 'client-key', $baseDirectory);
        }

        if (isset($user['token'])) {
            if (!is_string($user['token']) || '' === $user['token']) {
                throw new InvalidArgumentException(
                    'KubeConfig parse error - The attribute "token" must be a non empty string.'
                );
            }

            $options['token'] = $user['token'];
        } elseif (isset($user['tokenFile'])) {
            $options['token'] = self::resolveKubeConfigPath($user['tokenFile'], 'tokenFile', $baseDirectory);
        }

        if (isset($context['namespace']) && '' !== $context['namespace']) {
            $options['namespace'] = $context['namespace'];
        }

        if (!empty($cluster['insecure-skip-tls-verify'])) {
            $options['verify'] = false;
        }

        $client = new self(
            options: $options,
            repositoryRegistry: $repositoryRegistry,
            httpClient: $httpClient,
            httpRequestFactory: $httpRequestFactory,
            httpStreamFactory: $httpStreamFactory,
        );

        if (!$temporaryFiles->isEmpty()) {
            self::getTemporaryFilesMap()[$client] = $temporaryFiles;
        }

        return $client;
    }

    /**
     * Resolves a file path referenced by a kubeconfig (certificate, key, token file), relative paths being
     * resolved against the base directory when it is known, and checks the file exists.
     */
    private static function resolveKubeConfigPath(mixed $path, string $attribute, ?string $baseDirectory): string
    {
        if (!is_string($path) || '' === $path) {
            throw new InvalidArgumentException(
                'KubeConfig parse error - The attribute "' . $attribute . '" must be a non empty path.'
            );
        }

        if (null !== $baseDirectory && !self::isAbsolutePath($path)) {
            $path = rtrim($baseDirectory, '/\\') . DIRECTORY_SEPARATOR . $path;
        }

        if (!file_exists($path)) {
            throw new InvalidArgumentException(
                'KubeConfig parse error - The file "' . $path . '" referenced by "' . $attribute . '" does not exist.'
            );
        }

        return $path;
    }

    private static function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || 1 === preg_match('#^[a-zA-Z]:[\\\\/]#', $path);
    }

    /**
     * Builds a client from a kubeconfig file, relative paths of the document being resolved against its directory.
     *
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
            baseDirectory: dirname($filePath),
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

        if (null !== $tmpDir && !is_writable($tmpDir)) {
            throw new InvalidArgumentException("$tmpDir is not a writable directory");
        }

        self::$tmpDir = $tmpDir;
    }

    /**
     * Writes a private (0600) temporary file and registers it into $temporaryFiles, which owns it.
     *
     * @throws WriteErrorException
     */
    private static function getTempFilePath(
        string $fileName,
        string $fileContent,
        TemporaryFiles $temporaryFiles,
    ): string {
        self::$tmpNameFunction ??= tempnam(...);
        self::$tmpDir ??= sys_get_temp_dir();

        $tempFilePath = (self::$tmpNameFunction)(self::$tmpDir, 'kubernetes-client-' . $fileName);

        if (!is_string($tempFilePath) || '' === $tempFilePath) {
            throw new WriteErrorException('Failed to create a temp file in: ' . self::$tmpDir);
        }

        if (false === file_put_contents($tempFilePath, $fileContent)) {
            // @codeCoverageIgnoreStart
            throw new WriteErrorException('Failed to write content to temp file: ' . $tempFilePath);
            // @codeCoverageIgnoreEnd
        }

        $temporaryFiles->add($tempFilePath);
        chmod($tempFilePath, 0600);

        return $tempFilePath;
    }

    public function setNamespace(string $namespace): self
    {
        $this->namespace = $namespace;

        return $this;
    }

    /**
     * Defines the default patch type of this client, used when a request does not define its own patch type.
     */
    public function setPatchType(PatchType $patchType = PatchType::Strategic): self
    {
        $this->patchType = $patchType;

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
            $baseUri .= '/namespaces/' . rawurlencode($this->namespace);
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
     * The token option holds either the bearer token itself, or the path of a file holding it (service account
     * token, re-read at each request to follow its rotation). Stream wrapper urls are refused as path, and the
     * resolved token must be a single line. The token value is never included in error messages.
     */
    private function resolveToken(string $token): string
    {
        $scheme = parse_url($token, PHP_URL_SCHEME);
        if (
            str_contains($token, '://')
            || (is_string($scheme) && in_array(strtolower($scheme), stream_get_wrappers(), true))
        ) {
            throw new InvalidArgumentException('Error, stream wrapper urls are not allowed as token path');
        }

        $isFile = file_exists($token);
        if ($isFile) {
            $token = trim((string) file_get_contents($token));
        }

        if (1 === preg_match('/[\r\n]/', $token)) {
            if ($isFile) {
                throw new InvalidArgumentException("Error, the token read from the file `{$this->token}` is multiline");
            }

            throw new InvalidArgumentException('Error, the token must be a single line');
        }

        return trim($token);
    }

    /**
     * Encodes an array body as JSON: an empty body is an empty object, empty arrays which are the value of a key are
     * maps (JSON objects), lists stay lists (no JSON_FORCE_OBJECT) and string values are never altered.
     *
     * @param array<int|string, mixed> $body
     * @throws JsonException
     */
    private function encodeBody(array $body): string
    {
        if ([] === $body) {
            return '{}';
        }

        return str_replace('":[]', '":{}', json_encode($body, JSON_THROW_ON_ERROR));
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
        ?string $apiVersion = null,
        ?PatchType $patchType = null,
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
                $headers['Content-Type'] = ($patchType ?? $this->patchType)->contentType();
            }

            if (RequestMethod::Post === $method || RequestMethod::Put === $method) {
                $headers['Content-Type'] = 'application/json';
            }

            if (RequestMethod::Delete === $method && null !== $body) {
                // DeleteOptions body, without this header curl sends it as form data and the API answers 415
                $headers['Content-Type'] = 'application/json';
            }

            if (!empty($this->token)) {
                $headers['Authorization'] = 'Bearer ' . $this->resolveToken($this->token);
            }

            if (is_array($body)) {
                $body = $this->encodeBody($body);
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
                throw new ApiServerException(
                    'Server responded with ' . $response->getStatusCode() . ' Error: ' . $msg,
                    $response->getStatusCode(),
                );
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
        ?string $apiVersion = null,
        ?PatchType $patchType = null,
    ): array {
        $response = $this->makeRequest(
            method: $method,
            uri: $uri,
            query: $query,
            body: $body,
            namespace: $namespace,
            apiVersion: $apiVersion,
            patchType: $patchType,
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
        ?string $apiVersion = null,
        ?PatchType $patchType = null,
    ): string {
        $response = $this->makeRequest(
            method: $method,
            uri: $uri,
            query: $query,
            body: $body,
            namespace: $namespace,
            apiVersion: $apiVersion,
            patchType: $patchType,
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
        ?string $apiVersion = null,
        ?PatchType $patchType = null,
    ): ResponseInterface {
        return $this->makeRequest(
            method: $method,
            uri: $uri,
            query: $query,
            body: $body,
            namespace: $namespace,
            apiVersion: $apiVersion,
            patchType: $patchType,
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
     * Returns the repository registered under this name in the repository registry (pods(), deployments()...).
     * Instances are cached for the lifetime of the client.
     *
     * @param string $name key of the repository in the registry
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
