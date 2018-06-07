# a6a
Also known as: a -six letters- a  
Also known as: aetheria

## Code Documentation
To generate the documentation, run phpdox with `./vendor/bin/phpdox`. This generates and copies the documentation into `./public/docs/`.

**Note:** Running the tests also generates updated documentation, but only if the behat & phpspec tests all pass.  
**Note:** If you don't use `./public` for your public www root, adjust the `./run-tests.sh` script to suit your setup.

## Testing
To run all the tests, use `./run-tests.sh`. This copies the reports into `./public/docs/qa`.

**Note:** If you don't use `./public` for your public www root, adjust the `./run-tests.sh` script to suit your setup.



-----



#### Required hack for tests toolchain to work as of 2018-06-07
There is a limitation with using tabs as the indentation method and phpcs. I don't try to understand it, i just fix it:
```shell
$ mv vendor/bin/phpcs vendor/bin/phpcs-default
```
and create the replacement `./vendor/bin/phpcs` with contents:
```shell
#!/bin/sh

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
$DIR/phpcs-default "$@" --tab-width=4
```

## License

See the [LICENSE](LICENSE.md) file for license rights and limitations (MIT).