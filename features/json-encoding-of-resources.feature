Feature: JSON encoding of the resources
  Manifests are sent as JSON where empty maps are objects, lists stay lists
  and string values are never altered

  Scenario: A config map entry holding brackets is sent unaltered
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And a config map model "my-config" with the entry "config.yml" equal to "items: []"
    And the model is valid
    When the user create the resource on the server
    Then the server must return an array as response
    And without error
    And the last request sent to the cluster must have a JSON body equal to:
      """
      {"kind":"ConfigMap","apiVersion":"v1","metadata":{"name":"my-config"},"data":{"config.yml":"items: []"}}
      """

  Scenario: Empty maps are objects and lists stay lists in a pod manifest
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And a pod model "my-pod" with the attributes:
      """
      {"metadata":{"labels":{}},"spec":{"containers":[{"name":"c","env":[]}],"volumes":[[]]}}
      """
    And the model is valid
    When the user create the resource on the server
    Then the server must return an array as response
    And without error
    And the last request sent to the cluster must have a JSON body equal to:
      """
      {"kind":"Pod","apiVersion":"v1","metadata":{"labels":{},"name":"my-pod"},"spec":{"containers":[{"name":"c","env":{}}],"volumes":[[]]}}
      """

  Scenario: A raw array body keeps its lists
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And the cluster answers every request with a collection of pods
    When the user sends a raw "POST" request to "/pods" with the JSON body:
      """
      {"items":["a","b"],"meta":{},"patch":[{"op":"add","path":"/x","value":[]}]}
      """
    Then without error
    And the last request sent to the cluster must have a JSON body equal to:
      """
      {"items":["a","b"],"meta":{},"patch":[{"op":"add","path":"/x","value":{}}]}
      """
