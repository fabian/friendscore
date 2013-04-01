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

## Development

Run the server and open http://localhost:8000/ in your browser:

```
php app/console server:run
```

To execute the unit tests run the following command:

```
vendor/bin/phpunit -c app/
```
