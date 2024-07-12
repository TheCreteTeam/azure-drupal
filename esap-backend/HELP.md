# To Run this project do the following: 

- Run the following command in cmd to create a redis container
```batch
docker run -d --name redis-stack-container -p 6379:6379 redis/redis-stack:latest
```
- Have mssql container running
- Make sure you created the database in mssql
- update application.properties with the correct mssql connection string and user/pass
- Run the project as a spring boot application
- Open Postman ESMA-ESAP collection for the endpoints