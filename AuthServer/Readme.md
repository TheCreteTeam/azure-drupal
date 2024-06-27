Runs without docker compose, but it is available 
application.yml:
```yaml
docker:
    compose:
        enabled: false
```

Authenticates the controller FooController
```java
Authentication authentication = SecurityContextHolder.getContext().getAuthentication();
```

Authentication method is Code Grant Authorization.
Using InMemory repository for user with credentials:
user: admin
password: secret

To simulate external system asking for authoriazation, the following scenario is implemented:
External system or service URL: http://localhost:8084

Our authorization server URL: http://localhost:8083

Redirection URL: http://localhost:8083/oauth2/authorize?response_type=code&client_id=demo-client&redirect_uri=http://localhost:8084/auth

The above URL navigates to a page that does not exist, but the URL will contain the code parameter.

http://localhost:8084/auth?code=deTOtgK8iclKsx5ZNVQclnxmCi_wEgFBwWxs0HEYhKhkOmLAnsq5481OENWsrUvwgJ2a442_r4C4COEteOOFDRyf-bf1BYpTZIw7_g7xtUj90OcdKLom43pgBAwhFief

The code parameter is used to get the access token from the authorization server by adding it to the request code parameter of the body.
Via Postman, the following request is made:
```curl
curl --location 'localhost:8083/oauth2/token' \
--header 'Content-Type: application/x-www-form-urlencoded' \
--header 'Authorization: Basic ZGVtby1jbGllbnQ6ZGVtby1zZWNyZXQ=' \
--data-urlencode 'grant_type=authorization_code' \
--data-urlencode 'code=deTOtgK8iclKsx5ZNVQclnxmCi_wEgFBwWxs0HEYhKhkOmLAnsq5481OENWsrUvwgJ2a442_r4C4COEteOOFDRyf-bf1BYpTZIw7_g7xtUj90OcdKLom43pgBAwhFief' \
--data-urlencode 'redirect_uri=http://localhost:8084/auth'
```

The response will contain the access token and refresh token:
```json
{
    "access_token": "eyJraWQiOiJiNjBiNjY5Yi1jMTQzLTRiYzQtOTBkYS1lODUyMDYyZjYzNjQiLCJhbGciOiJSUzI1NiJ9.eyJzdWIiOiJhZG1pbiIsImF1ZCI6ImRlbW8tY2xpZW50IiwibmJmIjoxNzEzNTIzNDE3LCJpc3MiOiJodHRwOi8vYXV0aC1zZXJ2ZXI6OTAwMCIsImV4cCI6MTcxMzUyMzcxNywiaWF0IjoxNzEzNTIzNDE3LCJqdGkiOiI1ZGQ5MDBiYy05NDIzLTQ0ZmMtYjBlZi01OWM4OGRjMGU2YjUiLCJhdXRob3JpdGllcyI6WyJBUlRJQ0xFX1dSSVRFIiwiQVJUSUNMRV9SRUFEIl19.G1fUeGgZFunr7pRNYgcCnHxNhfRPKWLFoQY3WafldwILc0oeREOWPa1a1HBcD2DCldsxQ3Y9IN4eiTwBrdhi9B4EgJQoMlxjiou9EmM0jTgfJE1WCZmlq8FRDCuoKnxMAwaodXI12ilYpbV2DUr9my33exTHC1geHtesHdZjwjrV5ZpHiWkTYiNN1Q1PPxVhvjo2KOyWd3rzzm6myTmQetoitp2PYKy6Ccz2eGwUZqyPxb2-ETNIHh3eTlupJOvhsDNtdH0E-spl5o3x7L0brfe76IIlsRyGPpka0c7OIFmd7HzQaaryyfdcMqMN4FfeVniRttUQflyEVMvOwJYz4g",
    "refresh_token": "CxRU1K8BecPbqngUJYL9YpO8L8YBZ4L5FKluR3loyFZO9lW7L-2-lHrEqN_dTrJsvjvrCx011binBSnllMgJMTzsYsZ83PtaK3JIiqeMD8kqDcSKaCJVZ0RkSR5atNGP",
    "token_type": "Bearer",
    "expires_in": 300
}
```

We have obtained a JWT access token. Now you can copy it and visit the website https://jwt.io to verify that it contains valid data.


Changes to run with react client:
in React Oidc configuration, the following changes are made:
```javascript
const oidcConfig = {

    authority: 'http://localhost:8083', // Replace with the authority URL of your OpenID provider.
    client_id: 'demo-client', // Replace with the client ID assigned to your application by the OpenID provider.
    redirect_uri: 'http://localhost:3000/authorized', // Replace with the callback URL where the authentication response will be received.
    response_type: 'code', // Specify the response type. 'code' indicates the authorization code flow.
    scope: 'openid profile read write', // Adjust the scopes as per your requirements. 'openid' and 'profile' are common scopes for authentication and user profile information.
    automaticSilentRenew: true, // Enable automatic silent renewals for refreshing tokens.
    userStore: new WebStorageStateStore({ store: window.localStorage }), // Specify the store for storing the authentication data.
    loadUserInfo: true // Specify whether to load the user information from the user endpoint after successful authentication.
};
```