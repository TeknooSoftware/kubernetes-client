Feature: Build a model from a YAML document
  A model can be built from a YAML document, which must describe a mapping

  Scenario: A YAML document holding a scalar is refused
    Given a pod model from the YAML document:
      """
      just a string
      """
    Then the client must refuse the operation with an invalid argument error

  Scenario: An empty YAML document is refused
    Given a pod model from the YAML document:
      """
      """
    Then the client must refuse the operation with an invalid argument error

  Scenario: A YAML mapping builds a model that can be sent to the cluster
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And a pod model from the YAML document:
      """
      metadata:
        name: my-pod
      spec:
        containers:
          - name: nginx
            image: nginx
      """
    And the model is valid
    When the user create the resource on the server
    Then the server must return an array as response
    And without error
    And the last request sent to the cluster must have a JSON body equal to:
      """
      {"kind":"Pod","apiVersion":"v1","metadata":{"name":"my-pod"},"spec":{"containers":[{"name":"nginx","image":"nginx"}]}}
      """
