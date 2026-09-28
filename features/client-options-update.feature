Feature: Update the client options
  Changing a TLS or timeout option after a request rebuilds the HTTP client with the new option

  Scenario: A new inline client certificate is written and used after the change
    Given a Kubernetes cluster
    And an account identified by a certificate client
    And a namespace "behat-test"
    And a temporary directory for the certificate files
    And an instance of this client
    And a pod model "my-pod"
    And the model is valid
    When the user create the resource on the server
    Then the server must return an array as response
    And without error
    And 2 temporary certificate files must exist
    When the user changes the client certificate option to "new-certificate-content"
    And the user create the resource on the server
    Then the server must return an array as response
    And without error
    And 3 temporary certificate files must exist
    When the user releases the client
    Then no temporary certificate file must remain
