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

FROM eclipse-temurin:21-jdk-alpine AS alpine-base
RUN apk update

# https://learn.microsoft.com/en-us/azure/app-service/configure-custom-container?tabs=alpine&pivots=container-linux#enable-ssh
COPY docker/sshd_config /etc/ssh/
COPY docker/entrypoint.sh ./

# Start and enable SSH
RUN apk add openssh \
    && echo "root:Docker!" | chpasswd \
    && chmod +x ./entrypoint.sh \
    && cd /etc/ssh/ \
    && ssh-keygen -A \
    && apk add openrc
RUN rc-update add sshd

#ENTRYPOINT [ "./entrypoint.sh" ]
EXPOSE 8000 2222


FROM alpine-base
VOLUME /tmp

EXPOSE 15800 80 2222

COPY --from=build /app/target/esap-backend-0.0.1-SNAPSHOT.jar esap-backend.jar

RUN apk add supervisor
COPY docker/supervisord.conf /etc/supervisord.conf
# Run supervisord
CMD ["supervisord", "-c", "/etc/supervisord.conf"]

ENTRYPOINT ["java","-jar","/esap-backend.jar"]