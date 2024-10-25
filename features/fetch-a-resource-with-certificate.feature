Feature: Fetch a resource with a certificate
  Fetch as model instance a Kubernetes resource from its name

  Scenario: Fetch an existent resource with a certificate
    Given a Kubernetes cluster
    And an account identified by a certificate client
    And a namespace "behat-test"
    And an instance of this client
    And the cluster has several registered pods
    When the user fetch the first resource on the server
    Then the server must return a pod model
    And without error

  Scenario: Fetch a missing resource with a certificate
    Given a Kubernetes cluster
    And an account identified by a certificate client
    And a namespace "behat-test"
    And an instance of this client
    And the cluster has no registered pod
    When the user fetch the first resource on the server
    Then the server must return a null response
    And without error

  Scenario: Fetch a collection with a certificate
    Given a Kubernetes cluster
    And an account identified by a certificate client
    And a namespace "behat-test"
    And an instance of this client
    And the cluster has several registered pods
    When the user fetch a collection on the server
    Then the server must return a collection of pods
    And without error

  Scenario: Fetch a collection with label filter with a certificate
    Given a Kubernetes cluster
    And an account identified by a certificate client
    And a namespace "behat-test"
    And an instance of this client
    And the cluster has several registered pods
    When the user fetch a collection on the server with label selector
    Then the server must return a collection of pods
    And without error

  Scenario: Fetch a collection limited and with a certificate
    Given a Kubernetes cluster
    And an account identified by a certificate client
    And a namespace "behat-test"
    And an instance of this client
    And the cluster has several registered pods will be fetched in limited
    When the user fetch a limited collection on the server
    Then the server must return a limited collection of pods
    And without error
    When the user fetch the next collection on the server
    And the server must return a limited collection of pods
    And without error
    When the user fetch the next collection on the server
    And the server must return a final collection of pods
    And without error

  Scenario: Fetch an empty collection with a certificate
    Given a Kubernetes cluster
    And an account identified by a certificate client
    And a namespace "behat-test"
    And an instance of this client
    And the cluster has no registered pod
    When the user fetch a collection on the server
    Then the server must return an empty collection
    And without error
