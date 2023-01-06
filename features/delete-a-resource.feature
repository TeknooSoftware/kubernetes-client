Feature: Delete a resource
  Delete a Kubernetes resource from a model instance

  Scenario: Delete a valid resource
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And a pod model "my pod"
    And the model is valid
    And the resource already exists in the cluster
    When the user delete the resource on the server
    Then the server must return an array as response
    And without error

  Scenario: Recursive delete a valid resource
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And a pod model "my pod"
    And the model is valid
    And the resource already exists in the cluster
    When the user recursive delete the resource on the server
    Then the server must return an array as response
    And without error