#/bin/bash

echo "Downloading last release of Drupal's Sassy ..."
curl -s -o last_sassy_release.tar.gz `curl -s "http://drupal.org/project/sassy" | grep "release-update-status" -A10 | grep "tar\.gz" | sed -e 's/.*href="\([^"]*\)">tar\.gz.*/\1/'`

sassyExtPath="sassy/extensions/compass"
className="compassFunctions"

echo "Extracting compass stylesheets ..."
tar -xzf last_sassy_release.tar.gz "$sassyExtPath"

rm -rf stylesheets
mv "$sassyExtPath"/stylesheets .

echo "Building compass functions class ..."
classFilename="$className".class.php

echo -e "<?php\nclass $className {" > "$classFilename"
cat "$sassyExtPath"/sassy_compass.module | grep -v "^<?php" | grep -v "require_once" >> "$classFilename"
find "$sassyExtPath"/functions/ -name "*.inc" -exec grep -v "^<?php" "{}" \; | grep -v "require_once" >> "$classFilename"
echo "}" >> "$classFilename"

cat "$classFilename" | perl -pe 's/(?<!function) sassy_compass__/ \$this->sassy_compass__/g' > "$classFilename".tmp
mv "$classFilename".tmp "$classFilename"

echo "Cleanup ..."
rm -rf sassy
rm -rf last_sassy_release.tar.gz


