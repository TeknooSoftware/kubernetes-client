Feature: Watch a cluster scoped resource
  Cluster scoped resources such as persistent volumes are watched on a path without namespace

  Scenario: Watch a persistent volume
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And a persistent volume model "my-pv"
    And the model is valid
    When the user watch the resource on the server
    Then without error
    And the last request sent to the cluster must use the method "GET"
    And the last request sent to the cluster must target the uri "https://api.example.com/api/v1/persistentvolumes?watch=1&timeoutSeconds=30&fieldSelector=metadata.name%3Dmy-pv"

  Scenario: Delete a persistent volume
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And a persistent volume model "my-pv"
    And the model is valid
    When the user delete the resource on the server
    Then the server must return an array as response
    And without error
    And the last request sent to the cluster must target the uri "https://api.example.com/api/v1/persistentvolumes/my-pv"
