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
application, a CLI-driven console application or any combination thereof.

After the application files and folders are copied over, you will be asked if you
would like to configure a database. Follow those steps to configure a database and
create the database configuration file.

### Managing the Database

Once the application is initialized, you can manage the database by using the database
and migration commands.

```bash
./kettle db:config                  Configure the database
./kettle db:test                    Test the database connection
./kettle db:create-seed <class>     Create database seed class
./kettle db:seed                    Seed the database with data
./kettle db:reset                   Reset the database with original seed data
./kettle db:clear                   Clear the database of all data

./kettle migrate:create <class>     Create new database migration
./kettle migrate:run [<steps>]      Perform forward database migration
./kettle migrate:rollback [<steps>] Perform backward database migration
./kettle migrate:reset              Perform complete rollback of the database
```

#### Database Migrations

You can create the initial database migration that would create the tables by running
the command:

```bash
$ ./kettle migrate:create <class>
```

Where the `<class>` is the base class name of the migration class that will be created.
You will see your new migration class template in the `/database/migrations` folder:

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

From there, you can populate the `up()` and `down()` with the initial schema:

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

You can run the initial migration and create the `users` table by running the command:

```bash
$ ./kettle migrate:run
```

#### Seeding the Database
 
You can then seed the database with data in one of two ways. You can either place a
SQL file with the extension `.sql` in the `/database/seeds` folder or you can write
a seed class using PHP. To get a seed class started, you can run

```bash
$ ./kettle db:create-seed <class>
```

Where the `<class>` is the base class name of the seeder class that will be created.
The template seeder class will be copied to the `/database/seeds` folder:

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

From there you can fill in the `run()` method with the SQL you need to seed your data:

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
in any web browser and seeing the default index web page.

If you want to access the API application, the default route for that is `http://localhost:8000/api`
and you can access it like this:

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
