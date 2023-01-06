Feature: Apply a resource
  After apply a model instance, developper can be apply it as resource
  on a Kubernetes cluster : apply or update if it already exists

  Scenario: Apply a new valid resource
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And a pod model "my pod"
    And the model is valid
    And the resource does not already exist in the cluster
    When the user apply the resource on the server
    Then the server must return an array as response
    And without error

  Scenario: Apply a new not valid resource
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And a pod model "my pod"
    And the model is mal formed
    And the resource does not already exist in the cluster
    When the user apply the resource on the server
    Then the server must return an error "400"

  Scenario: Apply an existent valid resource
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And a pod model "my pod"
    And the model is valid
    And the resource already exists in the cluster
    When the user apply the resource on the server
    Then the server must return an array as response
    And without error

  Scenario: Apply an existent new not valid resource
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And a pod model "my pod"
    And the model is mal formed
    And the resource already exists in the cluster
    When the user apply the resource on the server
    Then the server must return an error "400"
