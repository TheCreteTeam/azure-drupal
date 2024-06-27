# azure-drupal
In order to run Drupal execute the command in the root directory of the project:
```bash
docker-compose up
```

Then navigate to http://localhost:35020/ in your browser.

# Keycloak
To navigate to keycloak admin console, navigate to http://localhost:8180 in your browser.
credentials:
````
username: admin
password: password
````

Must create user in keycloak admin console to login to auth server.

Users -> Add User -> Fill in the details -> Save

Then Role Mappings -> Assign role -> Select login-app-admin role in client roles AND login-app-admin in real roles -> Save

Then create password in Credentials tab.