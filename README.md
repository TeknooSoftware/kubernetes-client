Teknoo Software - Kubernetes Client
===================================

[![Latest Stable Version](https://poser.pugx.org/teknoo/kubernetes-client/v/stable)](https://packagist.org/packages/teknoo/kubernetes-client)
[![Latest Unstable Version](https://poser.pugx.org/teknoo/kubernetes-client/v/unstable)](https://packagist.org/packages/teknoo/kubernetes-client)
[![Total Downloads](https://poser.pugx.org/teknoo/kubernetes-client/downloads)](https://packagist.org/packages/teknoo/kubernetes-client)
[![License](https://poser.pugx.org/teknoo/kubernetes-client/license)](https://packagist.org/packages/teknoo/kubernetes-client)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-brightgreen.svg?style=flat)](https://github.com/phpstan/phpstan)

A PHP client for managing a Kubernetes cluster. 
This is a fork and a rework from [Maclof Kuebrnetes library](https://github.com/maclof/kubernetes-client).

Supported API Features
----------------------

### v1
* Config Maps
* Delete Options
* EndPoints
* Endpoints
* Events
* Namespaces
* Nodes
* Persistent Volume
* Persistent Volume Claims
* Pods
* Quota
* Replica Sets
* Replication Controllers
* Secrets
* Service Account
* Services

### autoscaling/v2
* Horizontal Pad Autoscaler

### batch/v1
* CronJobs
* Jobs

### batch/v1beta1
* Cron Jobs

### apps/v1
* Daemon Set
* Deployments
* ReplicaSet

### extensions/v1beta1
* Daemon Sets

### networking.k8s.io/v1
* Ingresses
* Network Policies

### certmanager.k8s.io/v1
* Certificates
* Issuers

### rbac.authorization.k8s.io/v1
* ClusterRole
* ClusterRoleBinding
* Role
* RoleBinding

### hnc.x-k8s.io/v1
* Subnamespace Anchor

Basic Usage
-----------

```php;
use Teknoo\Kubernetes\Client;

$client = new Client([
	'master' => 'http://master.mycluster.com',
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

// Find first pod with label selector (same for field selector)
$pod = $client->pods()->setLabelSelector(['name' => 'test'])->first();
```

## Authentication Examples

### Insecure HTTP
```php
use Teknoo\Kubernetes\Client;
$client = new Client([
	'master' => 'http://master.mycluster.com',
]);
```

### Connecting from a kubeconfig file
```php
use Teknoo\Kubernetes\Client;

// Parsing from the file data directly
$client = Client::loadFromKubeConfig('kubeconfig yaml data');

// Parsing from the file path
$client = Client::loadFromKubeConfigFile('~/.kube/config.yml');
```

## Extending a library

### Custom repositories
```php
use Teknoo\Kubernetes\Client;

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

## Usage Examples

### Create/Update a Replication Controller

The below example uses an array to specify the replication controller's attributes. 
You can specify the attributes either as an array, JSON encoded string or a YAML encoded string. 
The second parameter to the model constructor is the data type and defaults to array.

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
$client->replicationControllers()->apply($replicationController);
// or

```

### Delete a Replication Controller
```php
$replicationController = $client->replicationControllers()->setLabelSelector(['name' => 'nginx-test'])->first();
$client->replicationControllers()->delete($replicationController);
```

You can also specify options when performing a deletion, eg. to perform [cascading delete](https://kubernetes.io/docs/concepts/workloads/controllers/garbage-collection/#setting-the-cascading-deletion-policy)

```php
use Teknoo\Kubernetes\Model\DeleteOptions;

$client->replicationControllers()->delete(
	$replicationController,
	new DeleteOptions(['propagationPolicy' => 'Background'])
);
```

Support this project
---------------------
This project is free and will remain free. It is fully supported by the activities of the EIRL.
If you like it and help me maintain it and evolve it, don't hesitate to support me on
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
Kubernetes Client is licensed under the MIT License - see the licenses folder for details.

Installation & Requirements
---------------------------
To install this library with composer, run this command :

    composer require teknoo/kubernetes-client

This library requires :

    * PHP 8.1+
    * A PHP autoloader (Composer is recommended)
    * Symfony/Yaml.
    * Illuminate/Collections

Contribute :)
-------------
You are welcome to contribute to this project. [Fork it on Github](CONTRIBUTING.md)


