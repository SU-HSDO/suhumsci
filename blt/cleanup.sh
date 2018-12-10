#!/bin/bash

# Remove sensitive keys
#rm -rf keys

# make all hook files executable.
chmod -R +x hooks/
rm vendor/simplesamlphp/simplesamlphp/config/local*