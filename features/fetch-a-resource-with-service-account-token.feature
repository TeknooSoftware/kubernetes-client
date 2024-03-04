Feature: Fetch a resource with a service account token
  Fetch as model instance a Kubernetes resource from its name

  Scenario: Fetch an existent resource with a service account token
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And the cluster has several registered pods
    When the user fetch the first resource on the server
    Then the server must return a pod model
    And without error

  Scenario: Fetch a missing resource with a service account token
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And the cluster has no registered pod
    When the user fetch the first resource on the server
    Then the server must return a null response
    And without error

  Scenario: Fetch a collection with a service account token
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And the cluster has several registered pods
    When the user fetch a collection on the server
    Then the server must return a collection of pods
    And without error

  Scenario: Fetch a collection with label filter with a service account token
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And the cluster has several registered pods
    When the user fetch a collection on the server with label selector
    Then the server must return a collection of pods
    And without error

  Scenario: Fetch an empty collection with a service account token
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And the cluster has no registered pod
    When the user fetch a collection on the server
    Then the server must return an empty collection
    And without error
