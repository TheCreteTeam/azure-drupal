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

## NEXTJS authentification
to login in nextjs app, create a client in keycloak admin console with the following settings:
````
Valid redirect URIs: http://localhost:3000/*
Valid post logout redirect URIs: http://localhost:3000
````

Then in the .env.local file add the following variables with the values from the client created in keycloak admin console:
````
KEYCLOAK_CLIENT_ID="nextjs"
KEYCLOAK_CLIENT_SECRET="kwtmEpG4mVzOeVVxEs6MRvpK4hJJOXeh"
KEYCLOAK_ISSUER="http://localhost:8180/realms/myrealm"
NEXTAUTH_URL="http://localhost:3000"
NEXTAUTH_SECRET="XO6MgKX9bFkoiQYDT1q3MzBlVWbs7tU67iq7rWK5MCA="
````

To create NEXTAUTH_SECRET, run the following command in the root directory of the project:
```bash
openssl rand -base64 32
```

Then navigate to http://localhost:3000/ in your browser.