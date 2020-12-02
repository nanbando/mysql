# Nanbando: mysql

Nanando-Plugin which uses `mysqldump` to backup and restore mysql databases.

## Installation

You can install this plugin by adding `nanbando/mysql` to the `require`-section of the nanbando.json file.

## Configuration

```json
{
    "name": "application",
    "backup": {
        "your_database": {
            "plugin": "mysql",
            "parameter": {
                "username": "root",
                "password": "***",
                "database": "your_database",
                "host": "127.0.0.1",
                "port": "3306"
            }
        }
    },
    "require": {
        "nanbando/mysql": "^0.1"
    }
}
```

As an alternative you can use the environment variable of Doctrine DBAL:

```json
{
    "name": "application",
    "backup": {
        "your_database": {
            "plugin": "mysql",
            "parameter": {
                "databaseUrl": "%env(DATABASE_URL)%"
            }
        }
    },
    "require": {
        "nanbando/mysql": "^0.1"
    }
}
```

## Documentation

See the official documentation on [nanbando.readthedocs.io/en/latest/plugins/index.html](https://nanbando.readthedocs.io/en/latest/plugins/index.html).

