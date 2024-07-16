FROM eclipse-temurin:21-jdk-alpine AS build
WORKDIR /app

COPY mvnw .
COPY .mvn .mvn
COPY pom.xml .

RUN chmod +x mvnw && sed -i 's/\r$//' mvnw
RUN ./mvnw dependency:go-offline

COPY src src

RUN ./mvnw install -DskipTests

RUN mkdir -p target/dependency && (cd target/dependency; jar -xf ../*.jar)

FROM eclipse-temurin:21-jdk-alpine
VOLUME /tmp

EXPOSE 8083

COPY --from=build /app/target/esap-backend-0.0.1-SNAPSHOT.jar esap-backend.jar

ENTRYPOINT ["java","-jar","/esap-backend.jar"]