FROM php:7.4.33-cli-alpine3.16

WORKDIR /action

COPY ./ /action

RUN ["chmod", "+x", "/action/setup.sh"]

ENTRYPOINT "/action/setup.sh"

CMD ["-c","./config.ini"]