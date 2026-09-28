Feature: Resource names are URL encoded
  The namespace and the resource name are single path segments of the request uri,
  a name holding reserved characters must not change the targeted endpoint

  Scenario: A name holding a slash stays a single path segment
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And a pod model "my pod/../secrets"
    And the model is valid
    When the user delete the resource on the server
    Then the server must return an array as response
    And without error
    And the last request sent to the cluster must target the uri "https://api.example.com/api/v1/namespaces/behat-test/pods/my%20pod%2F..%2Fsecrets"

  Scenario: A namespace holding reserved characters stays a single path segment
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "team/a"
    And an instance of this client
    And the cluster has several registered pods
    When the user fetch a collection on the server
    Then the server must return a collection of pods
    And without error
    And the last request sent to the cluster must target the uri "https://api.example.com/api/v1/namespaces/team%2Fa/pods"
