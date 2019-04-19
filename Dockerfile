# Installing R
FROM rocker/r-base
#FROM rocker/r-devel
#FROM ubuntu:18.04

# Set the working directory to /app
WORKDIR /script

# Copy the current directory contents into the container at /app
COPY myscript.R /script

# Define environment variable
##ENV NAME World

# Run app.py when the container launches
#CMD RScript myscript.R

CMD ["/bin/bash"]
