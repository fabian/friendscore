# Friend Score

## Installation

First checkout source code and install the required dependencies:

```
git clone git@github.com:fabian/friendscore.git friendscore
php composer.phar install --dev
```

Then create the database and the tables:

```
php app/console doctrine:database:create
php app/console doctrine:migrations:migrate
```

## Elasticsearch

Download the latest version from http://www.elasticsearch.org/download/ and run it with:

```
bin/elasticsearch -f
```

## Development

Run the server and open http://localhost:8000/ in your browser:

```
php app/console server:run
```

To execute the unit tests run the following command:

```
bin/phpunit -c app/
```

After running the tests you can view the coverage with: 

```
open app/coverage/index.html
```
