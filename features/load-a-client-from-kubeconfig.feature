Feature: Load a client from a kubeconfig
  Build a client from a kubeconfig document, refusing invalid documents early

  Scenario: Invalid base64 certificate data is refused and no file is left behind
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
          client-certificate-data: "this is not base64!"
          client-key-data: Zm9vLWRhdGE=
      """
    Then the client must refuse the operation with an invalid argument error
    And no temporary certificate file must remain

  Scenario: The token and the namespace of the current context are used
    Given a Kubernetes cluster
    When the user loads a client from this kubeconfig:
      """
      apiVersion: v1
      clusters:
      - cluster:
          server: https://api.example.com
        name: cluster-name
      contexts:
      - context:
          cluster: cluster-name
          user: cluster-user
          namespace: team-a
        name: context-name
      current-context: context-name
      kind: Config
      users:
      - name: cluster-user
        user:
          token: kubeconfig-token
      """
    Then without error
    Given the cluster has several registered pods
    When the user fetch a collection on the server
    Then the server must return a collection of pods
    And without error
    And the last request sent to the cluster must target the uri "https://api.example.com/api/v1/namespaces/team-a/pods"
    And the last request sent to the cluster must have the header "Authorization" equal to "Bearer kubeconfig-token"

  Scenario: Certificate files referenced by relative paths are resolved against the kubeconfig directory
    Given a Kubernetes cluster
    And a temporary directory for the certificate files
    And a certificate file "ca.pem" in the temporary directory
    And a certificate file "client.pem" in the temporary directory
    And a certificate file "client-key.pem" in the temporary directory
    When the user loads a client from this kubeconfig:
      """
      apiVersion: v1
      clusters:
      - cluster:
          certificate-authority: ca.pem
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
          client-certificate: client.pem
          client-key: client-key.pem
      """
    Then without error
    Given the cluster has several registered pods
    When the user fetch a collection on the server
    Then the server must return a collection of pods
    And without error
    And no temporary certificate file must remain

  Scenario: A missing certificate file is refused
    Given a Kubernetes cluster
    And a temporary directory for the certificate files
    When the user loads a client from this kubeconfig:
      """
      apiVersion: v1
      clusters:
      - cluster:
          certificate-authority: missing.pem
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
          token: foo
      """
    Then the client must refuse the operation with an invalid argument error
