# Teknoo Software - Kubernetes Client - Change Log

## [1.4.3] - 2023-10-19
### Stable Release
- Prevent HTTP Header injection via the master's token passed to the client. 
    (Forbid all End of Line in the token)
- Forbid URL as token's path in client configuration.

## [1.4.2] - 2023-06-29
### Stable Release
- Upgrade lib, requires illuminate/contracts 1.10+

## [1.4.1] - 2023-06-07
### Stable Release
- Drop support of Symfony Client 6.2, require 6.3 or newer

## [1.4.0] - 2023-05-22
### Stable Release
- Add Stateful sets support

## [1.3.4] - 2023-05-15
### Stable Release
- Update dev lib requirements
- Update copyrights

## [1.3.3] - 2023-05-08
### Stable Release
- Remove deprecated in HttpClientDiscover

## [1.3.2] - 2023-04-16
### Stable Release
- Update dev lib requirements
- Support PHPUnit 10.1+
- Migrate phpunit.xml

## [1.3.1] - 2023-04-11
### Stable Release
- Allow psr/http-message 2

## [1.3.0] - 2023-03-22
### Stable Release
- Add `timeout` option

## [1.2.2] - 2023-03-12
### Stable Release
- QA

## [1.2.1] - 2023-02-17
### Stable Release
- Add `Client::setTmpNameFunction()` and `Client::setTmpDir()` to allow developpers to custom the client about
  temp file generation

## [1.2.0] - 2023-02-15
### Stable Release
- Support `ca_cert` option

## [1.1.1] - 2023-02-11
### Stable Release
- Remove phpcpd and upgrade phpunit.xml

## [1.1.0] - 2023-02-09
### Stable Release
- Add `HttpClientDiscovery` to manage client certificate and disable ssl verification. 
- Support authentification with client certificate.
- Support disabling ssl verification.

## [1.0.2] - 2023-02-03
### Stable Release
- Update dev libs to support PHPUnit 10 and remove unused phploc

## [1.0.1] - 2023-01-16
### Stable Release
- Rename `Teknoo\Kubernetes\Contract\` to `Teknoo\Kubernetes\Contracts\`

## [1.0.0] - 2023-01-08
### Stable Release
- First release

## [0.31.0] - 2023-01-08
### Dev Release
- Fix API Version issue on request
- `Model::$apiVersion` is now a static
- `Model::getApiVersion()` is now static
- Remove `Model::setApiVersion`
- `Collection::$modelClassName` is now static
- Add `Collection::getModelClass()` static method

## [0.30.3] - 2023-01-07
### Dev Release
- QA

## [0.30.2] - 2023-01-04
### Dev Release
- Client::health()
- QA

## [0.30.1] - 2023-01-04
### Dev Release
- Client::setOptions (and Client's construction) require a master value to be executed
- All 40x Errors returned by Kubernetes API throw now an exception  

## [0.30.0] - 2022-12-31
### Dev Release
- Fork from [Maclof Kuebrnetes library](https://github.com/maclof/kubernetes-client)
- Rewrite to PSR-1, PSR-12, PSR-4.
- Follow SOLID rules.
- Rename `Models` to `Model`, `Repositories` to `Repository` and `Collections` to `Collection`.
- Remove dead code.
- Migrate to `Teknoo` namespace.
- Factorise codes in abstract.
- Full tests coverage for PHP Unit.
- Repository's watch method require a `StreamingParser` implementation passed as argument to avoid hard dependency to 
  a third library
- Use Enums
- QA and static analyze