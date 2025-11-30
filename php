#!/bin/bash
docker-compose exec ${PHP_CONTAINER:-php} php "$@"