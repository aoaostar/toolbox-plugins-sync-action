FROM composer:latest as builder

WORKDIR /tmp/action

COPY ./ /tmp/action

RUN composer install --no-dev --ignore-platform-reqs

FROM php:7.4.33-cli-alpine3.16

COPY --from=builder /tmp/action /action/

WORKDIR /action

COPY ./setup.sh ./setup.sh

RUN ["chmod", "+x", "./setup.sh"]

ENTRYPOINT "/action/setup.sh"