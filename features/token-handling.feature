Feature: Bearer token handling
  The token option holds the bearer token itself or the path of a file holding it.
  Invalid tokens are refused without ever exposing the secret in the error message.

  Scenario: A token holding a colon is sent as bearer token
    Given a Kubernetes cluster
    And a service account identified by a token "user:p4ss"
    And a namespace "behat-test"
    And an instance of this client
    And the cluster has several registered pods
    When the user fetch a collection on the server
    Then the server must return a collection of pods
    And without error
    And the last request sent to the cluster must have the header "Authorization" equal to "Bearer user:p4ss"

  Scenario: A stream wrapper url is refused as token path
    Given a Kubernetes cluster
    And a service account identified by a token "phar://evil.phar/token"
    And a namespace "behat-test"
    And an instance of this client
    And the cluster has several registered pods
    When the user fetch a collection on the server
    Then the client must refuse the operation with an invalid argument error
    And 0 requests must have been sent to the cluster

  Scenario: A multiline token is refused without leaking it
    Given a Kubernetes cluster
    And a service account identified by a multiline token starting with "very-secret-value"
    And a namespace "behat-test"
    And an instance of this client
    And the cluster has several registered pods
    When the user fetch a collection on the server
    Then the client must refuse the operation with an invalid argument error
    And the error message must not contain "very-secret-value"
    And the error message must not contain "second-line-of-the-secret"
    And 0 requests must have been sent to the cluster
