#!/bin/sh

for f in $(find `pwd` -name 'ex?.ipynb');
    do echo $f;
    # do ipython nbconvert --to markdown $f;
done
