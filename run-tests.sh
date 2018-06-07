#!/bin/sh

###########
# PHPSpec #
###########
echo -e "\nPHPSpec\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-\n"

./vendor/bin/phpspec run
[[ $? -ne 0 ]] && exit


#########
# Behat #
#########
echo -e "\nBehat\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-\n"

if [[ ! -f ./behat.custom.yml ]]; then
  ./vendor/bin/behat --stop-on-failure
else
  ./vendor/bin/behat --stop-on-failure --config ./behat.custom.yml
fi

[[ $? -ne 0 ]] && exit


#########
# PHPQA #
#########
echo -e "\nPHPQA\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-\n"

./vendor/bin/phpqa --analyzedDirs=app/code --buildDir=var/build --report --tools=phpmetrics:0,phploc:0,phpcs:0,phpmd:0,pdepend:0,phpcpd:0,parallel-lint:0,phpstan:0
phpqa_result=$?


##########
# PHPDox #
##########
echo -e "\nPHPDox\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-\n"
./vendor/bin/phpdox


# Given the public www root is ./public
[[ ! -d public/docs ]] && mkdir public/docs
[[ ! -d public/docs/qa ]] && mkdir public/docs/qa
cp var/build/*.{xml,html,svg} public/docs/qa/
mv public/docs/qa/phpqa.html public/docs/qa/index.html
cp -r var/build/phpmetrics public/docs/qa/

# If there were any errors in the PHPQA run, end now
[[ $phpqa_result -ne 0 ]] && exit

# Pom pom padinka!
echo -e "\nAll tests pass\n"

