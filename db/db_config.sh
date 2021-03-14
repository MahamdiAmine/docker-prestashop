#!/bin/bash

set -e
mysql="mysql -u root -h 127.0.0.1 -P 3305"

# create a shared role to read & write general datasets into postgres
echo "Creating database role: metabase"
$mysql <<-EOSQL
CREATE USER metabase WITH
    LOGIN
    NOSUPERUSER
    NOCREATEDB
    NOCREATEROLE
    NOINHERIT
    NOREPLICATION
    PASSWORD 'METABASE_PASSWORD';
EOSQL
#echo "Starting"
#
#mysql -h localhost -u root -p"${MYSQL_ROOT_PASSWORD}" -D "${MYSQL_DATABASE}" < /data/prod_light_2.sql
#echo "Done"
