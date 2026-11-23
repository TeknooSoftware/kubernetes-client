Feature: PSR-18 client errors
  An error of a PSR-18 client which is not an HTTPlug client, like a network failure of a native Guzzle client,
  is reported as a bad request error, like the HTTPlug errors

  Scenario: A cluster which can not be reached is reported as a 500 bad request error
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And the cluster can not be reached
    When the user fetch a collection on the server
    Then the client must fail with a bad request error 500
