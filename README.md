Teknoo Software - Kubernetes Client
===================================

[![Latest Stable Version](https://poser.pugx.org/teknoo/kubernetes-client/v/stable)](https://packagist.org/packages/teknoo/kubernetes-client)
[![Latest Unstable Version](https://poser.pugx.org/teknoo/kubernetes-client/v/unstable)](https://packagist.org/packages/teknoo/kubernetes-client)
[![Total Downloads](https://poser.pugx.org/teknoo/kubernetes-client/downloads)](https://packagist.org/packages/teknoo/kubernetes-client)
[![License](https://poser.pugx.org/teknoo/kubernetes-client/license)](https://packagist.org/packages/teknoo/kubernetes-client)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-brightgreen.svg?style=flat)](https://github.com/phpstan/phpstan)

A PHP client for managing a Kubernetes cluster. Control your Kubernetes resources by manipulating manifests, as PHP
arrays, through the Kubernetes HTTP API. The client provides repositories for the most common resources, and it is
possible to define new resources usable with the client.
This is a fork and a rework of the [Maclof Kubernetes library](https://github.com/maclof/kubernetes-client).

Supported API Features
----------------------

### v1
* Config Maps
* Endpoints
* Events
* Limit Ranges
* Namespaces
* Nodes
* Persistent Volumes
* Persistent Volume Claims
* Pods
* Replication Controllers
* Resource Quotas
* Secrets
* Service Accounts
* Services

### apps/v1
* Daemon Sets
* Deployments
* Replica Sets
* Stateful Sets

### autoscaling/v2
* Horizontal Pod Autoscalers

### batch/v1
* Cron Jobs
* Jobs

### networking.k8s.io/v1
* Ingresses
* Network Policies

### rbac.authorization.k8s.io/v1
* Cluster Roles
* Cluster Role Bindings
* Roles
* Role Bindings

### cert-manager.io/v1 (cert-manager)
* Certificates
* Issuers

### hnc.x-k8s.io/v1 (Hierarchical Namespace Controller)
* Subnamespace Anchors

`DeleteOptions` is also available as a model, to pass options to a deletion.

Basic Usage
-----------

```php
use Teknoo\Kubernetes\Client;

$client = new Client([
    'master' => 'https://master.mycluster.com',
    'token' => '/var/run/secrets/kubernetes.io/serviceaccount/token',
]);

// Find pods by label selector
$pods = $client->pods()
    ->setLabelSelector(
        [
            'name'    => 'test',
            'version' => 'a',
        ]
    )->find();

// Both setLabelSelector and setFieldSelector can take an optional
// second parameter which lets you define inequality based selectors (ie using the != operator)
$pods = $client->pods()
    ->setLabelSelector(
        ['name' => 'test'],
        ['env' => 'staging']
    )->find();

// Find pods by field selector
$pods = $client->pods()->setFieldSelector(['metadata.name' => 'test'])->find();

// Find the first pod with a label selector (same for field selector), null when none matches
$pod = $client->pods()->setLabelSelector(['name' => 'test'])->first();
```

Selectors apply to the next query only: they are reset after each `find()`, `first()`, `continue()`, `exists()`
or `stream()` call.

Client options
--------------

| Option        | Description                                                                                                                                                           |
|---------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `master`      | Url of the API server (mandatory).                                                                                                                                    |
| `token`       | Bearer token, or path of a file holding it (a service account token is re-read at each request to follow its rotation). Stream wrapper urls are refused as path.      |
| `ca_cert`     | Path of the CA certificate used to verify the API server, or its PEM content.                                                                                         |
| `client_cert` | Path of the client certificate, or its PEM content.                                                                                                                   |
| `client_key`  | Path of the client key, or its PEM content.                                                                                                                           |
| `verify`      | `true` (default) to verify the TLS certificate of the API server, `false` to disable the verification (not recommended).                                              |
| `timeout`     | Request timeout, in seconds.                                                                                                                                          |
| `namespace`   | Namespace of the requests (`default`), can be changed later with `setNamespace()`.                                                                                    |

Options can be updated after the construction with `setOptions(array $options, bool $reset = false)`; a change of a
TLS or timeout option rebuilds the HTTP client on the next request.

### Temporary certificate files

When a certificate or a key is given as PEM content, or embedded in a kubeconfig, it is written into a private
(mode 0600) temporary file, in the system temporary directory by default. These files are removed when the client
is released (garbage collected), when its options are reset, or at the latest at the end of the script.
The directory and the naming function can be customized:

```php
use Teknoo\Kubernetes\Client;

Client::setTmpDir('/run/my-app');
Client::setTmpNameFunction(fn (string $directory, string $prefix): string => tempnam($directory, $prefix));
```

Authentication Examples
-----------------------

### Insecure HTTP
```php
use Teknoo\Kubernetes\Client;

$client = new Client([
    'master' => 'http://master.mycluster.com',
]);
```

### Service account token
```php
use Teknoo\Kubernetes\Client;

$client = new Client([
    'master' => 'https://kubernetes.default.svc',
    'ca_cert' => '/var/run/secrets/kubernetes.io/serviceaccount/ca.crt',
    'token' => '/var/run/secrets/kubernetes.io/serviceaccount/token',
]);
```

### Client certificate
```php
use Teknoo\Kubernetes\Client;

$client = new Client([
    'master' => 'https://master.mycluster.com',
    'ca_cert' => '/etc/kubernetes/pki/ca.pem',
    'client_cert' => '/etc/kubernetes/pki/client.pem',
    'client_key' => '/etc/kubernetes/pki/client-key.pem',
]);
```

### Connecting from a kubeconfig file
```php
use Teknoo\Kubernetes\Client;
use Teknoo\Kubernetes\Enums\FileFormat;

// Parsing from the file data directly (YAML by default, JSON or PHP array with the FileFormat enum)
$client = Client::loadFromKubeConfig($yamlContent);
$client = Client::loadFromKubeConfig($jsonContent, FileFormat::Json);

// Parsing from the file path (PHP does not expand ~, use an explicit path)
$client = Client::loadFromKubeConfigFile(getenv('HOME') . '/.kube/config');
```

The current context of the kubeconfig is used. Supported attributes: `server`, `certificate-authority` and
`certificate-authority-data`, `insecure-skip-tls-verify`, `client-certificate` and `client-certificate-data`,
`client-key` and `client-key-data`, `token`, `tokenFile` and the `namespace` of the context. Embedded data takes
precedence over file paths, and relative paths are resolved against the directory of the kubeconfig file (or the
`baseDirectory` argument of `loadFromKubeConfig()`). Authentications based on `exec` or `auth-provider` are not
supported.

### HTTP client

The client discovers the installed HTTP adapter (`php-http/curl-client`, `php-http/guzzle7-adapter`,
`php-http/socket-client` or `symfony/http-client`) to apply the TLS and timeout options. An instantiator can be
registered with `HttpClientDiscovery::registerInstantiator()` for a client class not supported out of the box. When
none of the supported adapters is installed, the generic discovery is used and an exception is thrown if a TLS or
timeout option can not be applied. A configured PSR-18 client can also be injected in the constructor; the TLS
options are then not applied to it.

Extending the library
---------------------

### Custom repositories
```php
use Teknoo\Kubernetes\Client;
use Teknoo\Kubernetes\RepositoryRegistry;

$repositories = new RepositoryRegistry();
$repositories['things'] = MyApp\Kubernetes\Repository\ThingRepository::class;

$client = new Client(
    [
        'master' => 'https://master.mycluster.com',
    ],
    $repositories
);

$client->things(); //ThingRepository
```

A repository extends `Teknoo\Kubernetes\Repository\Repository` and defines its `$uri` (the plural of the
resource), its collection class (which defines the model class, holding the `apiVersion` of the resource) and, for
a cluster scoped resource, `$namespace = false`. Repositories of custom resources (CRD) must return
`PatchType::Merge` from `getPatchType()`, or use the `PatchMergeTrait`, as CRDs do not support the strategic merge
patch.

Usage Examples
--------------

### Create/Update a Replication Controller

The below example uses an array to specify the replication controller's attributes.
You can specify the attributes either as an array, a JSON encoded string or a YAML encoded string.
The second parameter of the model constructor is the format of the attributes (`FileFormat` enum) and defaults to
`FileFormat::Array`.

```php
use Teknoo\Kubernetes\Model\ReplicationController;

$replicationController = new ReplicationController([
    'metadata' => [
        'name' => 'nginx-test',
        'labels' => [
            'name' => 'nginx-test',
        ],
    ],
    'spec' => [
        'replicas' => 1,
        'template' => [
            'metadata' => [
                'labels' => [
                    'name' => 'nginx-test',
                ],
            ],
            'spec' => [
                'containers' => [
                    [
                        'name'  => 'nginx',
                        'image' => 'nginx',
                        'ports' => [
                            [
                                'containerPort' => 80,
                                'protocol'      => 'TCP',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
]);

if ($client->replicationControllers()->exists($replicationController->getMetadata('name'))) {
    $client->replicationControllers()->update($replicationController);
} else {
    $client->replicationControllers()->create($replicationController);
}

// or, in a single call: patch the resource when it exists, create it otherwise
$client->replicationControllers()->apply($replicationController);
```

`update()`, `patch()`, `apply()` and `delete()` target a resource by its `metadata.name`: a model without name is
refused with an `InvalidArgumentException`. In a manifest, an empty array is sent as an empty JSON object (a map
such as `labels`), while string values are never altered.

### Patch a resource
```php
use Teknoo\Kubernetes\Enums\PatchType;

// The strategic merge patch is used by default, the default type can be changed on the client
$client->setPatchType(PatchType::Merge);
$client->deployments()->patch($deployment);

// A JSON patch (RFC 6902) is a list of operations
$client->deployments()->applyJsonPatch($deployment, [
    ['op' => 'replace', 'path' => '/spec/replicas', 'value' => 3],
]);
```

### Delete a Replication Controller
```php
$replicationController = $client->replicationControllers()->setLabelSelector(['name' => 'nginx-test'])->first();
if (null !== $replicationController) {
    $client->replicationControllers()->delete($replicationController);
}
```

You can also specify options when performing a deletion, eg. to perform a
[cascading delete](https://kubernetes.io/docs/concepts/architecture/garbage-collection/#cascading-deletion)

```php
use Teknoo\Kubernetes\Model\DeleteOptions;

$client->replicationControllers()->delete(
    $replicationController,
    new DeleteOptions(['propagationPolicy' => 'Background'])
);
```

### Paginate a collection
```php
$pods = $client->pods()->find(limit: 50);
while (null !== $pods) {
    foreach ($pods as $pod) {
        // ...
    }

    $pods = $pods->hasNext() ? $pods->continue() : null;
}
```

Collections extend `Illuminate\Support\Collection`. Operations of Illuminate building a new collection from
arbitrary items (such as `map()`) are not supported by these typed collections, use `toArray()` or a `foreach`.

### Explore and update a model
```php
$explorer = $pod->explore();
$name = $explorer->metadata->name; // scalar values are read as strings, maps as nested explorers
$explorer->metadata->labels = ['app' => 'web'];
$updatedPod = $explorer->getModel(); // a copy, the original model is not altered

// The same with a callable, which also returns a copy
$updatedPod = $pod->updateModel(fn (array $attributes): array => [...$attributes, 'spec' => ['replicas' => 3]]);
```

### Watch a resource and read logs
```php
use Teknoo\Kubernetes\Contracts\Repository\StreamingParser;

$client->pods()->stream($pod, $parser); // $parser implements StreamingParser and reads the watch events
$logs = $client->pods()->logs($pod, ['tailLines' => '100']);
```

The `curl` and `Guzzle` adapters buffer the whole answer of a watch until the API server closes it
(`timeoutSeconds`, 30 seconds by default); the `socket` and `symfony` adapters deliver the events as they arrive.
`PodRepository::exec()` sends a plain HTTP request, while the API server requires a connection upgrade for `exec`,
which this client does not implement.

Support this project
---------------------
This project is free and will remain free. It is fully supported by commercial activities of SASU Teknoo Software
and EIRL Richard DELOGE. If you like it and help me maintain it and evolve it, don't hesitate to support me on
[Patreon](https://patreon.com/teknoo_software) or [Github](https://github.com/sponsors/TeknooSoftware).

Thanks :) Richard.

Credits
-------
EIRL Richard Déloge - <https://deloge.io> - Lead developer.
SASU Teknoo Software - <https://teknoo.software>

About Teknoo Software
---------------------
**Teknoo Software** is a PHP software editor, founded by Richard Déloge, as part of EIRL Richard Déloge.
Teknoo Software's goals : Provide to our partners and to the community a set of high quality services or software,
sharing knowledge and skills.

License
-------
Kubernetes Client is licensed under the 3-Clause BSD License - see the [LICENSE](LICENSE) file for details.

Installation & Requirements
---------------------------
To install this library with composer, run this command :

    composer require teknoo/kubernetes-client

This library requires :

    * PHP 8.4+
    * A PHP autoloader (Composer is recommended)
    * Symfony/Yaml
    * Illuminate/Collections
    * A PSR-18 HTTP client and a PSR-17 factory (for example php-http/curl-client and nyholm/psr7)

Contribute :)
-------------
You are welcome to contribute to this project. Read the [contribution guidelines](CONTRIBUTING.md) and fork it on
[Github](https://github.com/TeknooSoftware/kubernetes-client).
