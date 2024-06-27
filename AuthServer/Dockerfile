# https://spring.io/guides/topicals/spring-boot-docker
FROM eclipse-temurin:21-jdk-alpine AS build
WORKDIR /app

COPY mvnw .
COPY .mvn .mvn
COPY pom.xml .
COPY maven-settings.xml .
ARG USE_NEXUS=TRUE
RUN if [ "$USE_NEXUS" = "TRUE" ] ; then \
      mkdir /root/.m2/; \
      cp ./maven-settings.xml /root/.m2/settings.xml; \
    fi
# Early resolve dependencies so they are cached by Docker
RUN chmod +x mvnw && sed -i 's/\r$//' mvnw
RUN ./mvnw dependency:go-offline

COPY src src

ARG CACHEBUST=42343324
RUN echo "Cache bust 1: $CACHEBUST" && pwd && ls -la .
RUN ./mvnw install -DskipTests

RUN mkdir -p target/dependency && (cd target/dependency; jar -xf ../*.jar)

ARG CACHEBUST=123
RUN echo "Cache bust 2: $CACHEBUST" && ls -la /app/target/

FROM eclipse-temurin:21-jdk-alpine

VOLUME /tmp

EXPOSE 8083

COPY --from=build /app/target/auth-server-0.0.1.jar AuthServer.jar
ARG CACHEBUST=ert
RUN echo "Cache bust 2: $CACHEBUST" && ls -la .

ENTRYPOINT ["java","-jar","/AuthServer.jar"]