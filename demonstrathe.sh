#!/bin/bash

# script for php MOD library work demonstrating 

if [ -n `which php` ] 
    then 
    echo '*****Demonstration of php MOD library work*****'

    echo '++++We hame a file example.html which contains this:++++'
    cat example.html

    cp example.html ._example.html

    echo '++++Then we run a php script exampleInstall.php++++'
    php exampleInstall.php > /dev/null 2>&1

    echo '++++And now example.html contains this:++++'
    cat example.html
    
    echo '++++We expect it to be like this:++++'
    cat expected.html

    if ! `cmp example.html expected.html >/dev/null 2>&1`
        then
        echo '++++Something went wrong - files is not like we expect it to be++++'
        else
        echo '++++File is exactly we want it to be++++'
    fi

    mv ._example.html example.html

    else
    echo '++++Can`t find php!++++'
fi