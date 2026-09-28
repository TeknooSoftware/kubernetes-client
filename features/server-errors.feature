Feature: Server errors
  A server error is reported with the real status code answered by the cluster

  Scenario: A 503 answer is reported as a 503 error
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And the server answers with the status 503
    When the user fetch a collection on the server
    Then the server must return an error 503

  Scenario: A 500 answer is still reported as a 500 error
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And the server answers with the status 500
    When the user fetch a collection on the server
    Then the server must return an error 500

  Scenario: A 404 answer is reported as a 404 error
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And the server answers with the status 404
    When the user fetch a collection on the server
    Then the server must return an error 404
