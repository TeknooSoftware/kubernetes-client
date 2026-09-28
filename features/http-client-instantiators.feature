Feature: HTTP client instantiators
  Every supported HTTP adapter must apply the TLS verification, the CA certificate
  and the timeout options the same way

  Scenario Outline: TLS verification, CA certificate and timeout are applied by every adapter
    When an HTTP client is built with the "<adapter>" instantiator, the verification "enabled", the CA certificate "/ca.pem" and the timeout 20
    Then the built client must verify the peer certificate and the host name
    And the built client must use the CA certificate "/ca.pem"
    And the built client must use the timeout 20

    Examples:
      | adapter |
      | curl    |
      | socket  |
      | guzzle7 |
      | symfony |

  Scenario Outline: A disabled verification is honoured by every adapter, even with a CA certificate
    When an HTTP client is built with the "<adapter>" instantiator, the verification "disabled", the CA certificate "/ca.pem" and the timeout 20
    Then the built client must verify neither the peer certificate nor the host name
    And the built client must use the timeout 20

    Examples:
      | adapter |
      | curl    |
      | socket  |
      | guzzle7 |
      | symfony |
