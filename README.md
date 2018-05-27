# devec-demography-data-portal

## Getting the data

The data is spread out across multiple repositories:

- [maddison-project-data](https://github.com/riceissa/maddison-project-data)
- [penn-world-table-data](https://github.com/riceissa/penn-world-table-data)
- [world development indicators](https://github.com/riceissa/world-development-indicators)
- [total-economy-database](https://github.com/riceissa/total-economy-database)
  (this one doesn't contain the data in the repository itself)
- [fred-processing](https://github.com/riceissa/fred-processing)
- [devec\_sql\_common](https://github.com/riceissa/devec_sql_common) contains
  common functions for use when working with all datasets

## Setting up

If you need to pass in arguments to `mysql`, use the `MYSQL_ARGS` variable. For
example:

```bash
vi my.cnf  # put config options in this file
make MYSQL_ARGS="--defaults-extra-file=my.cnf"
```

(So far the makefile doesn't do anything with MySQL so this shouldn't be
necessary.)

Once the database is ready, you can run the site locally:

```bash
cp access-portal/backend/globalVariables/{dummyPasswordFile.inc,passwordFile.inc}
vi access-portal/backend/globalVariables/passwordFile.inc  # change to add database login info
cd access-portal
php -S localhost:8000
```

Now you can visit `http://localhost:8000/index.php` in your browser.

For table sorting:

    make fetch_tablesorter

This will fetch the necessary files to allow tables to be sorted.
To remove these files, run `make clean_tablesorter`.

For [AnchorJS](https://github.com/bryanbraun/anchorjs):

    make fetch_anchorjs

To remove AnchorJS, run `make clean_anchorjs`.

## License

CC0.
