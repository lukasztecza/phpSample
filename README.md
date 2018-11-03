# phpSample
Sample application using lightApp

### How to use
- clone repo
- run `composer install`
- run `vagrant up`
- for tests bootstrap `php vendor/codeception/codeception/codecept bootstrap` 
- add tests and run `php vendor/codeception/codeception/codecept run`
- create `src/Config/parameters.json` - you may use `scripts/createParameters.php`
- you may want to use these values:
```
environment: dev
databaseEngine: mysql
databaseHost: localhost
databasePort: 3306
databaseName: some_db
databaseUser: user
databasePassword: pass
inMemoryUsername: user
inMemoryPasswordHash: pass
```
- app should be accessible at `http://localhost:8080/`
