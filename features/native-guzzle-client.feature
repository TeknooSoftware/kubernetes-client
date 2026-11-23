Feature: Native Guzzle client
  Guzzle 7 and 8 are PSR-18 clients: they are supported without adapter and receive the TLS and timeout options.
  The native Guzzle client is often installed by another dependency, it is chosen only when no other supported
  or registered HTTP client is installed

  Scenario: TLS verification, CA certificate and timeout are applied to the native Guzzle client
    When an HTTP client is built with the "guzzle" instantiator, the verification "enabled", the CA certificate "/ca.pem" and the timeout 20
    Then the built client must verify the peer certificate and the host name
    And the built client must use the CA certificate "/ca.pem"
    And the built client must use the timeout 20

  Scenario: A disabled verification is honoured by the native Guzzle client, even with a CA certificate
    When an HTTP client is built with the "guzzle" instantiator, the verification "disabled", the CA certificate "/ca.pem" and the timeout 20
    Then the built client must verify neither the peer certificate nor the host name
    And the built client must use the timeout 20

  Scenario: The native Guzzle client is discovered with the TLS options
    Given only the native Guzzle client is available
    When an HTTP client is discovered with a CA certificate
    Then the built client must be a native Guzzle client
    And the built client must use the CA certificate "/ca.pem"

  Scenario: A registered instantiator is preferred to the native Guzzle client
    Given only the native Guzzle client is available
    And an instantiator is registered for another HTTP client class
    When an HTTP client is discovered without options
    Then the built client must be built by the registered instantiator

  Scenario: The client class of the deprecated Guzzle 7 adapter gives a native Guzzle client
    When an HTTP client is discovered for the client class "Http\Adapter\Guzzle7\Client" with a CA certificate
    Then the built client must be a native Guzzle client
    And the built client must use the CA certificate "/ca.pem"
