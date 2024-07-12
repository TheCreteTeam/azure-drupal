#TODO: Fix the Dockerfile to run the application in a container

FROM ubuntu:latest
LABEL authors="kntav"

ENTRYPOINT ["top", "-b"]

FROM maven:3.8.1-openjdk-11-slim AS build
WORKDIR /app
COPY pom.xml .
RUN mvn dependency:go-offline -B
COPY src ./src
RUN mvn package -DskipTests

# Stage 2: Run the application
FROM openjdk:11-jre-slim
WORKDIR /app
COPY --from=build /app/target/*.jar app.jar
EXPOSE 15800
ENTRYPOINT ["java","-jar","app.jar"]