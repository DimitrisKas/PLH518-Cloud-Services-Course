FROM mysql

WORKDIR /var/lib/mysql
COPY project1.sql /docker-entrypoint-initdb.d/project1.sql

RUN chmod -R 775 /docker-entrypoint-initdb.d
