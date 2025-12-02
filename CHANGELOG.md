# Teknoo Software - Kubernetes Client - Change Log

## [2.0.2] - 2025-12-02
### Stable Release
- Update dev libraries

## [2.0.1] - 2025-09-02
### Stable Release
- Fix issue with HttpClientDiscovery to return instances managed by a referenced instantiator instead
  call the `HttpClientDiscovery::findOneByType` method.

## [2.0.0] - 2025-08-05
### Stable Release
- Drop support of PHP 8.3
- Requires PHP 8.4
- Update to PHPStan 2
- Fix some QA issues
- Switch license from MIT to 3-Clause BSD
- Remove last deprecated references about HttpClient

## [1.7.3] - 2025-02-25
### Stable Release
- Allow Illuminate collections 12

## [1.7.2] - 2025-02-07
### Stable Release
- Update dev lib requirements
  - Require Symfony libraries 6.4 or 7.2
  - Update to PHPUnit 12
- Drop support of PHP 8.2
  - The library stay usable with PHP 8.2, without any waranties and tests
  - In the next major release, Support of PHP 8.2 will be dropped

## [1.7.1] - 2024-11-25
### Stable Release
- Fix deprecation into test with PHP 8.4

## [1.7.0] - 2024-10-25
### Stable Release
- Fix `Explorer` from model, to not alter the model's attribute from the model. A clone will be eturned, 
  to get the same behavior of alter.
- Add and Support of the parameter `limit` for collections when the method `find` is passed.
  Collections have new method to known if others results are available, to get the query's value, the continue token 
  and directly get the next collection by calling its method `continue`. 
  ** Warning, if you use map from the `illuminate/collection`, these token will be lost. **
- Add a method `continue` in Repostiories to get the next dataset. 

## [1.6.0] - 2024-10-24
### Stable Release
- Add `Explorer` to explore model's attributes as object with object chaining

## [1.5.4] - 2024-10-06
### Stable Release
- Update dev lib requirements

## [1.5.3] - 2024-09-24
### Stable Release
- Remove deprecations about PHP 8.4

## [1.5.2] - 2024-04-26
### Stable Release
- Add flag `JSON_THROW_ON_ERROR` on all `json_encode`/`json_decode`

## [1.5.1] - 2024-04-10
### Stable Release
- Support `illuminate/collections` and `illuminate/contracts` 11+

## [1.5.0] - 2024-03-04
### Stable Release
- Rename and fix `Quota` to `ResourceQuota`.
- Add `LimiteRange`.

## [1.4.4] - 2023-11-29
### Stable Release
- Update dev lib requirements
- Support Symfony 7.0+

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