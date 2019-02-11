pop-kettle
==========

OVERVIEW
--------

`pop-kettle` is a CLI-helper application for the Pop PHP Framework that allows
a user to quickly build the scaffolding for an application. It is included with
the Pop PHP Framework as the command `kettle` within the main directory.

## BASIC USAGE

* [Initializing an application](#initializing-an-application)
* [Managing the database](#managing-the-database)
* [Running the web server](#Running-the-web-server)

### Initializing an application

By running the following command, you can set up the basic files and folders
required to run an application:

```bash
$ ./kettle app:init [--web] [--api] [--cli] <namespace>
```

The `<namespace>` parameter is the namespace of your application, for example `MyApp`.
The optional parameters of `--web`, `--api`, and `--cli` will create the related files
and folders to run the application as a normal web application, an API-driven web
application, a CLI-driven console application or any combination thereof.

After the application files and folders are copied over, you will be asked if you
would like to configure a database. Follow those steps to configure a database and
create the database configuration file. 

### Managing the database

Once the application is initialized, you can manage the database by using the database
and migration commands.

```bash
./kettle db:config                  Configure the database
./kettle db:test                    Test the database connection
./kettle db:seed                    Seed the database with data
./kettle db:reset                   Reset the database with original seed data
./kettle db:clear                   Clear the database of all data

./kettle migrate:create <class>     Create new database migration
./kettle migrate:run [<steps>]      Perform forward database migration
./kettle migrate:rollback [<steps>] Perform backward database migration
./kettle migrate:reset              Perform complete rollback of the database
```

You can create the initial database migration that would create the tables by running
the command:

```bash
$ ./kettle migrate:create <class>
```

Where the `<class>` is the base class name of the migration class that will be created.
From there, you can populate the initial migration class with the initial schema:

```php
<?php

use Pop\Db\Sql\Migration\AbstractMigration;

class MyNewMigration extends AbstractMigration
{

    public function up()
    {
        $schema = $this->db->createSchema();
        $schema->create('users')
            ->int('id', 16)->increment()
            ->varchar('username', 255)
            ->varchar('password', 255)
            ->primary('id');
        
        $this->db->query($schema);        
    }

    public function down()
    {
        $schema = $this->db->createSchema();
        $schema->drop('users');
        $this->db->query($schema);
    }

}
```

Then by running the command:

```bash
$ ./kettle migrate:run
```

it will run the initial migration and create the `users` table, which can then been seeded,
as shown below. You can write your own seed files under the `/database/seeds` folder. An
example be:

```php
<?php

use Pop\Db\Adapter\AbstractAdapter;
use Pop\Db\Sql\Seeder\AbstractSeeder;

class DatabaseSeeder extends AbstractSeeder
{
    
    public function run(AbstractAdapter $db)
    {
        $sql = $db->createSql();
        
        $sql->insert('users')->values([
            'username' => 'testuser',
            'password' => '12test34',
            'email'    => 'test@test.com'
        ]);
        
        $db->query($sql);
    }
    
}
```

Then running the command:

```bash
$ ./kettle db:seed
```

will execute any seed files in the `seeds` folder and populate the database with the initial data.

#### Seeding with SQL files

Alternatively, you can place SQL files with the extension `.sql` in the `/database/seeds` folder
and they will be executed when you run the `./kettle db:seed` command.

### Running the web server

`pop-kettle` also provides a simple way to run PHP's built-in web-server, by running the command:

```bash
$ ./kettle serve [--host=] [--port=] [--folder=]
```

This is for development environments only and it is strongly advised against using the built-in
web server in a production environment in any way.
