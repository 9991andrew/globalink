#!/bin/bash
find ../ -name "*.php" -maxdepth 1 >POTFILES && find ../includes -name "*.php" >>POTFILES && find ../classes -name "*.php" >>POTFILES
xgettext -n --files-from=POTFILES -p ../locale/en_CA/LC_MESSAGES
rm POTFILES