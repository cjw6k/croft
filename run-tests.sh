#!/bin/bash

cd "$(dirname "$0")"

###########
# PHPSpec #
###########
echo -e "\nPHPSpec\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-\n"

vendor/bin/phpspec run
[[ $? -ne 0 ]] && exit


#########
# Behat #
#########
echo -e "\nBehat\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-\n"

if [[ ! -f ./behat.custom.yml ]]; then
  vendor/bin/behat --stop-on-failure
else
  vendor/bin/behat --stop-on-failure --config ./behat.custom.yml
fi

[[ $? -ne 0 ]] && exit


#########
# PHPQA #
#########
echo -e "\nPHPQA\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-\n"

vendor/bin/phpqa --analyzedDirs=source --buildDir=tests/build --report
phpqa_result=$?


##########
# PHPDox #
##########
echo -e "\nPHPDox\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-\n"
vendor/bin/phpdox


# Given the public www root is ../public
[[ ! -d public/docs ]] && mkdir public/docs
[[ ! -d public/docs/qa ]] && mkdir public/docs/qa
cp tests/build/*.{xml,html,svg} public/docs/qa/
mv public/docs/qa/phpqa.html public/docs/qa/index.html
cp -r tests/build/phpmetrics public/docs/qa/

# If there were any errors in the PHPQA run, end now
[[ $phpqa_result -ne 0 ]] && exit

# Pom pom padinka!
echo -e "\nAll tests pass\n"

