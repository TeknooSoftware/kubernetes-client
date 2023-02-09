Feature: Create a resource with a certificate
  Create a new kubernetes resource from a model instance

  Scenario: Create a new valid resource with a certificate
    Given a Kubernetes cluster
    And an account identified by a certificate client
    And a namespace "behat-test"
    And an instance of this client
    And a pod model "my pod"
    And the model is valid
    When the user create the resource on the server
    Then the server must return an array as response
    And without error

  Scenario: Create a new not valid resource with a certificate
    Given a Kubernetes cluster
    And an account identified by a certificate client
    And a namespace "behat-test"
    And an instance of this client
    And a pod model "my pod"
    And the model is mal formed
    When the user create the resource on the server
    Then the server must return an error "400"
