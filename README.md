---
title: "Opencpu/exerciseR Setup and Notes"
author: Reto
---

# Repository Information

The repository contains all needed to get the exerciseR up and running.
The software consists of two parts:

* An opencpu-server with a small _R_ package (exerciser) provides the
    infrastructure/API to run _R_ code.
* `html` contains some code for the UI/UX. Simple php/jQuery code, uses
    sqlite3 to store the data.

In more detail:

* `README.md`: well, this readme file.
* `files`: contains the exercises (solutions/check files),
    user uploads, and the data base (sqlite3).
* `html`: contains the frontend (UI/UX).
* `exerciser`: contains the _R_ package used as API between UI and opencpu.

# Config files

* `cd /etc/opencpu/server.conf`. Don't forget to restart service after making changes.


# Local user to run the service

I am using the `opencpu` user to run the service. If not existing, the user has to
be added and requires a home directory where we store _R_ packages and _R_ environment
and _R_ profile files.

### Home directory

On my machine simply `/home/opencpu`

### Renviron file

Content of the file in `/home/opencpu/.Renviron`:

```
R_LIBS_USER='/usr/local/lib/opencpu/site-library'
```

### Rprofile file

Content of `/home/opencpu/.Rprofile`:

```
.libPaths("/home/opencpu/R/pkgs")

local({
   r <- getOption("repos")
   r["CRAN"] <- "https://cran.r-project.org/"
   options( repos = r )
})
```

**Note:** the symbolic link "`/home/opencpu/R/pkgs`" points on
"`/home/opencpu/R/3.6`" and is used for local package installation.
Makes the _R_ packages of the opencpu user independent from the system
packages - not sure whether this is easier to maintain or not. If you would
like to use the system package library make sure that the apparmor rules
allow you to read from the _R_ package library folder!


# Setting up the Deamon (systemd; opencpu.service)

```
[Unit]
Description=opencpu service
After=network.target
StartLimitIntervalSec=0

[Service]
Type=simple
Restart=always
RestartSec=1
User=opencpu
AppArmorProfile=opencpu-main
ExecStart=/usr/bin/env /usr/bin/Rscript -e "library('opencpu'); ocpu_start_server(5656)";
```

**Note:** Runs under the local user "`opencpu`" (needs to be created if not existing).

# Apparmor configuration (permissions)

opencpu comes with two profiles: `opencpu-main` and `opencpu-exec`. The server (see above)
is running under the `opencpu-main` profile. The files can be found here:

* `/etc/apparmor.d/opencpu-exec`
* `/etc/apparmor.d/opencpu-main`

... and internally load the files in `/etc/apparmor.d/opencpu.d/*` (exec only lodes
`base` and `custom`, the server, in addition, imports `server`). We need to add some
custom rules to allow our opencpu service to access the files we need to be able to
run the exerciseR. My rule set currently looks as follows:


```
## This is the file:
## - /etc/apparmor.d/opencpu.d/custom
##
## Custom apparmor policy rules for the opencpu server/service.
## You can add rules if your favorite packages need special privileges.

# Allow to read/write in the temporary folder
/tmp/**    rwmklix,
/tmp/      rwmix,

# Allow to read Renvironment and Rprofile file
@{HOME}/.Renviron  r, 
@{HOME}/.Rprofile  r, 

# Path with user package library
@{HOME}/R/** rwmklix,
@{HOME}/R/   rmix, 
```
