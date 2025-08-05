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
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Http\Client\Common\HttpMethodsClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Stream;
use PHPUnit\Framework\Assert;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Teknoo\Kubernetes\Client;
use Teknoo\Kubernetes\Collection\Collection;
use Teknoo\Kubernetes\Exceptions\ApiServerException;
use Teknoo\Kubernetes\Model\DeleteOptions;
use Teknoo\Kubernetes\Model\Model;
use Teknoo\Kubernetes\Model\Pod;
use Throwable;

use function array_shift;
use function json_encode;

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
}
