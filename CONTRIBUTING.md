# Contributing

 * Coding standard for the project is [PSR-12](https://www.php-fig.org/psr/psr-12/)
 * Any contribution must provide tests for additional introduced conditions, in both the PHPUnit and the Behat suites
 * Existing tests must not be modified: a failing test signals a regression. If a test locks a real bug, discuss it
   in the pull request before changing it
 * Any un-confirmed issue needs a failing test case before being accepted
 * Pull requests must be sent from a new hotfix/feature branch, not from `master`.

## Installation

To install the project and run the tests, you need to clone it first:

```sh
$ git clone https://github.com/TeknooSoftware/kubernetes-client.git
```

You will then need to run a composer installation:

```sh
$ cd kubernetes-client
$ make
```

## Testing

The PHPUnit and Behat versions to be used are the ones installed as dev dependencies via composer:

```sh
$ make test
```

Quality checks (lint, static analysis, coding standard and audit) are run with:

```sh
$ make qa
```

Accepted coverage for new contributions is 90%. Any contribution not satisfying this requirement
won't be merged.

For any questions, contact me : [richard@teknoo.software](mailto:richard@teknoo.software) :)

## Support this project

This project is free and will remain free, but it is developed on my personal time.
If you like it and help me maintain it and evolve it, don't hesitate to support me on [Patreon](https://patreon.com/teknoo_software).
Thanks :) Richard.
