# AGENTS.md

## Project Overview
`teknoo/kubernetes-client` is a PHP client library designed for interacting with Kubernetes clusters. It provides models, repositories, and various HTTP client implementations to manage Kubernetes resources such as Pods, Services, Deployments, and more.

## Technology Stack
- **Language**: PHP 8.4+
- **Dependency Management**: Composer
- **Testing**: PHPUnit
- **Static Analysis**: PHPStan
- **Linting**: PHP_CodeSniffer (PSR-2)
- **HTTP Clients Supported**: Guzzle7, Socket, Curl, Symfony

## Project Structure
- `src/`: Contains the core implementation.
    - `Model/`: Kubernetes resource models.
    - `Repository/`: Repositories for interacting with resources.
    - `HttpClient/`: Various HTTP client adapters.
    - `Collection/`: Collections for various Kubernetes resource types.
    - `Enums/`: Enumerations for request methods and patch types.
    - `Exceptions/`: Custom exception classes.
- `tests/`: The test suite for the project.
- `composer.json`: Project configuration and dependencies.
- `CONTRIBUTING.md`: Detailed contribution guidelines.

## Development Workflow

### Installation
To set up the development environment:
```bash
composer update
```

### Testing
The project requires high test coverage (minimum 90%).
Run the test suite using `make test`:
```bash
make test
```

### Static Analysis & Linting
Use `make qa` for quality checks (linting, static analysis, and audit):
```bash
make qa
```

## Standards & Guidelines
- **Coding Standard**: PSR-2.
- **Testing Requirement**: Every new feature or fix must include comprehensive tests. Unconfirmed issues must have a failing test case before acceptance.
- **Branching Strategy**: Pull requests should be submitted from feature or hotfix branches, not directly from the `master` branch.
