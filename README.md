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
                "database": "your_database"
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

