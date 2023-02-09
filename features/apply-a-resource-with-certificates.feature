Feature: Apply a resource with a certificate
  After apply a model instance, developper can be apply it as resource
  on a Kubernetes cluster : apply or update if it already exists

  Scenario: Apply a new valid resource with a client certificatte
    Given a Kubernetes cluster
    And an account identified by a certificate client
    And a namespace "behat-test"
    And an instance of this client
    And a pod model "my pod"
    And the model is valid
    And the resource does not already exist in the cluster
    When the user apply the resource on the server
    Then the server must return an array as response
    And without error

  Scenario: Apply a new not valid resource with a client certificatte
    Given a Kubernetes cluster
    And an account identified by a certificate client
    And a namespace "behat-test"
    And an instance of this client
    And a pod model "my pod"
    And the model is mal formed
    And the resource does not already exist in the cluster
    When the user apply the resource on the server
    Then the server must return an error "400"

  Scenario: Apply an existent valid resource with a client certificatte
    Given a Kubernetes cluster
    And an account identified by a certificate client
    And a namespace "behat-test"
    And an instance of this client
    And a pod model "my pod"
    And the model is valid
    And the resource already exists in the cluster
    When the user apply the resource on the server
    Then the server must return an array as response
    And without error

  Scenario: Apply an existent new not valid resource with a client certificatte
    Given a Kubernetes cluster
    And an account identified by a certificate client
    And a namespace "behat-test"
    And an instance of this client
    And a pod model "my pod"
    And the model is mal formed
    And the resource already exists in the cluster
    When the user apply the resource on the server
    Then the server must return an error "400"
