Feature: Custom resources
  Custom resources are addressed on their real api group and plural,
  and patched with a merge patch as CRDs do not support the strategic merge patch

  Scenario: Create an issuer on the cert-manager api group
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And an issuer model "my-issuer"
    And the model is valid
    When the user create the resource on the server
    Then the server must return an array as response
    And without error
    And the last request sent to the cluster must target the uri "https://api.example.com/apis/cert-manager.io/v1/namespaces/behat-test/issuers"
    And the last request sent to the cluster must have a JSON body equal to:
      """
      {"kind":"Issuer","apiVersion":"cert-manager.io/v1","metadata":{"name":"my-issuer"},"spec":{"selfSigned":{}}}
      """

  Scenario: Patch a subnamespace anchor on its plural with a merge patch
    Given a Kubernetes cluster
    And a service account identified by a token "super token"
    And a namespace "behat-test"
    And an instance of this client
    And a subnamespace anchor model "child"
    And the model is valid
    When the user patch the resource on the server
    Then the server must return an array as response
    And without error
    And the last request sent to the cluster must use the method "PATCH"
    And the last request sent to the cluster must target the uri "https://api.example.com/apis/hnc.x-k8s.io/v1/namespaces/behat-test/subnamespaceanchors/child"
    And the last request sent to the cluster must have the header "Content-Type" equal to "application/merge-patch+json"
