Feature: Record the requests sent to the cluster
  The Behat fake cluster keeps every request it receives, so scenarios can check
  the method, the uri, the headers and the body really sent by the client

  Scenario: Create a resource sends a JSON POST to the namespaced collection
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And a pod model "my-pod"
    And the model is valid
    When the user create the resource on the server
    Then the server must return an array as response
    And without error
    And 1 request must have been sent to the cluster
    And the last request sent to the cluster must use the method "POST"
    And the last request sent to the cluster must target the uri "https://api.example.com/api/v1/namespaces/behat-test/pods"
    And the last request sent to the cluster must have the header "Content-Type" equal to "application/json"
    And the last request sent to the cluster must have the header "Authorization" equal to "Bearer super token"
    And the last request sent to the cluster must have a JSON body equal to:
      """
      {"kind":"Pod","apiVersion":"v1","metadata":{"name":"my-pod"},"spec":{"foo":"bar"}}
      """

  Scenario: Fetch a collection sends a GET without body
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And the cluster has several registered pods
    When the user fetch a collection on the server
    Then the server must return a collection of pods
    And without error
    And the last request sent to the cluster must use the method "GET"
    And the last request sent to the cluster must target the uri "https://api.example.com/api/v1/namespaces/behat-test/pods"
    And the last request sent to the cluster must not have the header "Content-Type"
    And the last request sent to the cluster must have an empty body
