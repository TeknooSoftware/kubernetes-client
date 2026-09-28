Feature: Release the temporary certificate files
  Certificates and keys given as inline PEM content or embedded in a kubeconfig are written
  into private temporary files, which must be removed when the client is released

  Scenario: Inline certificates are written on the first request and removed with the client
    Given a Kubernetes cluster
    And an account identified by a certificate client
    And a namespace "behat-test"
    And a temporary directory for the certificate files
    And an instance of this client
    And a pod model "my-pod"
    And the model is valid
    Then no temporary certificate file must remain
    When the user create the resource on the server
    Then the server must return an array as response
    And without error
    And 2 temporary certificate files must exist
    When the user releases the client
    Then no temporary certificate file must remain

  Scenario: Certificates embedded in a kubeconfig are written at load time and removed with the client
    Given a Kubernetes cluster
    And a temporary directory for the certificate files
    When the user loads a client from this kubeconfig:
      """
      apiVersion: v1
      clusters:
      - cluster:
          certificate-authority-data: Zm9vLWRhdGE=
          server: https://api.example.com
        name: cluster-name
      contexts:
      - context:
          cluster: cluster-name
          user: cluster-user
        name: context-name
      current-context: context-name
      kind: Config
      users:
      - name: cluster-user
        user:
          client-certificate-data: Zm9vLWRhdGE=
          client-key-data: Zm9vLWRhdGE=
      """
    Then without error
    And 3 temporary certificate files must exist
    Given the cluster has several registered pods
    When the user fetch a collection on the server
    Then the server must return a collection of pods
    And without error
    When the user releases the client
    Then no temporary certificate file must remain
