# Typesense Search API

## Quick Start

1. Make sure you have a Typesense server running
2. Enable the module
3. Create a new Search API Typesense server at

        `/admin/config/search/search-api/add-server`

as per the [Search API documentation](https://www.drupal.org/docs/8/modules/search-api/getting-started/adding-a-server), selecting "Search API Typesense" as **Backend** option.

4. Configure the server credentials at

        `/admin/config/search/search-api/server/{server_name}/edit`

5. Create a new index on the server and add fields at

        /admin/config/search/search-api/add-index

and replace Search API datatypes with Typesense datatypes (for example `string` becomes `Typesense: string`).

6. Configure the Typesense Schema processor at

        `/admin/config/search/search-api/index/{index_name}/processors`

7. Index content from the UI or cli

Now your brand new index is a Typesense collection, that you can check from Typesense dashboard.

**There are sure to be bugs :)**

## Typesense documentation

You can find a clear and valuable documentation on the [Typesense API Reference](https://typesense.org/docs/0.25.2/api/).
