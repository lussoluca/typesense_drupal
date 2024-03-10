((Drupal, TypesenseInstantSearchAdapter, instantsearch) => {
  Drupal.behaviors.search = {
    attach(context, settings) {
      const [searchbox] = once('searchbox', '#searchbox', context);
      if (searchbox !== undefined) {
        return;
      }

      const typesenseInstantsearchAdapter = new TypesenseInstantSearchAdapter({
        server: {
          apiKey: settings.search_api_typesense.api_key,
          nodes: [
            {
              host: settings.search_api_typesense.host,
              port: settings.search_api_typesense.port,
              protocol: settings.search_api_typesense.protocol,
            },
          ],
        },
        additionalSearchParameters: {
          query_by: settings.search_api_typesense.query_by_fields,
        },
      });
      const { searchClient } = typesenseInstantsearchAdapter;

      const search = instantsearch({
        searchClient,
        indexName: settings.search_api_typesense.index,
        routing: true,
      });

      let template = '<article>';
      settings.search_api_typesense.all_fields.forEach((field) => {
        template += `<p><strong>${field}</strong>: {{#helpers.snippet}}{ "attribute": "${field}" }{{/helpers.snippet}}</p>`;
      });
      template += '</article>';

      search.addWidgets([
        instantsearch.widgets.searchBox({
          container: '#searchbox',
        }),
        instantsearch.widgets.hits({
          container: '#hits',
          templates: {
            item: template,
          },
        }),
        instantsearch.widgets.pagination({
          container: '#pagination',
        }),
        instantsearch.widgets.stats({
          container: '#stats',
        }),
      ]);

      settings.search_api_typesense.facet_string_fields.forEach((facet) => {
        search.addWidgets([
          instantsearch.widgets.refinementList({
            container: `#${facet}`,
            attribute: facet,
            searchable: true,
          }),
        ]);
      });

      search.start();
    },
  };
})(Drupal, TypesenseInstantSearchAdapter, instantsearch); // eslint-disable-line
