pop-kettle
==========

OVERVIEW
--------

`pop-kettle` is a CLI-helper application for the Pop PHP Framework that allows
a user to quickly build the scaffolding for an application. It is included with
the Pop PHP Framework as the command `kettle` within the main directory.

## BASIC USAGE

* [Initializing an Application](#initializing-an-application)
* [Managing the Database](#managing-the-database)
* [Running the Web Server](#running-the-web-server)
* [Accessing the Application](#accessing-the-application)
* [Using on Windows](#using-on-Windows)

### Initializing an Application

By running the following command, you can set up the basic files and folders
required to run an application:

```bash
$ ./kettle app:init [--web] [--api] [--cli] <namespace>
```

The `<namespace>` parameter is the namespace of your application, for example `MyApp`.
The optional parameters of `--web`, `--api`, and `--cli` will create the related files
and folders to run the application as a normal web application, an API-driven web
application, a CLI-driven console application or any combination thereof. The default
route for the web application is `/` and the default route for the API application
is `/api`. The web application will deliver a placeholder HTML page and the API
application will deliver a placeholder JSON response. 

After the application files and folders are copied over, you will be asked if you
would like to configure a database. Follow those steps to configure a database and
create the database configuration file.

### Managing the Database

Once the application is initialized, you can manage the database, or multiple databases,
by using the `db` and `migrate` commands. If you don't pass anything in the optional
`[<database>]` parameter, it will default to the `default` database.

```bash
./kettle db:install [<database>]                    Install the database (Runs the config, test and seed commands)
./kettle db:config [<database>]                     Configure the database
./kettle db:test [<database>]                       Test the database connection
./kettle db:create-seed <seed> [<database>]         Create database seed class
./kettle db:seed [<database>]                       Seed the database with data
./kettle db:reset [<database>]                      Reset the database with original seed data
./kettle db:clear [<database>]                      Clear the database of all data

./kettle migrate:create <class> [<database>]        Create new database migration class
./kettle migrate:run [<steps>] [<database>]         Perform forward database migration
./kettle migrate:rollback [<steps>] [<database>]    Perform backward database migration
./kettle migrate:reset [<database>]                 Perform complete rollback of the database
```

#### Seeding the Database
 
You can seed the database with data in one of two ways. You can either utilize a
SQL file with the extension `.sql` in the `/database/seeds/<database>` folder or you
can write a seeder class using PHP. To get a seed started, you can run

```bash
$ ./kettle db:create-seed <seed> [<database>]
```

Where the `<seed>` is either the base class name of the seeder class that will be created, or
the name of a SQL file (i.e., `seed.sql`) that will be populated later with raw SQL by the user.
The template seeder class will be copied to the `/database/seeds/<database>` folder:

```php
<?php

use Pop\Db\Adapter\AbstractAdapter;
use Pop\Db\Sql\Seeder\AbstractSeeder;

class MyFirstSeeder extends AbstractSeeder
{

    public function run(AbstractAdapter $db)
    {
        
    }

}
```

From there, you can populate your SQL file with the raw SQL needed, or you can fill in
the `run()` method in the seeder class with the SQL you need to seed your data:

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

Then running the following command will execute any SQL in any SQL files or any of the SQL
in the seeder classes:

```bash
$ ./kettle db:seed
```

#### Database Migrations

You can create the initial database migration that would modify your database schema as
your application grows by running the command:

```bash
$ ./kettle migrate:create <class> [<database>]
```

Where the `<class>` is the base class name of the migration class that will be created.
You will see your new migration class template in the `/database/migrations/<database>` folder:

```php
<?php

use Pop\Db\Sql\Migration\AbstractMigration;

class MyFirstMigration5dd822cdede29 extends AbstractMigration
{

    public function up()
    {

    }

    public function down()
    {

    }

} 
```

From there, you can populate the `up()` and `down()` with the schema to modify your database:

```php
<?php

use Pop\Db\Sql\Migration\AbstractMigration;

class MyFirstMigration5dd822cdede29 extends AbstractMigration
{

    public function up()
    {
        $schema = $this->db->createSchema();
        $schema->create('users')
            ->int('id', 16)->increment()
            ->varchar('username', 255)
            ->varchar('password', 255)
            ->varchar('email', 255)
            ->primary('id');
        
        $schema->execute();     
    }

    public function down()
    {
        $schema = $this->db->createSchema();
        $schema->drop('users');
        $schema->execute();
    }

}
```

You can run the migration and create the `users` table by running the command:

```bash
$ ./kettle migrate:run
```

And you can rollback the migration and drop the `users` table by running the command:

```bash
$ ./kettle migrate:rollback
```

### Running the Web Server

`pop-kettle` also provides a simple way to run PHP's built-in web-server, by running the command:

```bash
$ ./kettle serve [--host=] [--port=] [--folder=]
```

This is for development environments only and it is strongly advised against using the built-in
web server in a production environment in any way.

### Accessing the Application

If you have wired up the beginnings of an application, you can then access the default routes
in the following ways. Assuming you've started the web server as described above using
`./kettle serve`, you can access the web application by going to the address `http://localhost:8000/`
in any web browser and seeing the default index HTML page.

If you want to access the API application, the default route for that is `http://localhost:8000/api`
and you can access it like this to see the default JSON response:

```bash
$ curl -i -X GET http://localhost:8000/api
```

And, if you `cd script`, you'll see the default CLI application that was created. The default
route available to the CLI application is the `help` route:

```bash
$ ./myapp help
```

### Using on Windows

Most UNIX-based environments should recognize the main `kettle` application script as a PHP
script and run it accordingly, without having to explicitly call the `php` command and pass
the script and its parameters into it. However, if you're on an environment like Windows,
depending on your exact environment set up, you will most likely have to prepend all of the
command calls with the `php` command, for example:

```bash
C:\popphp\pop-kettle>php kettle help
``` 
