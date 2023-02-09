Feature: Patch a resource with a service account token
  Allow patch fully or partialy a kubernetes resourcce via the REST API.
  The resource must be a model instance. The client must return the result
  of the operation as array

  Scenario: Patch a valid resource with a service account token
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And a pod model "my pod"
    And the model is valid
    When the user patch the resource on the server
    Then the server must return an array as response
    And without error

  Scenario: Patch a not valid resource with a service account token
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And a pod model "my pod"
    And the model is mal formed
    When the user patch the resource on the server
    Then the server must return an error "400"
