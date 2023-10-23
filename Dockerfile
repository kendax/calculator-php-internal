# Using php 7.2 as the base image
FROM php:7.3-apache

# Copying all project files into the container's /var/www/html directory so that Apache can host them
COPY . /var/www/html

# Open port 80 in the container which is the port that Apache runs on to serve HTTP
EXPOSE 80
