<?php

/* * Kubernetes Client.
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
 *
 * @link        https://teknoo.software/libraries/kubernetes-client Project website
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\Tests\Kubernetes\Behat;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Hook\AfterScenario;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use GuzzleHttp\Client as GuzzleClient;
use Http\Adapter\Guzzle7\Client as Guzzle7Client;
use Http\Client\Common\HttpMethodsClient;
use Http\Client\Curl\Client as CurlClient;
use Http\Client\Socket\Client as SocketClient;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\HttpClient\HttplugClient as SymfonyHttplug;
use InvalidArgumentException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Stream;
use PHPUnit\Framework\Assert;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Teknoo\Kubernetes\Client;
use Teknoo\Kubernetes\Exception\UnsupportedHttpClientOptionsException;
use Teknoo\Kubernetes\HttpClientDiscovery;
use Teknoo\Kubernetes\HttpClient\InstantiatorInterface;
use Teknoo\Kubernetes\HttpClient\Instantiator\Curl as CurlInstantiator;
use Teknoo\Kubernetes\HttpClient\Instantiator\Guzzle7 as Guzzle7Instantiator;
use Teknoo\Kubernetes\HttpClient\Instantiator\Socket as SocketInstantiator;
use Teknoo\Kubernetes\HttpClient\Instantiator\Symfony as SymfonyInstantiator;
use Teknoo\Kubernetes\Collection\Collection;
use Teknoo\Kubernetes\Contracts\Repository\StreamingParser;
use Teknoo\Kubernetes\Enums\FileFormat;
use Teknoo\Kubernetes\Enums\RequestMethod;
use Teknoo\Kubernetes\Exceptions\ApiServerException;
use Teknoo\Kubernetes\Model\Certificate;
use Teknoo\Kubernetes\Model\ConfigMap;
use Teknoo\Kubernetes\Model\DeleteOptions;
use Teknoo\Kubernetes\Model\Issuer;
use Teknoo\Kubernetes\Model\Model;
use Teknoo\Kubernetes\Model\PersistentVolume;
use Teknoo\Kubernetes\Model\Pod;
use Teknoo\Kubernetes\Model\SubnamespaceAnchor;
use Teknoo\Kubernetes\RepositoryRegistry;
use Teknoo\Tests\Kubernetes\Behat\Fixture\ThingRepository;
use Throwable;

use function array_key_last;
use function array_shift;
use function bin2hex;
use function file_put_contents;
use function count;
use function gc_collect_cycles;
use function glob;
use function json_decode;
use function json_encode;
use function mkdir;
use function random_bytes;
use function rmdir;
use function sys_get_temp_dir;
use function unlink;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
{
    private ?ClientInterface $psrClient = null;

    private ?HttpMethodsClient $httpClient = null;

    private ?Client $kubeClient = null;

    private ?string $token = null;

    private ?string $clientCert = null;

    private ?string $clientKey = null;

    private ?string $namespace = null;

    private ?Model $model = null;

    private ?string $kubeCollections = null;

    private ?Throwable $error = null;

    private mixed $result = null;

    private ?string $tmpDir = null;

    private ?RepositoryRegistry $repositoryRegistry = null;

    private ?ClientInterface $builtHttpClient = null;

    /**
     * @var array<class-string, class-string>|null
     */
    private ?array $savedInstantiators = null;

    public function __construct()
    {
        $this->psrClient = null;
        $this->httpClient = null;
        $this->token = null;
        $this->clientCert = null;
        $this->clientKey = null;
        $this->namespace = null;
        $this->kubeClient = null;
        $this->model = null;
        $this->error = null;
        $this->kubeCollections = null;
        $this->result = null;
    }

    #[Given('a Kubernetes cluster')]
    public function aKubernetesCluster(): void
    {
        $this->psrClient = new class implements ClientInterface {
            private ?ResponseInterface $response = null;

            /**
             * @var array<int, RequestInterface>
             */
            private array $requests = [];

            /**
             * @return array<int, RequestInterface>
             */
            public function getRequests(): array
            {
                return $this->requests;
            }

            public function getLastRequest(): ?RequestInterface
            {
                if ([] === $this->requests) {
                    return null;
                }

                return $this->requests[array_key_last($this->requests)];
            }
            
            private array $responses = [];

            public function setResponse(?ResponseInterface $response): void
            {
                $this->response = $response;
            }

            public function setResponses(array $responses): void
            {
                $this->responses = $responses;
            }

            public function setFirstResponse(?ResponseInterface $firstResponse): void
            {
                $this->setResponses([$firstResponse]);
            }

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->requests[] = $request;

                if (!empty($this->responses)) {
                    return array_shift($this->responses);
                }

                Assert::assertNotNull($this->response);

                return $this->response;
            }
        };

        $this->httpClient = new HttpMethodsClient(
            $this->psrClient,
            new Psr17Factory()
        );
    }

    #[Given('a service account identified by a token :value')]
    public function aServiceAccountIdentifiedByAToken(string $value): void
    {
        $this->token = $value;
    }

    #[Given('an account identified by a certificate client')]
    public function anAccountIdentifiedByAclientCert(): void
    {
        $this->clientCert = 'fooo';
        $this->clientKey = 'baaar';
    }

    #[Given('a namespace :value')]
    public function aNamespace(string $value): void
    {
        $this->namespace = $value;
    }

    #[Given('an instance of this client')]
    public function anInstanceOfThisClient(): void
    {
        $this->kubeClient = new Client(
            options: [
                'master' => 'https://api.example.com',
                'token' => $this->token,
                'client_cert' => $this->clientCert,
                'client_key' => $this->clientKey,
                'namespace' => $this->namespace,
            ],
            repositoryRegistry: $this->repositoryRegistry,
            httpClient: $this->httpClient,
        );
    }

    #[Given('a pod model :name')]
    public function aPodModel(string $name): void
    {
        $this->model = new Pod(
            [
                'metadata' => [
                    'name' => $name
                ],
                'spec' => [
                    'foo' => 'bar'
                ]
            ]
        );

        $this->kubeCollections = 'pods';
    }

    #[Then('without error')]
    public function withoutError(): void
    {
        Assert::assertNull($this->error);
    }

    #[Given('the resource already exists in the cluster')]
    public function theResourceAlreadyExistsInTheCluster(): void
    {
        $this->psrClient->setFirstResponse(
            new Response(
                status: 200,
                body: Stream::create(
                    json_encode(
                        [
                            'items' => [$this->model->toArray()],
                        ]
                    ),
                ),
            ),
        );
    }

    #[Given('the resource does not already exist in the cluster')]
    #[Given('the cluster has no registered pod')]
    public function theResourceDoesNotAlreadyExistInTheCluster(): void
    {
        $this->kubeCollections = 'pods';
        $this->psrClient->setFirstResponse(
            new Response(
                status: 200,
                body: Stream::create(
                    json_encode(
                        [
                            'items' => [],
                        ]
                    ),
                ),
            ),
        );
    }

    #[Given('the model is valid')]
    public function theModelIsValid(): void
    {
        $this->psrClient->setResponse(
            new Response(
                status: 200,
                body: Stream::create(
                    json_encode(
                        $this->model->toArray(),
                    ),
                ),
            ),
        );
    }

    #[Given('the model is mal formed')]
    public function theModelIsMalFormed(): void
    {
        $this->psrClient->setResponse(
            new Response(
                status: 400,
                body: Stream::create(
                    json_encode(
                        [
                            'foo' => 'bar',
                        ],
                    ),
                ),
            ),
        );
    }

    #[Given('the cluster has several registered pods')]
    public function theClusterHasSeveralRegisteredPods(): void
    {
        $this->kubeCollections = 'pods';
        $this->psrClient->setFirstResponse(
            new Response(
                status: 200,
                body: Stream::create(
                    json_encode(
                        [
                            'items' => [
                                [
                                    'metadata' => [
                                        'name' => 'pod1'
                                    ],
                                    'spec' => [
                                        'foo' => 'bar'
                                    ]
                                ],
                                [
                                    'metadata' => [
                                        'name' => 'pod2'
                                    ],
                                    'spec' => [
                                        'foo' => 'bar'
                                    ]
                                ],
                                [
                                    'metadata' => [
                                        'name' => 'pod3'
                                    ],
                                    'spec' => [
                                        'foo' => 'bar'
                                    ]
                                ],
                            ],
                        ]
                    ),
                ),
            ),
        );
    }

    #[Given('the cluster has several registered pods will be fetched in limited')]
    public function theClusterHasSeveralRegisteredPodsWillBeFetchedInLimited(): void
    {
        $this->kubeCollections = 'pods';
        $this->psrClient->setResponses([
            new Response(
                status: 200,
                body: Stream::create(
                    json_encode(
                        [
                            'metadata' => [
                                'continue' => 'foo',
                            ],
                            'items' => [
                                [
                                    'metadata' => [
                                        'name' => 'pod1'
                                    ],
                                    'spec' => [
                                        'foo' => 'bar'
                                    ]
                                ],
                            ],
                        ]
                    ),
                ),
            ),
            new Response(
                status: 200,
                body: Stream::create(
                    json_encode(
                        [
                            'metadata' => [
                                'continue' => 'foo',
                            ],
                            'items' => [
                                [
                                    'metadata' => [
                                        'name' => 'pod2'
                                    ],
                                    'spec' => [
                                        'foo' => 'bar'
                                    ]
                                ],
                            ],
                        ]
                    ),
                ),
            ),
            new Response(
                status: 200,
                body: Stream::create(
                    json_encode(
                        [
                            'metadata' => [
                            ],
                            'items' => [
                                [
                                    'metadata' => [
                                        'name' => 'pod3'
                                    ],
                                    'spec' => [
                                        'foo' => 'bar'
                                    ]
                                ],
                            ],
                        ]
                    ),
                ),
            ),
        ]);
    }

    #[When('the user create the resource on the server')]
    public function theUserCreateTheResourceOnTheServer(): void
    {
        Assert::assertNotNull($this->kubeCollections);

        try {
            $this->result = $this->kubeClient
                ->{$this->kubeCollections}()
                ->create($this->model);
        } catch (Throwable $throwable) {
            $this->error = $throwable;
        }
    }


    #[When('the user apply the resource on the server')]
    public function theUserApplyTheResourceOnTheServer(): void
    {
        Assert::assertNotNull($this->kubeCollections);

        try {
            $this->result = $this->kubeClient
                ->{$this->kubeCollections}()
                ->apply($this->model);
        } catch (Throwable $throwable) {
            $this->error = $throwable;
        }
    }

    #[When('the user delete the resource on the server')]
    public function theUserDeleteTheResourceOnTheServer(): void
    {
        Assert::assertNotNull($this->kubeCollections);

        try {
            $this->result = $this->kubeClient
                ->{$this->kubeCollections}()
                ->delete($this->model);
        } catch (Throwable $throwable) {
            $this->error = $throwable;
        }
    }

    #[When('the user recursive delete the resource on the server')]
    public function theUserRecursiveDeleteTheResourceOnTheServer(): void
    {
        Assert::assertNotNull($this->kubeCollections);

        try {
            $this->result = $this->kubeClient
                ->{$this->kubeCollections}()
                ->delete(
                    $this->model,
                    new DeleteOptions(['propagationPolicy' => 'Background'])
                );
        } catch (Throwable $throwable) {
            $this->error = $throwable;
        }
    }

    #[When('the user patch the resource on the server')]
    public function theUserPatchTheResourceOnTheServer(): void
    {
        Assert::assertNotNull($this->kubeCollections);

        try {
            $this->result = $this->kubeClient
                ->{$this->kubeCollections}()
                ->patch($this->model);
        } catch (Throwable $throwable) {
            $this->error = $throwable;
        }
    }

    #[When('the user update the resource on the server')]
    public function theUserUpdateTheResourceOnTheServer(): void
    {
        Assert::assertNotNull($this->kubeCollections);

        try {
            $this->result = $this->kubeClient
                ->{$this->kubeCollections}()
                ->update($this->model);
        } catch (Throwable $throwable) {
            $this->error = $throwable;
        }
    }

    #[Then('the server must return an array as response')]
    public function theServerMustReturnAnArrayAsResponse(): void
    {
        Assert::assertIsArray($this->result);
    }

    #[Then('the server must return an error :code')]
    public function theServerMustReturnAnError(int $code): void
    {
        Assert::assertInstanceOf(
            ApiServerException::class,
            $this->error,
        );

        Assert::assertEquals(
            $code,
            $this->error->getCode()
        );
    }

    #[When('the user fetch the first resource on the server')]
    public function theUserFetchTheFirstResourceOnTheServer(): void
    {
        Assert::assertNotNull($this->kubeCollections);

        try {
            $this->result = $this->kubeClient
                ->{$this->kubeCollections}()
                ->first();
        } catch (Throwable $throwable) {
            $this->error = $throwable;
        }
    }

    #[When('the user fetch a collection on the server')]
    public function theUserFetchACollectionOnTheServer(): void
    {
        Assert::assertNotNull($this->kubeCollections);

        try {
            $this->result = $this->kubeClient
                ->{$this->kubeCollections}()
                ->find();
        } catch (Throwable $throwable) {
            $this->error = $throwable;
        }
    }

    #[When('the user fetch a collection on the server with label selector')]
    public function theUserFetchACollectionOnTheServerWithLabelSelector(): void
    {
        Assert::assertNotNull($this->kubeCollections);

        try {
            $this->result = $this->kubeClient
                ->{$this->kubeCollections}()
                ->setLabelSelector(['foo' => 'bar'])
                ->find();
        } catch (Throwable $throwable) {
            $this->error = $throwable;
        }
    }

    #[When('the user fetch a limited collection on the server')]
    public function theUserFetchALimitedCollectionOnTheServer(): void
    {
        Assert::assertNotNull($this->kubeCollections);

        try {
            $this->result = $this->kubeClient
                ->{$this->kubeCollections}()
                ->find(limit: 1);
        } catch (Throwable $throwable) {
            $this->error = $throwable;
        }
    }

    #[Then('the server must return a limited collection of pods')]
    public function theServerMustReturnALimitedCollectionOfPods(): void
    {
        Assert::assertInstanceOf(
            Collection::class,
            $this->result,
        );

        Assert::assertTrue($this->result->hasNext());

        $count = 0;
        foreach ($this->result as $model) {
            ++$count;
            Assert::assertInstanceOf(
                Pod::class,
                $model,
            );
        }

        Assert::assertEquals(1, $count);
    }

    #[When('the user fetch the next collection on the server')]
    public function theUserFetchTheNextCollectionOnTheServer(): void
    {
        Assert::assertInstanceOf(
            Collection::class,
            $this->result,
        );

        try {
            $this->result = $this->result->continue();
        } catch (Throwable $throwable) {
            $this->error = $throwable;
        }
    }

    #[When('the server must return a final collection of pods')]
    public function theServerMustReturnAFinalCollectionOfPods(): void
    {
        Assert::assertInstanceOf(
            Collection::class,
            $this->result,
        );

        Assert::assertFalse($this->result->hasNext());

        $count = 0;
        foreach ($this->result as $model) {
            ++$count;
            Assert::assertInstanceOf(
                Pod::class,
                $model,
            );
        }

        Assert::assertEquals(1, $count);
    }

    #[Then('the server must return a collection of pods')]
    public function theServerMustReturnACollectionOfPods(): void
    {
        Assert::assertInstanceOf(
            Collection::class,
            $this->result,
        );

        Assert::assertNotCount(0, $this->result);

        Assert::assertContainsOnlyInstancesOf(Pod::class, $this->result);
    }

    #[Then('the server must return a pod model')]
    public function theServerMustReturnAPodModel(): void
    {
        Assert::assertInstanceOf(
            Pod::class,
            $this->result,
        );
    }

    #[Then('the server must return an empty collection')]
    public function theServerMustReturnAnEmptyCollection(): void
    {
        Assert::assertInstanceOf(
            Collection::class,
            $this->result,
        );

        Assert::assertCount(0, $this->result);
    }

    #[Then('the server must return a null response')]
    public function theServerMustReturnANullResponse(): void
    {
        Assert::assertNull($this->result);
    }

    private function getLastRequest(): RequestInterface
    {
        Assert::assertNotNull($this->psrClient);
        $request = $this->psrClient->getLastRequest();
        Assert::assertInstanceOf(RequestInterface::class, $request, 'No request was sent to the cluster');

        return $request;
    }

    #[Then('the last request sent to the cluster must use the method :method')]
    public function theLastRequestSentToTheClusterMustUseTheMethod(string $method): void
    {
        Assert::assertSame($method, $this->getLastRequest()->getMethod());
    }

    #[Then('the last request sent to the cluster must target the uri :uri')]
    public function theLastRequestSentToTheClusterMustTargetTheUri(string $uri): void
    {
        Assert::assertSame($uri, (string) $this->getLastRequest()->getUri());
    }

    #[Then('the last request sent to the cluster must have the header :name equal to :value')]
    public function theLastRequestSentToTheClusterMustHaveTheHeaderEqualTo(string $name, string $value): void
    {
        Assert::assertSame($value, $this->getLastRequest()->getHeaderLine($name));
    }

    #[Then('the last request sent to the cluster must not have the header :name')]
    public function theLastRequestSentToTheClusterMustNotHaveTheHeader(string $name): void
    {
        Assert::assertFalse($this->getLastRequest()->hasHeader($name));
    }

    #[Then('the last request sent to the cluster must have a JSON body equal to:')]
    public function theLastRequestSentToTheClusterMustHaveAJsonBodyEqualTo(PyStringNode $body): void
    {
        Assert::assertJsonStringEqualsJsonString(
            $body->getRaw(),
            (string) $this->getLastRequest()->getBody(),
        );
    }

    #[Then('the last request sent to the cluster must have an empty body')]
    public function theLastRequestSentToTheClusterMustHaveAnEmptyBody(): void
    {
        Assert::assertSame('', (string) $this->getLastRequest()->getBody());
    }

    #[Then('the request number :index sent to the cluster must have the header :name equal to :value')]
    public function theRequestNumberSentToTheClusterMustHaveTheHeaderEqualTo(int $index, string $name, string $value): void
    {
        Assert::assertNotNull($this->psrClient);
        $requests = $this->psrClient->getRequests();
        Assert::assertArrayHasKey($index - 1, $requests, "No request number $index was sent to the cluster");
        Assert::assertSame($value, $requests[$index - 1]->getHeaderLine($name));
    }

    #[Then(':count requests must have been sent to the cluster')]
    #[Then(':count request must have been sent to the cluster')]
    public function requestsMustHaveBeenSentToTheCluster(int $count): void
    {
        Assert::assertNotNull($this->psrClient);
        Assert::assertCount($count, $this->psrClient->getRequests());
    }

    #[Given('the cluster answers every request with a collection of pods')]
    public function theClusterAnswersEveryRequestWithACollectionOfPods(): void
    {
        $this->kubeCollections = 'pods';
        $this->psrClient->setResponse(
            new Response(
                status: 200,
                body: Stream::create(
                    json_encode(
                        [
                            'items' => [
                                [
                                    'metadata' => [
                                        'name' => 'pod1'
                                    ],
                                    'spec' => [
                                        'foo' => 'bar'
                                    ]
                                ],
                            ],
                        ]
                    ),
                ),
            ),
        );
    }

    #[Given('the server answers with the status :code')]
    public function theServerAnswersWithTheStatus(int $code): void
    {
        $this->kubeCollections ??= 'pods';
        $this->psrClient->setResponse(
            new Response(
                status: $code,
                body: Stream::create(
                    json_encode(
                        [
                            'message' => 'Error hath occurred',
                        ],
                    ),
                ),
            ),
        );
    }

    #[Given('a temporary directory for the certificate files')]
    public function aTemporaryDirectoryForTheCertificateFiles(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/kc-behat-' . bin2hex(random_bytes(4));
        mkdir($this->tmpDir, 0700);
        Client::setTmpDir($this->tmpDir);
    }

    /**
     * @return array<int, string>
     */
    private function listTemporaryCertificateFiles(): array
    {
        Assert::assertNotNull($this->tmpDir, 'No temporary directory was defined in this scenario');

        $files = glob($this->tmpDir . '/kubernetes-client-*');

        if (false === $files) {

            $files = [];

        }


        return $files;
    }

    #[Then(':count temporary certificate files must exist')]
    #[Then(':count temporary certificate file must exist')]
    public function temporaryCertificateFilesMustExist(int $count): void
    {
        Assert::assertCount($count, $this->listTemporaryCertificateFiles());
    }

    #[Then('no temporary certificate file must remain')]
    public function noTemporaryCertificateFileMustRemain(): void
    {
        Assert::assertCount(0, $this->listTemporaryCertificateFiles());
    }

    #[When('the user releases the client')]
    public function theUserReleasesTheClient(): void
    {
        $this->kubeClient = null;
        $this->result = null;
        gc_collect_cycles();
    }

    #[When('the user loads a client from this kubeconfig:')]
    public function theUserLoadsAClientFromThisKubeconfig(PyStringNode $kubeconfig): void
    {
        try {
            $this->kubeClient = Client::loadFromKubeConfig(
                content: $kubeconfig->getRaw(),
                httpClient: $this->httpClient,
                baseDirectory: $this->tmpDir,
            );
            $this->kubeCollections = 'pods';
        } catch (Throwable $throwable) {
            $this->error = $throwable;
        }
    }

    #[AfterScenario]
    public function cleanTemporaryDirectory(): void
    {
        Client::setTmpDir(null);
        Client::setTmpNameFunction(null);

        if (null === $this->tmpDir) {
            return;
        }

        $files = glob($this->tmpDir . '/*');

        if (false === $files) {

            $files = [];

        }


        foreach ($files as $file) {
            @unlink($file);
        }

        @rmdir($this->tmpDir);
        $this->tmpDir = null;
    }

    #[Then('the client must refuse the operation with an invalid argument error')]
    public function theClientMustRefuseTheOperationWithAnInvalidArgumentError(): void
    {
        Assert::assertInstanceOf(InvalidArgumentException::class, $this->error);
    }

    #[Given('a pod model from the YAML document:')]
    public function aPodModelFromTheYamlDocument(PyStringNode $document): void
    {
        $this->kubeCollections = 'pods';

        try {
            $this->model = new Pod($document->getRaw(), FileFormat::Yaml);
        } catch (Throwable $throwable) {
            $this->error = $throwable;
        }
    }

    #[Given('a service account identified by a multiline token starting with :value')]
    public function aServiceAccountIdentifiedByAMultilineTokenStartingWith(string $value): void
    {
        $this->token = $value . "\n" . 'second-line-of-the-secret';
    }

    #[Then('the error message must not contain :value')]
    public function theErrorMessageMustNotContain(string $value): void
    {
        Assert::assertInstanceOf(Throwable::class, $this->error);
        Assert::assertStringNotContainsString($value, $this->error->getMessage());
    }

    #[Given('a certificate model :name')]
    public function aCertificateModel(string $name): void
    {
        $this->model = new Certificate(
            [
                'metadata' => [
                    'name' => $name
                ],
                'spec' => [
                    'secretName' => $name
                ]
            ]
        );

        $this->kubeCollections = 'certificates';
    }

    #[When('the user apply a json patch on the resource on the server')]
    public function theUserApplyAJsonPatchOnTheResourceOnTheServer(): void
    {
        Assert::assertNotNull($this->kubeCollections);

        try {
            $this->result = $this->kubeClient
                ->{$this->kubeCollections}()
                ->applyJsonPatch(
                    $this->model,
                    [
                        ['op' => 'replace', 'path' => '/spec/foo', 'value' => 'baz'],
                    ],
                );
        } catch (Throwable $throwable) {
            $this->error = $throwable;
        }
    }

    #[When('the user fetch a collection on the server with inequality label selector')]
    public function theUserFetchACollectionOnTheServerWithInequalityLabelSelector(): void
    {
        Assert::assertNotNull($this->kubeCollections);

        try {
            $this->result = $this->kubeClient
                ->{$this->kubeCollections}()
                ->setLabelSelector([], ['env' => 'prod'])
                ->find();
        } catch (Throwable $throwable) {
            $this->error = $throwable;
        }
    }

    #[Then('the request number :index sent to the cluster must target the uri :uri')]
    public function theRequestNumberSentToTheClusterMustTargetTheUri(int $index, string $uri): void
    {
        Assert::assertNotNull($this->psrClient);
        $requests = $this->psrClient->getRequests();
        Assert::assertArrayHasKey($index - 1, $requests, "No request number $index was sent to the cluster");
        Assert::assertSame($uri, (string) $requests[$index - 1]->getUri());
    }

    #[Given('a pod model without name')]
    public function aPodModelWithoutName(): void
    {
        $this->model = new Pod(
            [
                'spec' => [
                    'foo' => 'bar'
                ]
            ]
        );

        $this->kubeCollections = 'pods';
    }

    #[Given('a pod model :name with the attributes:')]
    public function aPodModelWithTheAttributes(string $name, PyStringNode $attributes): void
    {
        $decoded = json_decode($attributes->getRaw(), true, 512, JSON_THROW_ON_ERROR);
        $decoded['metadata']['name'] = $name;

        $this->model = new Pod($decoded);
        $this->kubeCollections = 'pods';
    }

    #[Given('a config map model :name with the entry :key equal to :value')]
    public function aConfigMapModelWithTheEntryEqualTo(string $name, string $key, string $value): void
    {
        $this->model = new ConfigMap(
            [
                'metadata' => [
                    'name' => $name
                ],
                'data' => [
                    $key => $value
                ]
            ]
        );

        $this->kubeCollections = 'configMaps';
    }

    #[When('the user sends a raw :method request to :uri with the JSON body:')]
    public function theUserSendsARawRequestToWithTheJsonBody(string $method, string $uri, PyStringNode $body): void
    {
        try {
            $this->result = $this->kubeClient->sendRequest(
                method: RequestMethod::from($method),
                uri: $uri,
                body: json_decode($body->getRaw(), true, 512, JSON_THROW_ON_ERROR),
            );
        } catch (Throwable $throwable) {
            $this->error = $throwable;
        }
    }

    #[Given('a persistent volume model :name')]
    public function aPersistentVolumeModel(string $name): void
    {
        $this->model = new PersistentVolume(
            [
                'metadata' => [
                    'name' => $name
                ],
                'spec' => [
                    'capacity' => ['storage' => '1Gi']
                ]
            ]
        );

        $this->kubeCollections = 'persistentVolume';
    }

    #[When('the user watch the resource on the server')]
    public function theUserWatchTheResourceOnTheServer(): void
    {
        Assert::assertNotNull($this->kubeCollections);

        $parser = new class implements StreamingParser {
            public function parse(StreamInterface $stream): StreamingParser
            {
                return $this;
            }
        };

        try {
            $this->result = $this->kubeClient
                ->{$this->kubeCollections}()
                ->stream($this->model, $parser);
        } catch (Throwable $throwable) {
            $this->error = $throwable;
        }
    }

    #[When('the user fetch a collection on the server with the query parameter :name equal to :value')]
    public function theUserFetchACollectionOnTheServerWithTheQueryParameterEqualTo(string $name, string $value): void
    {
        Assert::assertNotNull($this->kubeCollections);

        try {
            $this->result = $this->kubeClient
                ->{$this->kubeCollections}()
                ->find([$name => $value]);
        } catch (Throwable $throwable) {
            $this->error = $throwable;
        }
    }

    #[Given('a custom repository :name for the api group :group')]
    public function aCustomRepositoryForTheApiGroup(string $name, string $group): void
    {
        ThingRepository::$apiGroup = $group;
        $this->repositoryRegistry = new RepositoryRegistry();
        $this->repositoryRegistry[$name] = ThingRepository::class;
    }

    #[Given('the cluster answers every request with an empty collection')]
    public function theClusterAnswersEveryRequestWithAnEmptyCollection(): void
    {
        $this->psrClient->setResponse(
            new Response(
                status: 200,
                body: Stream::create(
                    json_encode(
                        [
                            'items' => [],
                        ]
                    ),
                ),
            ),
        );
    }

    #[When('the user fetch a collection of :collection on the server')]
    public function theUserFetchACollectionOfOnTheServer(string $collection): void
    {
        $this->kubeCollections = $collection;

        try {
            $this->result = $this->kubeClient
                ->{$this->kubeCollections}()
                ->find();
        } catch (Throwable $throwable) {
            $this->error = $throwable;
        }
    }

    #[When('an HTTP client is built with the :adapter instantiator, the verification :state, the CA certificate :ca and the timeout :timeout')]
    public function anHttpClientIsBuiltWithTheInstantiator(string $adapter, string $state, string $ca, int $timeout): void
    {
        $instantiator = match ($adapter) {
            'curl' => new CurlInstantiator(),
            'socket' => new SocketInstantiator(),
            'guzzle7' => new Guzzle7Instantiator(),
            'symfony' => new SymfonyInstantiator(),
        };

        Assert::assertInstanceOf(InstantiatorInterface::class, $instantiator);

        $this->builtHttpClient = $instantiator->build(
            verify: 'enabled' === $state,
            caCertificate: $ca,
            clientCertificate: '/cert.pem',
            clientKey: '/key.pem',
            timeout: $timeout,
        );
    }

    /**
     * Reads the TLS and timeout configuration really applied to the built HTTP client, whatever its adapter.
     *
     * @return array{verify_peer: bool, verify_host: bool, ca: ?string, timeout: ?int}
     */
    private function extractBuiltHttpClientOptions(): array
    {
        $client = $this->builtHttpClient;
        Assert::assertNotNull($client, 'No HTTP client was built');

        if ($client instanceof CurlClient) {
            $options = new ReflectionProperty(CurlClient::class, 'curlOptions')->getValue($client);

            return [
                'verify_peer' => (bool) $options[CURLOPT_SSL_VERIFYPEER],
                'verify_host' => 2 === $options[CURLOPT_SSL_VERIFYHOST],
                'ca' => $options[CURLOPT_CAINFO] ?? null,
                'timeout' => $options[CURLOPT_TIMEOUT] ?? null,
            ];
        }

        if ($client instanceof SocketClient) {
            $config = new ReflectionProperty(SocketClient::class, 'config')->getValue($client);

            return [
                'verify_peer' => (bool) $config['stream_context_options']['ssl']['verify_peer'],
                'verify_host' => (bool) $config['stream_context_options']['ssl']['verify_peer_name'],
                'ca' => $config['stream_context_options']['ssl']['cafile'] ?? null,
                'timeout' => (int) ($config['timeout'] / 1000),
            ];
        }

        if ($client instanceof Guzzle7Client) {
            $guzzle = new ReflectionProperty(Guzzle7Client::class, 'guzzle')->getValue($client);
            $config = new ReflectionProperty(GuzzleClient::class, 'config')->getValue($guzzle);

            $ca = null;
            if (is_string($config['verify'])) {
                $ca = $config['verify'];
            }

            return [
                'verify_peer' => false !== $config['verify'],
                'verify_host' => false !== $config['verify'],
                'ca' => $ca,
                'timeout' => $config['timeout'] ?? null,
            ];
        }

        if ($client instanceof SymfonyHttplug) {
            $inner = new ReflectionProperty(SymfonyHttplug::class, 'client')->getValue($client);
            $options = new ReflectionProperty($inner::class, 'defaultOptions')->getValue($inner);

            $timeout = null;
            if (null !== $options['timeout']) {
                $timeout = (int) $options['timeout'];
            }

            return [
                'verify_peer' => (bool) $options['verify_peer'],
                'verify_host' => (bool) $options['verify_host'],
                'ca' => $options['cafile'] ?? null,
                'timeout' => $timeout,
            ];
        }

        Assert::fail('Unknown HTTP client ' . $client::class);
    }

    #[Then('the built client must verify the peer certificate and the host name')]
    public function theBuiltClientMustVerifyThePeerCertificateAndTheHostName(): void
    {
        $options = $this->extractBuiltHttpClientOptions();
        Assert::assertTrue($options['verify_peer'], 'The peer certificate must be verified');
        Assert::assertTrue($options['verify_host'], 'The host name must be verified');
    }

    #[Then('the built client must verify neither the peer certificate nor the host name')]
    public function theBuiltClientMustVerifyNeitherThePeerCertificateNorTheHostName(): void
    {
        $options = $this->extractBuiltHttpClientOptions();
        Assert::assertFalse($options['verify_peer'], 'The peer certificate must not be verified');
        Assert::assertFalse($options['verify_host'], 'The host name must not be verified');
    }

    #[Then('the built client must use the CA certificate :ca')]
    public function theBuiltClientMustUseTheCaCertificate(string $ca): void
    {
        Assert::assertSame($ca, $this->extractBuiltHttpClientOptions()['ca']);
    }

    #[Then('the built client must use the timeout :timeout')]
    public function theBuiltClientMustUseTheTimeout(int $timeout): void
    {
        Assert::assertSame($timeout, $this->extractBuiltHttpClientOptions()['timeout']);
    }

    private function saveHttpClientInstantiators(): void
    {
        $this->savedInstantiators ??= new ReflectionProperty(HttpClientDiscovery::class, 'instantiatorsList')->getValue();
    }

    #[Given('no supported HTTP client instantiator is available')]
    public function noSupportedHttpClientInstantiatorIsAvailable(): void
    {
        $this->saveHttpClientInstantiators();
        new ReflectionClass(HttpClientDiscovery::class)->setStaticPropertyValue('instantiatorsList', []);
    }

    #[When('an HTTP client is discovered with a CA certificate')]
    public function anHttpClientIsDiscoveredWithACaCertificate(): void
    {
        try {
            $this->builtHttpClient = HttpClientDiscovery::find(caCertificate: '/ca.pem');
        } catch (Throwable $throwable) {
            $this->error = $throwable;
        }
    }

    #[When('an HTTP client is discovered without options')]
    public function anHttpClientIsDiscoveredWithoutOptions(): void
    {
        try {
            $this->builtHttpClient = HttpClientDiscovery::find();
        } catch (Throwable $throwable) {
            $this->error = $throwable;
        }
    }

    #[Then('the discovery must fail with an unsupported options error')]
    public function theDiscoveryMustFailWithAnUnsupportedOptionsError(): void
    {
        Assert::assertInstanceOf(UnsupportedHttpClientOptionsException::class, $this->error);
        Assert::assertNull($this->builtHttpClient);
    }

    #[Then('an HTTP client must have been discovered')]
    public function anHttpClientMustHaveBeenDiscovered(): void
    {
        Assert::assertNull($this->error);
        Assert::assertInstanceOf(ClientInterface::class, $this->builtHttpClient);
    }

    #[AfterScenario]
    public function restoreHttpClientInstantiators(): void
    {
        if (null === $this->savedInstantiators) {
            return;
        }

        new ReflectionClass(HttpClientDiscovery::class)->setStaticPropertyValue(
            'instantiatorsList',
            $this->savedInstantiators,
        );
        $this->savedInstantiators = null;
    }

    #[When('the user changes the client certificate option to :value')]
    public function theUserChangesTheClientCertificateOptionTo(string $value): void
    {
        $this->kubeClient->setOptions(['client_cert' => $value]);
    }

    #[Given('an issuer model :name')]
    public function anIssuerModel(string $name): void
    {
        $this->model = new Issuer(
            [
                'metadata' => [
                    'name' => $name
                ],
                'spec' => [
                    'selfSigned' => []
                ]
            ]
        );

        $this->kubeCollections = 'issuers';
    }

    #[Given('a subnamespace anchor model :name')]
    public function aSubnamespaceAnchorModel(string $name): void
    {
        $this->model = new SubnamespaceAnchor(
            [
                'metadata' => [
                    'name' => $name
                ]
            ]
        );

        $this->kubeCollections = 'subnamespacesAnchors';
    }

    #[Given('a certificate file :name in the temporary directory')]
    public function aCertificateFileInTheTemporaryDirectory(string $name): void
    {
        Assert::assertNotNull($this->tmpDir, 'No temporary directory was defined in this scenario');
        file_put_contents(
            $this->tmpDir . '/' . $name,
            "-----BEGIN CERTIFICATE-----\n" . $name . "\n-----END CERTIFICATE-----\n",
        );
    }
}
