# AGENTS.md

## Project Overview
`teknoo/kubernetes-client` is a PHP client library designed for interacting with Kubernetes clusters. It provides
models, repositories, and various HTTP client adapters to manage Kubernetes resources such as Pods, Services,
Deployments, and more.

## Technology Stack
- **Language**: PHP 8.4+
- **Dependency Management**: Composer
- **Testing**: PHPUnit (unit tests) and Behat (behaviour tests against a fake cluster)
- **Static Analysis**: PHPStan (level max)
- **Linting**: PHP_CodeSniffer (PSR-12)
- **HTTP Clients Supported**: php-http/curl-client, php-http/guzzle7-adapter, php-http/socket-client, symfony/http-client

## Project Structure
- `src/`: Contains the core implementation.
    - `Client.php`: entry point, options, kubeconfig loading, request sending and error handling.
    - `HttpClientDiscovery.php`: discovery of the installed HTTP adapter, instantiated with the TLS/timeout options.
    - `RepositoryRegistry.php`: map of the repository accessors (`$client->pods()`...).
    - `Model/`: Kubernetes resource models (`Model/Attribute/Explorer.php` explores and edits attributes).
    - `Repository/`: Repositories for interacting with resources (`Repository/Strategy/PatchMergeTrait.php` for CRDs).
    - `Collection/`: Collections for various Kubernetes resource types (extend Illuminate collections).
    - `HttpClient/`: Instantiators of the supported HTTP adapters.
    - `Contracts/`: Interfaces (streaming parser).
    - `Enums/`: `FileFormat`, `PatchType` and `RequestMethod`.
    - `Exception/` and `Exceptions/`: Custom exception classes (both directories are in use).
    - `Support/`: Internal helpers (temporary files ownership, JSON normalisation).
- `tests/`: The PHPUnit test suite (`tests/fixtures/` holds kubeconfig, token and schema fixtures).
- `tests/Behat/FeatureContext.php` and `features/`: The Behat suite, run against an in-memory fake API server.
- `composer.json`: Project configuration and dependencies.
- `CONTRIBUTING.md`: Detailed contribution guidelines.

## Development Workflow

### Installation
To set up the development environment:
```bash
make
```

### Testing
Run both suites (PHPUnit with coverage, then Behat) using `make test` (requires Xdebug for the coverage):
```bash
make test
```
Without coverage:
```bash
php vendor/bin/phpunit -c phpunit.xml --no-coverage
php vendor/bin/behat
```

### Static Analysis & Linting
Use `make qa` for quality checks (lint, static analysis, coding standard and audit), or `make qa-offline` without
the composer audit:
```bash
make qa
```

## Standards & Guidelines
- **Coding Standard**: PSR-12.
- **Testing Requirement**: Every new feature or fix must include new tests in both suites (PHPUnit test files under
  `tests/`, Behat scenarios under `features/`). Existing tests must not be modified: a failing test signals a
  regression. When an existing test locks a real bug, ask before changing it. Unconfirmed issues must have a failing
  test case before acceptance. The Behat fake cluster records the requests it receives, so scenarios can assert the
  method, uri, headers and body really sent.
- **Commits**: one commit per bug or fix, each one green with its own tests.
- **Compatibility**: the public API and the documented behaviour must not be broken; behaviour changes must be
  listed in the changelog.
- **Temporary files**: certificates and keys given as PEM content or embedded in a kubeconfig are written into
  private temporary files owned by the client instance and removed when it is released; tests must point
  `Client::setTmpDir()` to a scratch directory and reset it in `tearDown()`.
- **Branching Strategy**: Pull requests should be submitted from feature or hotfix branches, not directly from the
  `master` branch.
