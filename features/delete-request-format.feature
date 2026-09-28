Feature: Format of the delete requests
  A deletion must be sent with the uppercase DELETE method, as HTTP methods are case sensitive

  Scenario: Delete a resource sends an uppercase DELETE without body
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And a pod model "my-pod"
    And the model is valid
    When the user delete the resource on the server
    Then the server must return an array as response
    And without error
    And the last request sent to the cluster must use the method "DELETE"
    And the last request sent to the cluster must target the uri "https://api.example.com/api/v1/namespaces/behat-test/pods/my-pod"
    And the last request sent to the cluster must not have the header "Content-Type"
    And the last request sent to the cluster must have an empty body

  Scenario: Delete a resource with options sends the options as a JSON body
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And a pod model "my-pod"
    And the model is valid
    When the user recursive delete the resource on the server
    Then the server must return an array as response
    And without error
    And the last request sent to the cluster must use the method "DELETE"
    And the last request sent to the cluster must have the header "Content-Type" equal to "application/json"
    And the last request sent to the cluster must have a JSON body equal to:
      """
      {"kind":"DeleteOptions","apiVersion":"v1","propagationPolicy":"Background"}
      """
