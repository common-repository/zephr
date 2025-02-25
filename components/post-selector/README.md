# PostSelector

Allows users to select a post or multiple posts using a search query against the REST API. Optionally, accepts a list of post types to which to restrict the search. Utilizes the search endpoint, so posts must have the appropriate visibility within the REST API to appear in the result list.

Importantly, this component does not save the selected post, it just returns it in the `onSelect` method. The enclosing block or component is responsible for managing the selected posts in some way, and using this component as a method for picking a new one.

## Usage

``` js
  <PostSelector
    className="custom-autocomplete-classname"
    emptyLabel="No posts."
    label="Label"
    multiple
    onSelect={onSelect}
    placeholder="Placeholder..."
    postTypes={['post', 'page']}
    selected={[{
      id: 123,
      title: 'Title of Element',
    }]}
    threshold={3}
  />
```

| Prop        | Default          | Required | Type     | Description                                                                                                                 |
|-------------|------------------|----------|----------|-----------------------------------------------------------------------------------------------------------------------------|
| className   |                  | false    | string   | If specified, the className is prepended to the top-level container.                                                        |
| emptyLabel  | No posts found   | false    | string   | If specified, this overrides the default language when no posts are found.                                                  |
| label       | Search for posts | false    | string   | If specified, this overrides the default label text for the post selection search input.                                    |
| multiple    | false            | false    | boolean  | If set to true the component allows for the ability to select multiple posts returned through the `onSelect` callback.      |
| onSelect    | NA               | true     | function | Callback to receive the selected post array, as it is returned from the `search` REST endpoint. Required.                   |
| placeholder | Search for posts | false    | string   | If specified, this overrides the default input placeholder value.                                                           |
| postTypes   | []               | false    | array    | All queryable post types that will be included in the form comma-separated array. The default query is "any" post type.     |
| selected    | []               | false    | array    | Optional array of objects with id and title keys to auto-hydrate selections on load.                                        |
| threshold   | 3                | false    | integer  | If specified, this overrides the default minimum number of characters that must be entered in order for the search to fire. |
