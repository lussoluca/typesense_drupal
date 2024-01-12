# Setup instructions

## Prerequisites

* Git
* Docker
* DDEV

## Installation

1. Clone the repository
2. Run `ddev start`
3. Run `ddev composer install`
4. Run `ddev drush site-install --account-pass=admin -y`
5. Go `https://dumps.wikimedia.org/itwiki/latest/` and download the file `itwiki-latest-pages-articles1.xml-p1p316052.bz2 `
6. Decompress the file and put it in the `web` folder with the name `itwiki-latest-pages-articles1.xml`
7. Run `ddev drush pm:enable typesense`
8. Run `ddev drush typesense:import`, this will create 20k nodes by reading from the Wikipedia dump file
9. Run `ddev drush typesense:create-collection`, this will create the Typesense collection
10. Run `ddev drush typesense:index`, this will index the nodes in the Typesense collection
11. Run `ddev drush pm:uninstall search``
12. Navigate to `https://typesense-drupal.ddev.site/search` and search for something

A dashboard for Typesense is available at `https://bfritscher.github.io/typesense-dashboard`, the API key is `xyz`.
