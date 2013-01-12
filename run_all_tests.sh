#!/bin/bash

PHP="$(which php)"
BUNDLES="$(dirname $0)/bundles/"
mv application/bundles.php application/app-bundles.php
mv application/test-bundles.php application/bundles.php

for bundle in $BUNDLES*; do
   if [ -d "$bundle" ]; then
      eval $PHP " -f artisan test $( basename $bundle )"
   fi
done

mv application/bundles.php application/test-bundles.php
mv application/app-bundles.php application/bundles.php

echo "Done all tests"
