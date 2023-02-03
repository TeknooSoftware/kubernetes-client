# Teknoo Software - Kubernetes Client - Change Log

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