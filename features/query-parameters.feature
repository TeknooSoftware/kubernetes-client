Feature: Query parameters of a list request
  Query parameters given to a list request are sent as is, including zero values

  Scenario: A zero resource version is sent to the cluster
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And the cluster has several registered pods
    When the user fetch a collection on the server with the query parameter "resourceVersion" equal to "0"
    Then the server must return a collection of pods
    And without error
    And the last request sent to the cluster must target the uri "https://api.example.com/api/v1/namespaces/behat-test/pods?resourceVersion=0"

  Scenario: An empty parameter is not sent to the cluster
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And the cluster has several registered pods
    When the user fetch a collection on the server with the query parameter "labelSelector" equal to ""
    Then the server must return a collection of pods
    And without error
    And the last request sent to the cluster must target the uri "https://api.example.com/api/v1/namespaces/behat-test/pods"
