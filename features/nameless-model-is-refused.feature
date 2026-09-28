Feature: A model without name is refused for named operations
  Without a metadata.name, the request would target the whole collection instead of a resource,
  so the client must refuse it before sending anything to the cluster

  Scenario: Delete a model without name is refused
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And a pod model without name
    And the model is valid
    When the user delete the resource on the server
    Then the client must refuse the operation with an invalid argument error
    And 0 requests must have been sent to the cluster

  Scenario: Update a model without name is refused
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And a pod model without name
    And the model is valid
    When the user update the resource on the server
    Then the client must refuse the operation with an invalid argument error
    And 0 requests must have been sent to the cluster

  Scenario: Patch a model without name is refused
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And a pod model without name
    And the model is valid
    When the user patch the resource on the server
    Then the client must refuse the operation with an invalid argument error
    And 0 requests must have been sent to the cluster

  Scenario: Create a model without name is still possible, the cluster may generate the name
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And a pod model without name
    And the model is valid
    When the user create the resource on the server
    Then the server must return an array as response
    And without error
    And 1 request must have been sent to the cluster
