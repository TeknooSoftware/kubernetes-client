Feature: Patch type isolation
  The patch type used by a request must not leak into the following requests,
  whatever the repository or the patch method used

  Scenario: A JSON patch does not change the type of the next patch
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And a pod model "my-pod"
    And the model is valid
    When the user apply a json patch on the resource on the server
    Then the server must return an array as response
    And without error
    And the last request sent to the cluster must use the method "PATCH"
    And the last request sent to the cluster must have the header "Content-Type" equal to "application/json-patch+json"
    And the last request sent to the cluster must have a JSON body equal to:
      """
      [{"op":"replace","path":"/spec/foo","value":"baz"}]
      """
    When the user patch the resource on the server
    Then the server must return an array as response
    And without error
    And the last request sent to the cluster must have the header "Content-Type" equal to "application/strategic-merge-patch+json"
    And the request number 1 sent to the cluster must have the header "Content-Type" equal to "application/json-patch+json"
    And 2 requests must have been sent to the cluster

  Scenario: A merge patch repository does not change the type used by other repositories
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And a certificate model "my-cert"
    And the model is valid
    When the user patch the resource on the server
    Then the server must return an array as response
    And without error
    And the last request sent to the cluster must have the header "Content-Type" equal to "application/merge-patch+json"
    Given a pod model "my-pod"
    And the model is valid
    When the user patch the resource on the server
    Then the server must return an array as response
    And without error
    And the last request sent to the cluster must have the header "Content-Type" equal to "application/strategic-merge-patch+json"
    And 2 requests must have been sent to the cluster
