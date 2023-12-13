pop-kettle
==========

[![Join the chat at https://popphp.slack.com](https://media.popphp.org/img/slack.svg)](https://popphp.slack.com)
[![Join the chat at https://discord.gg/TZjgT74U7E](https://media.popphp.org/img/discord.svg)](https://discord.gg/TZjgT74U7E)

* [Overview](#overview)
* [Install](#install)
* [Initializing an Application](#initializing-an-application)
    + [Application Status](#application-status)
* [Kettle Include](#kettle-include)
* [Managing the Database](#managing-the-database)
    + [Seeding the Database](#seeding-the-database)
    + [Database Migrations](#database-migrations)
    + [Migration State Storage](#migration-state-storage)
* [Creating Application Files](#creating-application-files)
* [Running the Web Server](#running-the-web-server)
* [Accessing the Application](#accessing-the-application)
* [Using on Windows](#using-on-Windows)

Overview
--------

`pop-kettle` is a CLI-helper application for the Pop PHP Framework that allows
a user to quickly build the scaffolding for an application. It is included with
the Pop PHP Framework as the command `kettle` within the main project directory.

[Top](#pop-kettle)

Install
-------

The `pop-kettle` component comes automatically installed when you install the full Pop PHP
Framework. You should see the `kettle` script in the main project directory. However, if
that is not the case and you need to install it manually, you can place a copy of the `kettle`
script from the `vendor/popphp/pop-kettle/kettle` location in the main project folder
(adjacent to the `vendor` folder):

```bash
$ cp vendor/popphp/popphp-framework/kettle .
```
Once you've copied the script over, you have to change the reference to the script's
config file from:

```php
    $app = new Pop\Application(
        $autoloader, include __DIR__ . '/config/app.console.php'
    );
```

to

```php
    $app = new Pop\Application(
        $autoloader, include __DIR__ . '/vendor/popphp/pop-kettle/config/app.console.php'
    );
```

and make sure the newly copied `kettle` script is set to execute (755)

```bash
$ chmod 755 kettle
```

[Top](#pop-kettle)

Initializing an Application
---------------------------

By running the following command, you can set up the basic files and folders
required to run an application:

```bash
$ ./kettle app:init [--web] [--api] [--cli] <namespace>
```

The `<namespace>` parameter is the namespace of your application, for example `MyApp`.
The optional parameters of `--web`, `--api`, and `--cli` will create the related files
and folders to run the application as a normal web application, an API-driven web
application, a CLI-driven console application or any combination thereof. The default
route for the web application or the API application is `/`. However, if both are
initialized, then the default route for the API application becomes `/api`. The web
application will deliver a placeholder HTML page and the API application will deliver
a placeholder JSON response. 

The web/API application's front controller will be located in `public/index.php` and
the main script for the CLI application will be located in `script/myapp` (named
according to the provided \<namespace\> value.)

After the application files and folders are copied over, you will be asked if you
would like to configure a database. Follow those steps to configure a database and
create the database configuration file.

### Application Status

You can view and manage the status of the application with the following commands outlined below.

#### Check the current environment:

The environment is set in the `.env` file under the `APP_ENV` variable. Options available are:

- `local`
- `dev`
- `testing`
- `staging`
- `production` (or `prod`)

```bash
$ ./kettle app:env
```

#### Check (or change) the current status:

The status of the application can either be "live" or in "maintenance mode". The value is set
in the `.env` file under the `MAINTENANCE_MODE` variable (`true` or `false`).

```bash
$ ./kettle app:status
```

To put the application into maintenance mode, where it's not accessible, use the following command:

```bash
$ ./kettle app:down
```

You can generate a "secret" key to allow a select set of users to view the application while still in
maintenance mode:

```bash
$ ./kettle app:down --secret
```

When the command finishes, it will output the auto-generated secret:

```text
    The secret is SECRET_STRING
```

You can also provide your own secret:

```bash
$ ./kettle app:down --secret=MY_SECRET_STRING
```

Use that string one time in the browser as a URL query parameter to view the application while it is
still in maintenance mode. It will store in the browser's cookies so subsequent requests will be valid:

```text
http://localhost:8000/?secret=SECRET_STRING
```

To take the application out of maintenance mode and make it live again, use the following command:

```bash
$ ./kettle app:up
```

[Top](#pop-kettle)

Kettle Include
--------------

You should see a file `kettle.inc.php` next to the main `kettle` script. This serves
as a configuration file for anything additional that needs to be wired up for your
application to work with kettle. The file is included right after the creation of the
`$autoloader` and `$app` objects, so you will have direct access to them. In this file
you can add any additional runtime requirements, configurations or routes.

For example, there may be an instance were `kettle` needs to be aware of your application
and its namespace. You can access the autoloader here and register your application with
`kettle` in the `kettle.inc.php` file:

```php
$autoloader->addPsr4('MyApp\\', __DIR__ . '/app/src');
```

**Note:** If the `kettle.inc.php` file isn't available, you can copy it from the
`vendor/popphp/pop-kettle/kettle` location to the main project folder (adjacent to
the `vendor` folder.)

[Top](#pop-kettle)

Managing the Database
---------------------

Once the application is initialized, you can manage the database, or multiple databases,
by using the `db` and `migrate` commands. If you don't pass anything in the optional
`[<database>]` parameter, it will default to the `default` database.

```bash
./kettle db:install [<database>]                    Install the database (Runs the config, test and seed commands)
./kettle db:config [<database>]                     Configure the database
./kettle db:test [<database>]                       Test the database connection
./kettle db:create-seed <seed> [<database>]         Create database seed class
./kettle db:seed [<database>]                       Seed the database with data
./kettle db:export [<database>]                     Export the database to a file (MySQL only)
./kettle db:import <file> [<database>]              Import the database from a file (MySQL only)
./kettle db:reset [<database>]                      Reset the database with original seed data
./kettle db:clear [<database>]                      Clear the database of all data

./kettle migrate:create <class> [<database>]        Create new database migration class
./kettle migrate:run [<steps>] [<database>]         Perform forward database migration
./kettle migrate:rollback [<steps>] [<database>]    Perform backward database migration
./kettle migrate:point [<id>] [<database>]          Point to specific migration, w/o running (.current file only)'
./kettle migrate:reset [<database>]                 Perform complete rollback of the database
```

### Seeding the Database
 
You can seed the database with data in one of two ways. You can either utilize a
SQL file with the extension `.sql` in the `/database/seeds/<database>` folder, or you
can write a seeder class using PHP. To create a seeder class, you can run:

```bash
$ ./kettle db:create-seed <seed> [<database>]
```

Where the `<seed>` is the base class name of the seeder class that will be created.
The template seeder class will be copied to the `/database/seeds/<database>` folder:

```php
<?php

use Pop\Db\Adapter\AbstractAdapter;
use Pop\Db\Sql\Seeder\AbstractSeeder;

class MyFirstSeeder extends AbstractSeeder
{

    public function run(AbstractAdapter $db): void
    {
        
    }

}
```

From there, you can fill in the `run()` method in the seeder class with the SQL you need to seed your data:

```php
<?php

use Pop\Db\Adapter\AbstractAdapter;
use Pop\Db\Sql\Seeder\AbstractSeeder;

class DatabaseSeeder extends AbstractSeeder
{
    
    public function run(AbstractAdapter $db): void
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

Then running the following command will execute any SQL in the seeder classes or any raw SQL in SQL files:

```bash
$ ./kettle db:seed
```

### Database Migrations

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

    public function up(): void
    {

    }

    public function down(): void
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

    public function up(): void
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

    public function down(): void
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

### Migration State Storage

The migration state storage can be stored in one of two places. By default, it will store in a file called
`.current` in the database migration folder, for example:

```text
/database/migrations/default/.current
```

However, it can also be stored in the database itself in a separate migrations table. This requires a file
called `.table` to be placed in the database migration folder:

```text
/database/migrations/default/.table
```

The contents of the table will be the table class name for the migrations table in the database, for example:

```text
MyApp\Table\Migrations
```

Please note, while `kettle` is a CLI-helper tool that assists in wiring up your initial application, it is
unaware of your application and its namespace. If you choose to manage database migrations with a database
table, `kettle` will have to be made aware of the namespace and location of your application. You can do
that by adding it to the autoloader in the `kettle.inc.php` file:

```php
$autoloader->addPsr4('MyApp\\', __DIR__ . '/app/src');
```

Reference [Kettle Include](#kettle-include) for more information.

[Top](#pop-kettle)

Creating Application Files
--------------------------

You can create skeleton application files with the `create` commands to assist you in wiring up various
MVC-based components, such as models, views and controllers: 

```bash
./kettle create:ctrl [--web] [--api] [--cli] <ctrl>      Create a new controller class
./kettle create:model <model>                            Create a new model class
./kettle create:view <view>                              Create a new view file
```

Once the respective class files or view scripts are created in the appropriate folders, you can then
open them up and begin writing your application code. 

[Top](#pop-kettle)

Running the Web Server
----------------------

`pop-kettle` also provides a simple way to run PHP's built-in web-server, by running the command:

```bash
$ ./kettle serve [--host=] [--port=] [--folder=]
```

This is for development environments only and it is strongly advised against using the built-in
web server in a production environment in any way.

[Top](#pop-kettle)

Accessing the Application
-------------------------

If you have wired up the beginnings of an application, you can then access the default routes
in the following ways. Assuming you've started the web server as described above using
`./kettle serve`, you can access the web application by going to the address `http://localhost:8000/`
in any web browser and seeing the default index HTML page.

If you create both a web and API application, the HTML application will be accessible at `http://localhost:8000/`.
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

[Top](#pop-kettle)

Using on Windows
----------------

Most UNIX-based environments should recognize the main `kettle` application script as a PHP
script and run it accordingly, without having to explicitly call the `php` command and pass
the script and its parameters into it. However, if you're on an environment like Windows,
depending on your exact environment set up, you will most likely have to prepend all of the
command calls with the `php` command, for example:

```bash
C:\popphp\pop-kettle>php kettle help
``` 
