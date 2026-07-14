# Repository Rules

- Express every Eloquent query as `Model::query()->...`.
- Put each PHP trait declaration on its own line.
- Avoid PHPDoc/docblocks unless a tool or runtime behavior requires one.
- Access model primary keys with `->getKey()`, never `->id`.
