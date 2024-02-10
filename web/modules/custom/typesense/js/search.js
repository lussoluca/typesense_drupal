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
              host: 'localhost',
              port: 8108,
              protocol: 'http',
            },
          ],
        },
        additionalSearchParameters: {
          // query_by: 'embedding',
          // query_by: 'title,body,embedding',
          query_by: 'title,body',
          exclude_fields: 'embedding',
        },
      });
      const searchClient = typesenseInstantsearchAdapter.searchClient;

      const search = instantsearch({
        searchClient,
        indexName: 'local',
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
          templates: {
            item: `
        <a href="/node/{{ nid }}">
          <div>
            <div class="hit-title">
              {{#helpers.highlight}}{ "attribute": "title" }{{/helpers.highlight}}
            </div>
            <div class="hit-snippet">
              {{#helpers.snippet}}{ "attribute": "body" }{{/helpers.snippet}}
            </div>
          </div>
        </a>
      `,
          },
        }),
        instantsearch.widgets.refinementList({
          container: '#category',
          attribute: 'author',
          searchable: true,
          operator: 'and',
          templates: {
            item(item, { html }) {
              const { url, label, count, isRefined } = item;

              return html`
        <a href="${url}" style="${isRefined ? 'font-weight: bold' : ''}">
          <span>${label}</span>
        </a>
      `;
            },
          },
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
