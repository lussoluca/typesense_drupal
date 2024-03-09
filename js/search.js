(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.search = {
    attach: function (context) {

      const [x] = once('searchbox', '#searchbox', context);
      if (x !== undefined) {
        return;
      }

      const typesenseInstantsearchAdapter = new TypesenseInstantSearchAdapter({
        server: {
          apiKey: 'ddev',
          nodes: [
            {
              host: 'typesense.ddev.site',
              port: 8108,
              protocol: 'https',
            },
          ],
        },
        additionalSearchParameters: {
          query_by: 'name',
        },
      });
      const searchClient = typesenseInstantsearchAdapter.searchClient;

      const search = instantsearch({
        searchClient,
        indexName: 'terms',
        routing: true,
        searchFunction(helper) {
          const container = document.querySelector('#results');
          container.style.display = helper.state.query === '' ? 'none' : '';

          helper.search();
        },
      });

      search.addWidgets([
        instantsearch.widgets.searchBox({
          container: '#searchbox',
          // searchAsYouType: false,
        }),
        instantsearch.widgets.hits({
          container: '#hits',
        }),
        instantsearch.widgets.pagination({
          container: '#pagination',
        }),
        instantsearch.widgets.stats({
          container: '#stats',
        }),
      ]);

      search.start();
    },
  };

}(jQuery, Drupal, drupalSettings));
