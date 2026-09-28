Feature: HTTP client discovery
  The client discovers an installed HTTP adapter able to apply the TLS and timeout options.
  The generic fallback must not silently drop the options.

  Scenario: The fallback discovery refuses TLS options it can not apply
    Given no supported HTTP client instantiator is available
    When an HTTP client is discovered with a CA certificate
    Then the discovery must fail with an unsupported options error

  Scenario: The fallback discovery still works without options
    Given no supported HTTP client instantiator is available
    When an HTTP client is discovered without options
    Then an HTTP client must have been discovered
