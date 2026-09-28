Feature: Custom repository
  A custom repository registered in the repository registry can override how the api version
  of its resource is resolved, and this override is honoured by the requests

  Scenario: Fetch a collection of a custom resource with an overridden api group
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And a custom repository "things" for the api group "acme.example.com/v2"
    And an instance of this client
    And the cluster answers every request with an empty collection
    When the user fetch a collection of "things" on the server
    Then the server must return an empty collection
    And without error
    And the last request sent to the cluster must target the uri "https://api.example.com/apis/acme.example.com/v2/namespaces/behat-test/things"
