#!/bin/bash

cd "$(dirname "$0")"

function phpspec {
	###########
	# PHPSpec #
	###########
	echo -e "\nPHPSpec\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-\n"

	vendor/bin/phpspec run
}

function behat {
	#########
	# Behat #
	#########
	echo -e "\nBehat\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-\n"

	if [[ ! -f ./behat.custom.yml ]]; then
	  vendor/bin/behat --stop-on-failure
	else
	  vendor/bin/behat --stop-on-failure --config ./behat.custom.yml
	fi
}

function phpdox {
	##########
	# PHPDox #
	##########
	echo -e "\nPHPDox\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-\n"
	vendor/bin/phpdox
}

if [[ 'quick' != "$1" ]]; then
	phpspec
	[[ $? -ne 0 ]] && exit
	behat
	[[ $? -ne 0 ]] && exit

fi

#########
# PHPQA #
#########
echo -e "\nPHPQA\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-\n"

vendor/bin/phpqa --analyzedDirs=source --buildDir=tests/build --report
phpqa_result=$?

if [[ 'quick' != "$1" ]]; then
	phpdox
fi

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

