# Teknoo Software - Kubernetes Client - Change Log

## [0.30.0] - 2022-12-19
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