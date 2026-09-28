Feature: Selectors are reset between queries
  Label and field selectors, including the inequality ones, apply to a single query
  and must not leak into the following queries of the shared repository

  Scenario: An inequality label selector does not leak into the next query
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And the cluster answers every request with a collection of pods
    When the user fetch a collection on the server with inequality label selector
    Then the server must return a collection of pods
    And without error
    And the last request sent to the cluster must target the uri "https://api.example.com/api/v1/namespaces/behat-test/pods?labelSelector=env%21%3Dprod"
    When the user fetch a collection on the server
    Then the server must return a collection of pods
    And without error
    And the last request sent to the cluster must target the uri "https://api.example.com/api/v1/namespaces/behat-test/pods"
    And the request number 1 sent to the cluster must target the uri "https://api.example.com/api/v1/namespaces/behat-test/pods?labelSelector=env%21%3Dprod"
    And 2 requests must have been sent to the cluster
