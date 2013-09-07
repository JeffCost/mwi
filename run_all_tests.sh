#!/bin/bash

PHP="$(which php)"
BUNDLES="$(dirname $0)/bundles/"
mv application/bundles.php application/app-bundles.php

`echo "<?php " > ./application/test-bundles.php`
`echo "return array(" >> ./application/test-bundles.php`

for bundle in $BUNDLES*; do
   if [ -d "$bundle" ]; then
      `echo "    '$( basename $bundle )' => array('auto' => true)," >> ./application/test-bundles.php`
   fi
done
`echo ");" >> ./application/test-bundles.php`

mv application/test-bundles.php application/bundles.php

for bundle in $BUNDLES*; do
    if [ -d "$bundle" ]; then
        echo "Starting test for $( basename $bundle )"
        eval $PHP " -f artisan test $( basename $bundle )"
    fi
done

#mv application/bundles.php application/test-bundles.php
mv application/app-bundles.php application/bundles.php

echo "Done all tests"
