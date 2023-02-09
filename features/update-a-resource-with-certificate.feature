Feature: Update a resource with a certificate
  Update an existent kubernetes resource from a model instance

  Scenario: Update a valid resource with a certificate
    Given a Kubernetes cluster
    And an account identified by a certificate client
    And a namespace "behat-test"
    And an instance of this client
    And a pod model "my pod"
    And the model is valid
    And the resource already exists in the cluster
    When the user update the resource on the server
    Then the server must return an array as response
    And without error

  Scenario: Update a not valid resource with a certificate
    Given a Kubernetes cluster
    And an account identified by a certificate client
    And a namespace "behat-test"
    And an instance of this client
    And a pod model "my pod"
    And the model is mal formed
    When the user update the resource on the server
    Then the server must return an error "400"
