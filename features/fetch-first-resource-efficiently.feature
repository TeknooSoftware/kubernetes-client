Feature: Fetch the first resource efficiently
  Fetching the first resource of a collection asks the cluster for a single item

  Scenario: Fetch the first resource asks a single item
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And the cluster has several registered pods
    When the user fetch the first resource on the server
    Then the server must return a pod model
    And without error
    And the last request sent to the cluster must target the uri "https://api.example.com/api/v1/namespaces/behat-test/pods?limit=1"
